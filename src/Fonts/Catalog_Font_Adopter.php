<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF_Vendor\Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns a coverage entry's files that are already on disk into installed rows
 *
 * Two things reach this: a site that ran the 6.x core font installer, and a site where someone placed a pack's
 * files by hand — the offline install path, and why the shipped seed index matters. The installer itself has no
 * "already on disk" branch; this is that branch, in one place, for every source.
 *
 * Sibling of `Legacy_Font_Adopter`, and deliberately **not** a `Font_Population_Pass`: those run once per site
 * inside `ensure_ready()`, gated on the schema version, while this runs after every sync that replaces a source
 * carrying coverage entries and is expected to find nothing most times.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Catalog_Font_Adopter {

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

	/**
	 * @var Catalog_Repository
	 * @since 7.0
	 */
	protected $catalog;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	/**
	 * @var string
	 * @since 7.0
	 */
	protected $font_dir;

	public function __construct( Font_Repository $repository, Catalog_Repository $catalog, LoggerInterface $log ) {
		$this->repository = $repository;
		$this->catalog    = $catalog;
		$this->log        = $log;
		$this->font_dir   = $repository->get_font_dir();
	}

	/**
	 * Adopt every verified file one source's coverage entries list
	 *
	 * Every candidate is verified — size first, then sha256 against the entry — before a row is written, so a row
	 * never claims a hash the disk does not have. A file that fails is left alone and a real install downloads it
	 * fresh. Idempotent: a matched file gains a row and a file with a row is skipped, so nothing is hashed twice
	 * across runs and a second call inserts nothing.
	 *
	 * @return int How many font rows were created
	 *
	 * @since 7.0
	 */
	public function run( string $source ): int {
		$this->repository->ensure_ready();

		$claimed = $this->repository->claimed_filenames();
		$taken   = array_flip( array_keys( $this->repository->all() ) );
		$created = 0;
		$failed  = [];

		foreach ( $this->catalog->coverage_entries_for_adoption( $source ) as $row ) {
			$created += $this->adopt_entry( $row, $claimed, $taken, $failed );
		}

		if ( $created > 0 || $failed !== [] ) {
			$this->log->notice(
				'Adopted font files already on disk',
				[
					'source'       => $source,
					'adopted'      => $created,
					'failed_check' => $failed,
				]
			);
		}

		return $created;
	}

	/**
	 * Adopt every font key of one entry whose faces are present and verified
	 *
	 * @param array               $row     A catalog row carrying its decoded `data`
	 * @param array<string, true> $claimed Filenames some file row already records
	 * @param array<string, true> $taken   Font keys already in use, kept current in memory because each insert
	 *                                     flushes the cache the answer would otherwise come from
	 * @param string[]            $failed  Files present but not what the entry describes, appended to
	 *
	 * @since 7.0
	 */
	protected function adopt_entry( array $row, array &$claimed, array &$taken, array &$failed ): int {
		$source   = (string) $row['source'];
		$entry_id = (string) $row['entry'];

		/*
		 * One stat rules out an entry that was never installed, which is the overwhelmingly common case: without
		 * it every role of every font key costs a miss, and these hosts are often NFS-backed.
		 */
		if ( ! is_dir( $this->font_dir . $source . '/' . $entry_id ) ) {
			return 0;
		}

		$entry   = (array) $row['data'];
		$files   = (array) ( $entry['files'] ?? [] );
		$fonts   = (array) ( $entry['fonts'] ?? [] );
		$created = 0;

		foreach ( $fonts as $font_key => $roles ) {
			$font_key = (string) $font_key;

			if ( isset( $taken[ $font_key ] ) || $this->repository->is_key_reserved( $font_key ) ) {
				continue;
			}

			$verified = $this->verified_files( $source, $entry_id, (array) $roles, $files, $claimed, $failed );

			/* mPDF needs a regular face; a bold-only row would never render */
			if ( ! isset( $verified['R'] ) ) {
				continue;
			}

			$font_id = $this->repository->insert(
				[
					'font_key'    => $font_key,
					'label'       => count( $fonts ) === 1 ? (string) $row['label'] : $font_key,
					'source'      => $source,
					'entry'       => $entry_id,
					'coverage'    => 1,
					'meta'        => Font_Sources::coverage_meta( $font_key, $entry, (array) $roles ),
					'version'     => $row['version'] ?? null,
					'use_otl'     => (int) ( $roles['useOTL'] ?? 0 ),
					'use_kashida' => (int) ( $roles['useKashida'] ?? 0 ),
					'files'       => $verified,
				]
			);

			if ( $font_id > 0 ) {
				++$created;

				$taken[ $font_key ] = true;

				foreach ( $verified as $file ) {
					$claimed[ $file['path'] ] = true;
				}
			}
		}

		return $created;
	}

	/**
	 * The faces of one font key that are on disk, unclaimed and byte-for-byte what the entry lists
	 *
	 * A source's files live under `{source}/{entry}/`, so adoption looks where an install would have written them
	 * — which is also where a hand-placed file has to go. The 6.x installer's flat files are `Legacy_Font_Adopter`'s
	 * job and are already rows by the time this runs.
	 *
	 * @return array<string, array{path: string, size: int, sha256: string}>
	 *
	 * @since 7.0
	 */
	protected function verified_files( string $source, string $entry_id, array $roles, array $files, array $claimed, array &$failed ): array {
		$verified = [];

		foreach ( $roles as $role => $filename ) {
			if ( in_array( $role, Font_Sources::NON_ROLE_KEYS, true ) || ! is_string( $filename ) ) {
				continue;
			}

			$listed = $files[ $filename ] ?? null;

			if ( ! is_array( $listed ) || ! isset( $listed['sha256'], $listed['size'] ) ) {
				continue;
			}

			$relative = $source . '/' . $entry_id . '/' . $filename;
			$absolute = $this->font_dir . $relative;

			if ( isset( $claimed[ $relative ] ) || ! is_file( $absolute ) ) {
				continue;
			}

			/* Size first, so a large file is never hashed only to be rejected on length */
			if ( (int) filesize( $absolute ) !== (int) $listed['size'] || ! hash_equals( (string) $listed['sha256'], (string) hash_file( 'sha256', $absolute ) ) ) {
				$failed[] = $relative;

				if ( $role === 'R' ) {
					return [];
				}

				continue;
			}

			$verified[ (string) $role ] = [
				'path'   => $relative,
				'size'   => (int) $listed['size'],
				'sha256' => (string) $listed['sha256'],
			];
		}

		return $verified;
	}
}
