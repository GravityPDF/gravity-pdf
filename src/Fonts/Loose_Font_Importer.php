<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF_Vendor\GravityPdf\Upload\Exception as UploadException;
use GFPDF_Vendor\GravityPdf\Upload\FileInfo;
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
 * Turns fonts dropped into the uploads fonts directory into font rows, once
 *
 * 6.x registered every unclaimed `*.ttf` in that directory on every render, under a key derived from the filename,
 * invisible to the Font Manager. 7.0 removes that glob from the render path, so this runs in its place — one pass,
 * from `ensure_ready()`, after which the directory is never scanned by this code again.
 *
 * It reads no catalogue and needs no network, which is what lets it run during an offline upgrade.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Loose_Font_Importer implements Font_Population_Pass {

	/**
	 * @var Font_Repository
	 * @since 7.0
	 */
	protected $repository;

	/**
	 * @var SupportsOtl
	 * @since 7.0
	 */
	protected $supports_otl;

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

	public function __construct( Font_Repository $repository, SupportsOtl $supports_otl, LoggerInterface $log, string $font_dir ) {
		$this->repository   = $repository;
		$this->supports_otl = $supports_otl;
		$this->log          = $log;
		$this->font_dir     = trailingslashit( $font_dir );
	}

	/**
	 * Import every loose font file that nothing else claims
	 *
	 * A bad file is skipped and logged rather than thrown, so one unreadable font cannot stop the pass — in 6.x
	 * such a file fatally broke the first render that selected it.
	 *
	 * @return int How many rows were created
	 *
	 * @since 7.0
	 */
	public function run(): int {
		$created = 0;

		foreach ( $this->candidates() as $filename ) {
			if ( ! $this->is_valid_font( $filename ) ) {
				continue;
			}

			$font_key = $this->repository->unique_key( $this->derive_key( $filename ) );

			$row_id = $this->repository->insert(
				[
					'font_key'    => $font_key,
					'label'       => $this->derive_label( $filename, $font_key ),
					'source'      => 'imported',
					'blog_id'     => $this->repository->current_blog_id(),
					'use_otl'     => $this->supports_otl->supports_otl( $filename ) ? 0xFF : 0,
					'use_kashida' => 0,
					'files'       => [
						'R' => [
							'path' => $filename,
							'size' => (int) filesize( $this->font_dir . $filename ),
						],
					],
				]
			);

			if ( $row_id > 0 ) {
				++$created;
			}
		}

		if ( $created > 0 ) {
			$this->log->notice( 'Imported loose font files into the font tables', [ 'created' => $created ] );
		}

		return $created;
	}

	/**
	 * Files in the fonts directory that no row already records
	 *
	 * `.otf` was never picked up by 6.x's glob and still isn't. Files named by any site's `custom_fonts` snapshot
	 * are excluded too: those belong to a migration, past or future, so one site's importer cannot swallow
	 * another's not-yet-migrated faces as stem-keyed loose rows. So are the core font installer's own filenames,
	 * which `Legacy_Font_Adopter` owns — one that failed its hash check is left alone rather than imported under a
	 * filename key, because a file that is not what the installer wrote is not a font this plugin put there.
	 *
	 * @return string[] Filenames, relative to the fonts directory
	 *
	 * @since 7.0
	 */
	public function candidates(): array {
		$files = glob( $this->font_dir . '*.[tT][tT][fF]', GLOB_NOSORT );
		$files = is_array( $files ) ? $files : [];

		$claimed = $this->claimed_filenames();

		$candidates = [];
		foreach ( $files as $file ) {
			$filename = basename( $file );

			if ( ! isset( $claimed[ $filename ] ) ) {
				$candidates[] = $filename;
			}
		}

		sort( $candidates );

		return $candidates;
	}

	/**
	 * Every filename that already belongs to something
	 *
	 * @return array<string, true>
	 *
	 * @since 7.0
	 */
	protected function claimed_filenames(): array {
		$claimed = $this->repository->claimed_filenames();

		foreach ( $this->snapshot_filenames() as $filename ) {
			$claimed[ $filename ] = true;
		}

		return $claimed + Legacy_Installer_Files::filenames();
	}

	/**
	 * Filenames named by any site's frozen 6.x `custom_fonts` snapshot
	 *
	 * The option is kept forever, so one loop over the sites builds the exclusion set and cross-site activation
	 * ordering stops mattering.
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function snapshot_filenames(): array {
		$filenames = [];

		$blog_ids = is_multisite() ? get_sites(
			[
				'fields' => 'ids',
				'number' => 0,
			]
		) : [ 0 ];

		foreach ( $blog_ids as $blog_id ) {
			if ( is_multisite() ) {
				switch_to_blog( (int) $blog_id );
			}

			$settings = get_option( 'gfpdf_settings', [] );
			$fonts    = is_array( $settings ) ? ( $settings['custom_fonts'] ?? [] ) : [];

			foreach ( (array) $fonts as $font ) {
				foreach ( Font_Repository::LEGACY_FACE_ROLES as $face => $role ) {
					if ( ! empty( $font[ $face ] ) ) {
						$filenames[] = basename( (string) $font[ $face ] );
					}
				}
			}

			if ( is_multisite() ) {
				restore_current_blog();
			}
		}

		return array_values( array_unique( $filenames ) );
	}

	/**
	 * @since 7.0
	 */
	protected function is_valid_font( string $filename ): bool {
		try {
			( new TtfFontValidation() )->validate( new FileInfo( $this->font_dir . $filename, $filename ) );

			return true;
		} catch ( UploadException $e ) {
			$this->log->warning(
				'Skipping an unreadable font file in the fonts directory',
				[
					'file'      => $filename,
					'exception' => $e->getMessage(),
				]
			);
		} catch ( \Throwable $e ) {
			$this->log->warning(
				'Skipping a font file that could not be parsed',
				[
					'file'      => $filename,
					'exception' => $e->getMessage(),
				]
			);
		}

		return false;
	}

	/**
	 * The key a template's `font-family` will name
	 *
	 * The legacy rule first — lowercase, spaces removed — so a template written against 6.x keeps resolving. A stem
	 * that still isn't a valid key (a dot, non-ASCII) collapses to `-`, and both spellings are logged, because a
	 * template naming the old one needs a one-word edit.
	 *
	 * @since 7.0
	 */
	public function derive_key( string $filename ): string {
		$stem   = (string) preg_replace( '/\.[tT][tT][fF]$/', '', $filename );
		$legacy = mb_strtolower( str_replace( ' ', '', $stem ), 'UTF-8' );

		if ( preg_match( Font_Repository::KEY_PATTERN, $legacy ) ) {
			return $legacy;
		}

		$collapsed = strtolower( (string) preg_replace( '/[^A-Za-z0-9_\-]+/', '-', $legacy ) );
		$collapsed = trim( $collapsed, '-' );

		$this->log->notice(
			'A loose font file needs a different key than 6.x gave it',
			[
				'previous' => $legacy,
				'current'  => $collapsed,
			]
		);

		return $collapsed !== '' ? $collapsed : 'font';
	}

	/**
	 * A human name from the filename: `Roboto-Regular.ttf` → "Roboto Regular"
	 *
	 * Always satisfies `check_font_name_valid()`, falling back to the key when nothing survives.
	 *
	 * @since 7.0
	 */
	public function derive_label( string $filename, string $font_key ): string {
		$stem  = (string) preg_replace( '/\.[tT][tT][fF]$/', '', $filename );
		$label = str_replace( [ '-', '_', '.' ], ' ', $stem );
		$label = (string) preg_replace( '/[^A-Za-z0-9 ]+/', '', $label );
		$label = trim( (string) preg_replace( '/\s+/', ' ', $label ) );

		return $label !== '' ? $label : $font_key;
	}
}
