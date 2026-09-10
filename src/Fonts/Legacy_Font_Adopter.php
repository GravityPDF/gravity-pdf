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
 * Turns the fonts the 6.x core font installer left on disk into font rows
 *
 * 7.0 removes both the installer and mPDF's built-in `fontdata` map, so nothing registers these files any more.
 * This runs once, from `ensure_ready()`, and re-registers what is actually there under the keys 6.x used — which
 * is what keeps a site that renders Korean through `unbatang` rendering it byte-identically after the upgrade,
 * even though a fresh install of the `korean` language pack ships a different face.
 *
 * It reads `Legacy_Installer_Files` and nothing else: no catalogue, no network, so it runs during an offline
 * upgrade. Every face is verified against the frozen manifest before it is written, so a truncated, replaced or
 * half-downloaded file produces no row rather than a phantom registration pointing at unusable bytes.
 *
 * The row carries the map's language half as its `meta`, which is what makes the adopted font reachable rather
 * than merely present: 6.x routed `ko` to UnBatang through mPDF's own `LanguageToFont`, a class 7.0 keeps out of
 * the registry, so without this the file would sit on disk with nothing resolving to it. `meta.legacy` puts every
 * such row in a tier below every pack (`Registry::precedence()`), so installing the Korean pack still moves Korean
 * to it by the ordinary rule.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Legacy_Font_Adopter implements Font_Population_Pass {

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

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

	public function __construct( Font_Repository $repository, LoggerInterface $log ) {
		$this->repository = $repository;
		$this->log        = $log;
		$this->font_dir   = $repository->get_font_dir();
	}

	/**
	 * Adopt every installer font still on disk
	 *
	 * @return int How many rows were created
	 *
	 * @since 7.0
	 */
	public function run(): int {
		$claimed = $this->repository->claimed_filenames();

		/*
		 * Taken keys are collected once and kept current in memory. Asking the repository per family would re-run
		 * its two queries every time, because each insert below flushes the cache the answer would come from.
		 */
		$taken      = array_flip( array_keys( $this->repository->all() ) );
		$created    = 0;
		$failed     = [];
		$taken_keys = [];
		$pending    = [];

		foreach ( Legacy_Installer_Files::FAMILIES as $font_key => $family ) {
			/*
			 * The key is frozen, so a collision means something already answers to it — a custom font the user
			 * uploaded under the same name, say. That row wins; re-keying this one would only add a font under a
			 * name no template mentions. Checked before the faces are hashed, which is the expensive half.
			 */
			if ( isset( $taken[ $font_key ] ) || $this->repository->is_key_reserved( $font_key ) ) {
				$taken_keys[] = $font_key;

				continue;
			}

			$faces = $this->verified_faces( $family, $claimed, $failed );

			/* Without a regular face there is no font: mPDF requires `R`, and a bold-only row would never render */
			if ( isset( $faces['R'] ) ) {
				$pending[ $font_key ] = $faces;
			}
		}

		/* `sip_ext` names another family, so nothing can be written until the whole pass knows what it adopted */
		$registered = $taken + $pending;

		foreach ( $pending as $font_key => $faces ) {
			$family = Legacy_Installer_Files::FAMILIES[ $font_key ];

			$row_id = $this->repository->insert(
				[
					'font_key'    => $font_key,
					'label'       => $font_key,
					'source'      => 'imported',
					'blog_id'     => $this->repository->current_blog_id(),
					'use_otl'     => $family['use_otl'],
					'use_kashida' => $family['use_kashida'],
					'meta'        => $this->meta( $family, $registered ),
					'files'       => $faces,
				]
			);

			if ( $row_id > 0 ) {
				++$created;
			}
		}

		/*
		 * Logged whenever anything happened, not only on success: an upgrade that adopts nothing because ten files
		 * failed their hash check is exactly the case someone will need this record to explain.
		 */
		if ( $created > 0 || $failed !== [] || $taken_keys !== [] ) {
			$this->log->notice(
				'Adopted the fonts the 6.x core font installer left behind',
				[
					'adopted'          => $created,
					'failed_check'     => $failed,
					'key_already_used' => $taken_keys,
				]
			);
		}

		return $created;
	}

	/**
	 * The `meta` that routes an adopted family, from the same frozen map its files came from
	 *
	 * @param array               $family     One `Legacy_Installer_Files::FAMILIES` entry
	 * @param array<string, mixed> $registered Keyed by every font key this site will hold once the pass is done
	 *
	 * @return array<string, mixed>
	 *
	 * @since 7.0
	 */
	protected function meta( array $family, array $registered ): array {
		$meta = [ 'legacy' => true ];

		foreach ( [ 'languages', 'backup_subs', 'bmp', 'aliases' ] as $key ) {
			if ( ! empty( $family[ $key ] ) ) {
				$meta[ $key ] = $family[ $key ];
			}
		}

		/* mPDF looks the named font up when it needs a Plane 2 glyph, so a name nothing answers to is worse than none */
		if ( isset( $family['sip_ext'], $registered[ $family['sip_ext'] ] ) ) {
			$meta['sip_ext'] = $family['sip_ext'];
		}

		return $meta;
	}

	/**
	 * The faces of one family that are present, unclaimed and byte-for-byte what the installer wrote
	 *
	 * The manifest lists `R` first for every family, and a family without it is dropped, so a failed regular face
	 * returns immediately rather than hashing up to three more files for a row that will not be written.
	 *
	 * @param array               $family  One `Legacy_Installer_Files::FAMILIES` entry
	 * @param array<string, true> $claimed
	 * @param string[]            $failed  Filenames present but not what the manifest describes, appended to
	 *
	 * @return array<string, array{path: string, size: int}>
	 *
	 * @since 7.0
	 */
	protected function verified_faces( array $family, array $claimed, array &$failed ): array {
		$faces = [];

		foreach ( $family['faces'] as $role => $face ) {
			$filename = $face['name'];
			$path     = $this->font_dir . $filename;

			if ( isset( $claimed[ $filename ] ) ) {
				continue;
			}

			if ( ! Legacy_Installer_Files::verify( $face, $path ) ) {
				/* Only a file that is there but wrong is worth reporting — most of the 70 were simply never installed */
				if ( is_file( $path ) ) {
					$failed[] = $filename;
				}

				if ( $role === 'R' ) {
					return [];
				}

				continue;
			}

			$faces[ $role ] = [
				'path' => $filename,
				'size' => $face['size'],
			];
		}

		return $faces;
	}
}
