<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Helper\Fonts\FlushCache;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Mpdf\On_Demand_Font_File_Finder;
use GFPDF\Statics\Git_Blob;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use WP_Error;

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
 * Downloads the font sources' files into the fonts folder, verifying each against its manifest
 *
 * @since 6.19.0
 */
class Model_Font_Downloader extends Helper_Abstract_Model {

	/**
	 * Failed files awaiting their one retry, and those that gave up, which the admin notice lists. An option of the
	 * main network, as every site shares the fonts folder
	 *
	 * @since 6.19.0
	 */
	const FAILURES = 'gfpdf_core_font_failures';

	/**
	 * Prefix of the per-file site transient holding when a download last failed, which skips the network until it
	 * expires
	 *
	 * @since 6.19.0
	 */
	const BACK_OFF_KEY = 'gfpdf_core_font_backoff_';

	/**
	 * @since 6.19.0
	 */
	const BACK_OFF = 15 * MINUTE_IN_SECONDS;

	/**
	 * Upper bound of the random delay before the retry
	 *
	 * @since 6.19.0
	 */
	const RETRY_JITTER = 5 * MINUTE_IN_SECONDS;

	/**
	 * Cron event that downloads the required fonts and runs the retries that are due
	 *
	 * @since 6.19.0
	 */
	const SYNC_EVENT = 'gfpdf_core_font_sync';

	/**
	 * The files a paused sync run left for the next one
	 *
	 * @since 6.19.0
	 */
	const SYNC_REMAINING = 'gfpdf_core_font_sync_remaining';

	/**
	 * Seconds a sync run keeps starting downloads
	 *
	 * @since 6.19.0
	 */
	const SYNC_BUDGET = 20;

	/**
	 * Main network option holding the 6.19 cleanup's progress while it runs, then true once it's done
	 *
	 * @since 6.19.0
	 */
	const CLEANUP = 'gfpdf_core_font_cleanup';

	/**
	 * Cron event that reads every site's fonts a batch at a time, then cleans the shared fonts folder
	 *
	 * @since 6.19.0
	 */
	const CLEANUP_EVENT = 'gfpdf_core_font_cleanup_run';

	/**
	 * @var Model_Font_Sources
	 * @since 6.19.0
	 */
	protected $font_sources;

	/**
	 * @var Helper_Data
	 * @since 6.19.0
	 */
	protected $data;

	/**
	 * @var Helper_Abstract_Options
	 * @since 6.19.0
	 */
	protected $options;

	/**
	 * @var Helper_Misc
	 * @since 6.19.0
	 */
	protected $misc;

	/**
	 * @var LoggerInterface
	 * @since 6.19.0
	 */
	protected $log;

	/**
	 * When each failed file's retry is due (else 0), once the retry also failed when it gave up (else 0), and whether
	 * an admin dismissed the notice listing it
	 *
	 * @var array<string, array{retry_at: int, gave_up: int, dismissed: bool}>|null
	 * @since 6.19.0
	 */
	protected $failures;

	/**
	 * @var Model_Required_Fonts
	 * @since 6.19.0
	 */
	protected $required_fonts;

	/**
	 * @var int|float
	 * @since 6.19.0
	 */
	protected $sync_budget = self::SYNC_BUDGET;

	/**
	 * @var int
	 * @since 6.19.0
	 */
	protected $retry_jitter = self::RETRY_JITTER;

	/**
	 * Set while the sync runs, which schedules its own next run once it's done
	 *
	 * @var bool
	 * @since 6.19.0
	 */
	protected $syncing = false;

	/**
	 * Build the downloader from the font sources, which list the files it may fetch, and the required fonts it syncs
	 *
	 * @since 6.19.0
	 */
	public function __construct( Model_Font_Sources $font_sources, Helper_Data $data, Helper_Abstract_Options $options, Helper_Misc $misc, LoggerInterface $log, Model_Required_Fonts $required_fonts ) {
		$this->font_sources   = $font_sources;
		$this->data           = $data;
		$this->options        = $options;
		$this->misc           = $misc;
		$this->log            = $log;
		$this->required_fonts = $required_fonts;
	}

	/**
	 * An mPDF font finder that downloads missing fonts
	 *
	 * @param array $config The filtered mPDF config
	 *
	 * @since 6.19.0
	 */
	public function get_font_file_finder( array $config ): On_Demand_Font_File_Finder {
		$fontdata     = is_array( $config['fontdata'] ?? null ) ? $config['fontdata'] : [];
		$default_font = is_string( $config['default_font'] ?? null ) ? strtolower( $config['default_font'] ) : '';
		$default_file = $fontdata[ $default_font ]['R'] ?? '';
		$font_dirs    = array_values( array_filter( (array) ( $config['fontDir'] ?? [] ), 'is_string' ) );

		/* Debug Mode only, not Debug::is_enabled(): a staging site should still render with a fallback */
		$fallback = $this->options->get_option( 'debug_mode', 'No' ) !== 'Yes';

		return new On_Demand_Font_File_Finder( $font_dirs, $this, is_string( $default_file ) ? $default_file : '', $fallback );
	}

	/**
	 * @since 6.19.0
	 */
	public function get_font_path( string $file ): string {
		return $this->font_sources->get_font_path( $file );
	}

	/**
	 * The bundled Arimo Regular path, or '' (logged) if it's missing
	 *
	 * @since 6.19.0
	 */
	public function get_fallback_file(): string {
		$file = $this->font_sources->get_fallback_file();
		if ( ! is_file( $file ) ) {
			$this->log->error( 'The bundled fallback font is missing; reinstall Gravity PDF', [ 'file' => $file ] );

			return '';
		}

		return $file;
	}

	/**
	 * @since 6.19.0
	 */
	public function is_core_font_file( string $file ): bool {
		return $this->font_sources->is_core_font_file( $file );
	}

	/**
	 * Whether fonts may be downloaded, e.g. not on hosts that block outbound requests: the constant, else the filter
	 *
	 * @since 6.19.0
	 */
	protected function is_auto_install_enabled( string $file ): bool {
		$locked = $this->get_auto_install_constant();
		if ( $locked !== null ) {
			return $locked;
		}

		/**
		 * Filter whether a font may be downloaded. The GPDF_AUTO_INSTALL_FONTS constant overrides it.
		 *
		 * @param bool   $download Whether to download the file
		 * @param string $file     The file name
		 *
		 * @since 6.19.0
		 */
		return (bool) apply_filters( 'gfpdf_auto_install_fonts', true, $file );
	}

	/**
	 * GPDF_AUTO_INSTALL_FONTS as a bool, or null when it isn't defined
	 *
	 * @since 6.19.0
	 */
	protected function get_auto_install_constant(): ?bool {
		return defined( 'GPDF_AUTO_INSTALL_FONTS' ) ? (bool) constant( 'GPDF_AUTO_INSTALL_FONTS' ) : null;
	}

	/**
	 * Whether the manifest's files may be downloaded: `On`, `Off (GPDF_AUTO_INSTALL_FONTS)`, or `Off` or `Partly off` with
	 * `(gfpdf_auto_install_fonts filter)`. It doesn't depend on which files are missing, as those change without the setting.
	 *
	 * @since 6.19.0
	 */
	public function get_auto_install_status(): string {
		$locked = $this->get_auto_install_constant();
		if ( $locked !== null ) {
			return $locked ? 'On' : 'Off (GPDF_AUTO_INSTALL_FONTS)';
		}

		$files   = array_keys( $this->font_sources->get_manifest() );
		$blocked = array_filter(
			$files,
			function ( $file ) {
				return ! $this->is_auto_install_enabled( (string) $file );
			}
		);

		if ( $blocked === [] ) {
			return 'On';
		}

		return sprintf( '%s (gfpdf_auto_install_fonts filter)', count( $blocked ) === count( $files ) ? 'Off' : 'Partly off' );
	}

	/**
	 * What stops the missing required files downloading, or null: the constant, else the filter for any of them
	 *
	 * @param string[] $missing The missing required files
	 *
	 * @since 6.19.0
	 */
	public function get_auto_install_blocker( array $missing ): ?string {
		$locked = $this->get_auto_install_constant();
		if ( $locked !== null ) {
			return $locked ? null : 'GPDF_AUTO_INSTALL_FONTS';
		}

		foreach ( $missing as $file ) {
			if ( ! $this->is_auto_install_enabled( $file ) ) {
				return 'gfpdf_auto_install_fonts filter';
			}
		}

		return null;
	}

	/**
	 * The required files whose own copy isn't in the fonts folder, so one a different font holds the name of counts
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_missing_files(): array {
		return array_values(
			array_filter(
				$this->required_fonts->get_required_font_files(),
				function ( $file ) {
					return ! $this->font_sources->is_installed( $file );
				}
			)
		);
	}

	/**
	 * Whether a Font Manager record of this site uses the file, so it isn't the core font's
	 *
	 * @since 6.19.0
	 */
	public function is_record_file( string $file ): bool {
		return $this->font_sources->is_record_file( $file );
	}

	/**
	 * Whether a path is in the fonts folder, as opposed to a bundled font's or one a filter added
	 *
	 * @since 6.19.0
	 */
	public function is_in_fonts_folder( string $path ): bool {
		return untrailingslashit( dirname( $path ) ) === untrailingslashit( $this->data->template_font_location );
	}

	/**
	 * Download a file on the manifest into the fonts folder, skipped if it's there. A file at its name that is another
	 * font's (see is_conflict()) is left alone, and the manifest's is saved under its hashed name instead.
	 *
	 * @param string $file   The manifest file name
	 * @param bool   $verify Hash a file of a known size too, to find one of another font's that is the same size
	 *
	 * @return bool Whether the font is on disk now. False when its download is off or failed, even if another font's
	 *              file sits at its name, which then stays in use.
	 *
	 * @since 6.19.0
	 */
	public function download( string $file, bool $verify = false ): bool {
		$manifest = $this->font_sources->get_manifest();

		if ( ! isset( $manifest[ $file ] ) ) {
			$this->log->error( 'Font not on the approved list', [ 'name' => $file ] );

			return false;
		}

		if ( $this->font_sources->has_hashed_copy( $file ) ) {
			return true;
		}

		$font_path = $this->font_sources->get_plain_path( $file );
		$conflict  = is_file( $font_path );
		if ( $conflict && ! $this->is_conflict( $file, $verify ) ) {
			return true;
		}

		if ( ! $this->is_auto_install_enabled( $file ) ) {
			return false;
		}

		if ( $this->is_backing_off( $file ) ) {
			return false;
		}

		$target = $conflict ? $this->font_sources->get_plain_path( $this->font_sources->get_hashed_name( $file ) ) : $font_path;
		if ( ! $this->save( $file, $target, $manifest[ $file ] ) ) {
			$this->record_failure( $file );

			return false;
		}

		$this->clear_failure( $file );

		if ( $conflict ) {
			$this->log->notice( 'Font saved under a new name as another font has its name', [ 'name' => $file ] );
			$this->remove_older_hashed_copies( $file, basename( $target ) );

			/* The metrics and PDFs cached from the file that was there are stale */
			FlushCache::flush();
		}

		return true;
	}

	/**
	 * A new release of a font has a new SHA-1, so its hashed name changes and the copy under the old one is orphaned.
	 * Delete the copies of the file under other hashed names, if the name holds the first 8 of the file's own SHA-1,
	 * which a font someone else put there would not.
	 *
	 * @param string $file The manifest file name
	 * @param string $keep The hashed name to keep
	 *
	 * @since 6.19.0
	 */
	protected function remove_older_hashed_copies( string $file, string $keep ): void {
		$info    = pathinfo( $file );
		$pattern = '/^' . preg_quote( $info['filename'], '/' ) . '-([0-9a-f]{8})' . preg_quote( isset( $info['extension'] ) ? '.' . $info['extension'] : '', '/' ) . '$/i';
		$dir     = $this->data->template_font_location;

		foreach ( scandir( $dir ) ?: [] as $name ) {
			if ( $name === $keep || preg_match( $pattern, $name, $match ) !== 1 ) {
				continue;
			}

			$size = (int) filesize( $dir . $name );
			if ( strpos( $this->hash_blob( $dir . $name, $size ), strtolower( $match[1] ) ) === 0 ) {
				wp_delete_file_from_directory( $dir . $name, $dir );
				$this->log->notice( 'Font copy for an older release removed', [ 'name' => $name ] );
			}
		}
	}

	/**
	 * Whether the file at a manifest name is another font's: its size is no known build's, else with $hash its SHA-1
	 * isn't either. A file that can't be read is left alone, as nothing shows it's wrong.
	 *
	 * @since 6.19.0
	 */
	protected function is_conflict( string $file, bool $hash ): bool {
		if ( $this->font_sources->is_size_conflict( $file ) ) {
			return true;
		}

		$hashed = $this->font_sources->get_hashed_name( $file );
		$path   = $this->font_sources->get_plain_path( $file );
		if ( ! $hash || $hashed === '' || $hashed === $file || ! is_file( $path ) ) {
			return false;
		}

		$size = (int) filesize( $path );
		$sha  = $this->hash_blob( $path, $size );

		return $sha !== '' && ! in_array( $sha, $this->font_sources->get_known_builds( $file )[ $size ] ?? [], true );
	}

	/**
	 * Detecting another font's file is the only reason to hash one that is already on disk
	 *
	 * @since 6.19.0
	 */
	protected function hash_blob( string $path, int $size ): string {
		return Git_Blob::sha1( $path, $size );
	}

	/**
	 * Whether the file's own copy is on disk, hashing the file at its name to be sure it's the manifest's
	 *
	 * @since 6.19.0
	 */
	protected function is_resolved( string $file ): bool {
		return $this->font_sources->has_hashed_copy( $file ) || ( is_file( $this->font_sources->get_plain_path( $file ) ) && ! $this->is_conflict( $file, true ) );
	}

	/**
	 * Delete the unmodified files (fonts and licences) the Core Font installer downloaded that aren't kept. A file whose
	 * download is turned off stays, as it couldn't come back. Only files of the installer's size are hashed.
	 *
	 * @param string[]|null $keep  File names to keep, else the fonts the site's PDFs need
	 * @param int           $since Also keep files written after this time, as a download since then may be needed
	 *
	 * @return string[] The files removed
	 *
	 * @since 6.19.0
	 */
	public function prune( ?array $keep = null, int $since = 0 ): array {
		$manifest = $this->font_sources->get_legacy_manifest( 'core' );

		/* Without the manifest nothing is known to be unmodified */
		if ( count( $manifest ) === 0 ) {
			$this->log->error( 'Core Font cleanup skipped as the font manifest could not be read' );

			return [];
		}

		$keep    = array_flip( $keep ?? $this->required_fonts->get_required_font_files() );
		$removed = [];
		$bytes   = 0;
		$offline = [];

		foreach ( $manifest as $file => $entry ) {
			$file = (string) $file;
			if ( isset( $keep[ $file ] ) ) {
				continue;
			}

			/* The file at the legacy name, never a hashed copy, which isn't the installer's */
			$path = $this->font_sources->get_plain_path( $file );
			clearstatcache( true, $path );
			if ( ! is_file( $path ) || filesize( $path ) !== $entry['size'] || ( $since > 0 && filemtime( $path ) > $since ) ) {
				continue;
			}

			if ( ! $this->is_auto_install_enabled( $file ) ) {
				$offline[] = $file;
				continue;
			}

			if ( ! $this->is_unmodified( $path, $entry ) ) {
				continue;
			}

			if ( ! wp_delete_file_from_directory( $path, $this->data->template_font_location ) ) {
				$this->log->warning( 'Core Font could not be removed', [ 'name' => $file ] );
				continue;
			}

			$removed[] = $file;
			$bytes    += $entry['size'];
		}

		if ( count( $offline ) > 0 ) {
			$this->log->notice( 'Core Font cleanup kept the fonts that downloads are turned off for', [ 'files' => $offline ] );
		}

		$this->log->notice(
			'Core Font cleanup complete',
			[
				'removed' => count( $removed ),
				'bytes'   => $bytes,
			]
		);

		return $removed;
	}

	/**
	 * Each downloadable font family with files in the fonts folder, with why it's in use (a reason disables Delete).
	 * Walks every form, and on Multisite every site, so it's only for the Font Manager.
	 *
	 * @return array<int, array{id: string, label: string, source: string, files: array, size: int, size_label: string, in_use: string[], auto_install: bool}>
	 *
	 * @since 6.19.0
	 */
	public function get_downloaded_fonts(): array {
		$families = $this->get_downloaded_families();
		if ( count( $families ) === 0 ) {
			return [];
		}

		$reasons = $this->required_fonts->get_font_reasons( is_multisite() );
		$labels  = $this->get_font_labels();
		$locales = $this->font_sources->get_language_font_locales();
		$fonts   = [];

		foreach ( $families as $key => $family ) {
			$files = [];
			foreach ( $family['files'] as $style => $file ) {
				if ( isset( $family['sizes'][ $file ] ) ) {
					$files[] = [
						'style' => $style,
						'file'  => $file,
						'size'  => $family['sizes'][ $file ],
					];
				}
			}

			$size    = array_sum( $family['sizes'] );
			$fonts[] = [
				'id'           => $key,
				'label'        => $labels[ $key ] ?? $key,
				'source'       => $family['source'],
				'files'        => $files,
				'size'         => $size,
				'size_label'   => (string) size_format( $size, 1 ),
				'in_use'       => $this->get_in_use( $key, $reasons, $locales[ $key ] ?? [] ),
				'auto_install' => $this->can_download_all( $family['files'] ),
			];
		}

		usort(
			$fonts,
			static function ( array $a, array $b ): int {
				return strcasecmp( $a['label'], $b['label'] );
			}
		);

		return $fonts;
	}

	/**
	 * Delete an unused downloaded family's files whatever their hash, keeping the licence files. A file with a copy under
	 * its hashed name loses only the copy: the file at the name is another font's.
	 *
	 * @return array{id: string, removed: string[], bytes: int}|WP_Error 404 when nothing is on disk, 409 when in use
	 *
	 * @since 6.19.0
	 */
	public function delete_downloaded_font( string $key ) {
		$families = $this->get_downloaded_families();
		if ( ! isset( $families[ $key ] ) ) {
			return new WP_Error( 'font_not_downloaded', __( 'The font is not downloaded.', 'gravity-pdf' ), [ 'status' => 404 ] );
		}

		$files = array_values( array_unique( $families[ $key ]['files'] ) );
		if ( count( $this->get_in_use( $key, $this->required_fonts->get_font_reasons( is_multisite() ) ) ) > 0 ) {
			return new WP_Error( 'font_in_use', __( 'The font is in use, so it would be downloaded again.', 'gravity-pdf' ), [ 'status' => 409 ] );
		}

		$removed = [];
		$bytes   = 0;
		foreach ( $files as $file ) {
			$this->clear_failure( $file );
			$this->delete_back_off( $file );

			$size = $families[ $key ]['sizes'][ $file ] ?? null;
			if ( $size === null ) {
				continue;
			}

			if ( ! wp_delete_file_from_directory( $this->get_font_path( $file ), $this->data->template_font_location ) ) {
				$this->log->warning( 'Downloaded font file could not be removed', [ 'name' => $file ] );
				continue;
			}

			$removed[] = $file;
			$bytes    += $size;
		}

		FlushCache::flush( $key );

		$this->log->notice(
			'Downloaded font deleted',
			[
				'font'  => $key,
				'files' => $removed,
				'bytes' => $bytes,
			]
		);

		return [
			'id'      => $key,
			'removed' => $removed,
			'bytes'   => $bytes,
		];
	}

	/**
	 * The downloadable sources' families with at least one file in the fonts folder: their files on the manifest by
	 * style (R, B, I, BI), and the sizes of those on disk
	 *
	 * @return array<string, array{source: string, files: array<string, string>, sizes: array<string, int>}>
	 *
	 * @since 6.19.0
	 */
	protected function get_downloaded_families(): array {
		$families = [];

		/* A Font Manager record's file at a family's name isn't the family's: its files have hashed names */
		$collisions = $this->font_sources->get_record_collisions();

		foreach ( $this->font_sources->get_sources() as $id => $source ) {
			if ( empty( $source['manifest'] ) ) {
				continue;
			}

			$manifest = $this->font_sources->get_source_manifest( (string) $id );
			foreach ( $source['families'] ?? [] as $key => $family ) {
				$key = (string) $key;
				if ( isset( $families[ $key ] ) || ! is_array( $family ) ) {
					continue;
				}

				$files = [];
				foreach ( [ 'R', 'B', 'I', 'BI' ] as $style ) {
					$file = $family[ $style ] ?? null;
					$file = is_string( $file ) ? $collisions[ $file ] ?? $file : null;
					if ( is_string( $file ) && isset( $manifest[ $file ] ) && $this->font_sources->is_core_font_file( $file ) ) {
						$files[ $style ] = $file;
					}
				}

				$sizes = [];
				foreach ( $files as $file ) {
					$path = $this->get_font_path( $file );
					if ( is_file( $path ) ) {
						$sizes[ $file ] = (int) filesize( $path );
					}
				}

				if ( count( $sizes ) > 0 ) {
					$families[ $key ] = [
						'source' => (string) $id,
						'files'  => $files,
						'sizes'  => $sizes,
					];
				}
			}
		}

		return $families;
	}

	/**
	 * Font list labels by key
	 *
	 * @return array<string, string>
	 *
	 * @since 6.19.0
	 */
	protected function get_font_labels(): array {
		$labels = [];
		foreach ( (array) $this->options->get_installed_fonts() as $group ) {
			foreach ( is_array( $group ) ? $group : [] as $key => $label ) {
				if ( is_string( $label ) && ! isset( $labels[ $key ] ) ) {
					$labels[ (string) $key ] = $label;
				}
			}
		}

		return $labels;
	}

	/**
	 * Why a family is in use, as phrases for the Font Manager; none means it can be deleted
	 *
	 * @param array<string, array> $reasons get_font_reasons()
	 * @param string[]             $locales The locales it's a language font for
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_in_use( string $key, array $reasons, array $locales = [] ): array {
		if ( ! isset( $reasons[ $key ] ) ) {
			return [];
		}

		$reasons = $reasons[ $key ];
		$phrases = [];

		if ( $reasons['default'] ) {
			$phrases[] = __( 'Default font', 'gravity-pdf' );
		}

		if ( $reasons['language'] ) {
			$names = array_map( [ $this, 'get_language_name' ], $locales );
			/* translators: %s: the site's languages, e.g. ไทย */
			$phrases[] = count( $names ) > 0 ? sprintf( __( 'Site language (%s)', 'gravity-pdf' ), implode( ', ', $names ) ) : __( 'Site language', 'gravity-pdf' );
		}

		if ( $reasons['pdfs'] > 0 ) {
			/* translators: %d: the number of PDFs */
			$phrases[] = sprintf( _n( 'Used by %d PDF', 'Used by %d PDFs', $reasons['pdfs'], 'gravity-pdf' ), $reasons['pdfs'] );
		}

		if ( $reasons['source'] ) {
			$phrases[] = __( 'Required by an add-on', 'gravity-pdf' );
		}

		if ( $reasons['sites'] > 0 ) {
			/* translators: %d: the number of sites */
			$phrases[] = sprintf( _n( 'Used on %d other site', 'Used on %d other sites', $reasons['sites'], 'gravity-pdf' ), $reasons['sites'] );
		}

		return $phrases;
	}

	/**
	 * A locale's native name when WordPress has the translations list cached, else the locale
	 *
	 * @since 6.19.0
	 */
	protected function get_language_name( string $locale ): string {
		$translations = get_site_transient( 'available_translations' );
		$name         = is_array( $translations ) ? ( $translations[ $locale ]['native_name'] ?? '' ) : '';

		return is_string( $name ) && $name !== '' ? $name : $locale;
	}

	/**
	 * Whether every file would download again if deleted
	 *
	 * @param string[] $files
	 *
	 * @since 6.19.0
	 */
	protected function can_download_all( array $files ): bool {
		foreach ( $files as $file ) {
			if ( ! $this->is_auto_install_enabled( $file ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array{url: string, size: int, sha: string} $entry
	 *
	 * @since 6.19.0
	 */
	protected function is_unmodified( string $path, array $entry ): bool {
		$hash = Git_Blob::sha1( $path, $entry['size'] );

		return $hash !== '' && hash_equals( $entry['sha'], $hash );
	}

	/**
	 * Files whose retry also failed, mapped to when they gave up
	 *
	 * @return array<string, int>
	 *
	 * @since 6.19.0
	 */
	public function get_failed_files(): array {
		$failed = [];
		foreach ( $this->get_failures() as $file => $failure ) {
			if ( $failure['gave_up'] > 0 ) {
				$failed[ $file ] = $failure['gave_up'];
			}
		}

		return $failed;
	}

	/**
	 * Failed files no admin has dismissed the notice for
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_undismissed_failed_files(): array {
		$undismissed = array_filter(
			$this->get_failures(),
			static function ( array $failure ): bool {
				return $failure['gave_up'] > 0 && ! $failure['dismissed'];
			}
		);

		return array_map( 'strval', array_keys( $undismissed ) );
	}

	/**
	 * Dismiss the notice for every failed file, on every site. A file that downloads, then fails again, is listed anew
	 *
	 * @since 6.19.0
	 */
	public function dismiss_failed_files(): void {
		$failures = $this->get_failures();
		foreach ( $failures as $file => $failure ) {
			if ( $failure['gave_up'] > 0 ) {
				$failures[ $file ]['dismissed'] = true;
			}
		}

		$this->save_failures( $failures );
	}

	/**
	 * Forget the failures read this request
	 *
	 * @since 6.19.0
	 */
	public function flush(): void {
		$this->failures = null;
	}

	/**
	 * The transient key of a file's back-off
	 *
	 * @since 6.19.0
	 */
	public static function back_off_key( string $file ): string {
		return self::BACK_OFF_KEY . md5( $file );
	}

	/**
	 * Queue a single background sync
	 *
	 * @since 6.19.0
	 */
	public function schedule_sync(): void {
		/* The trigger may have changed the required set */
		delete_transient( self::SYNC_REMAINING );

		$this->schedule_by( time() );
	}

	/**
	 * Run the retries that are due, then download the missing required files, rescheduling when the time budget runs
	 * out or a retry is still to come
	 *
	 * @since 6.19.0
	 */
	public function sync(): void {
		$due     = $this->get_retries()[0];
		$missing = array_diff( $this->get_sync_files(), $due );
		delete_transient( self::SYNC_REMAINING );

		/* The back-off would skip a file that failed minutes ago */
		array_map( [ $this, 'delete_back_off' ], $due );

		$this->syncing = true;
		$left          = $this->within_budget(
			array_merge( $due, $missing ),
			function ( $file ) {
				/* The one place a file of the right size is hashed, to find another font's of that size */
				$this->download( (string) $file, true );
			}
		);
		$this->syncing = false;

		/* Includes the retries this run's first failures added */
		[ $still_due, $next ] = $this->get_retries();

		if ( $left !== [] ) {
			$this->log->notice( 'Font sync paused until its next run', [ 'remaining' => count( $left ) ] );
			set_transient( self::SYNC_REMAINING, array_values( array_diff( $left, $due ) ), DAY_IN_SECONDS );
		}

		if ( $left !== [] || $still_due !== [] ) {
			$next = time();
		}

		if ( $next > 0 ) {
			$this->schedule_by( $next );
		}
	}

	/**
	 * Queue the 6.19 cleanup of the shared fonts folder, once per install
	 *
	 * @param string $unset_default The default font of a site with none saved
	 *
	 * @since 6.19.0
	 */
	public function schedule_cleanup( string $unset_default ): void {
		if ( get_network_option( get_main_network_id(), self::CLEANUP ) !== false ) {
			return;
		}

		update_network_option(
			get_main_network_id(),
			self::CLEANUP,
			[
				'started' => time(),
				'default' => $unset_default,
				'sites'   => $this->required_fonts->get_site_ids(),
				'keys'    => [],
			]
		);

		$this->schedule_by( time(), self::CLEANUP_EVENT );
	}

	/**
	 * Read the fonts of the sites left until the time budget runs out, then clean the fonts folder once all are read
	 *
	 * @since 6.19.0
	 */
	public function cleanup(): void {
		$state = get_network_option( get_main_network_id(), self::CLEANUP );
		if ( ! is_array( $state ) ) {
			return;
		}

		$keys = $state['keys'];
		$left = $this->within_budget(
			$state['sites'],
			function ( $site_id ) use ( &$keys, $state ) {
				$keys = array_merge( $keys, $this->required_fonts->get_site_font_keys( (int) $site_id, $state['default'] ) );
			}
		);

		if ( $left !== [] ) {
			$state['sites'] = $left;
			$state['keys']  = array_values( array_unique( $keys ) );
			update_network_option( get_main_network_id(), self::CLEANUP, $state );
			$this->schedule_by( time(), self::CLEANUP_EVENT );

			return;
		}

		$this->prune( $this->required_fonts->get_install_font_files( $keys ), $state['started'] );
		update_network_option( get_main_network_id(), self::CLEANUP, true );
	}

	/**
	 * Have the event run by the time given: queue it, or move one queued later forward. Never two, never later.
	 *
	 * @since 6.19.0
	 */
	protected function schedule_by( int $at, string $event = self::SYNC_EVENT ): void {
		$next = wp_next_scheduled( $event );
		if ( $next !== false && $next <= $at ) {
			return;
		}

		if ( $next !== false ) {
			wp_unschedule_event( $next, $event );
		}

		if ( wp_schedule_single_event( $at, $event ) !== true ) {
			$this->log->warning( 'Font event could not be scheduled', [ 'event' => $event ] );
		}
	}

	/**
	 * Run the callback on each item until the time budget runs out
	 *
	 * @param mixed[] $items
	 *
	 * @return mixed[] The items it didn't reach
	 *
	 * @since 6.19.0
	 */
	protected function within_budget( array $items, callable $each ): array {
		$items    = array_values( $items );
		$deadline = microtime( true ) + $this->sync_budget;

		foreach ( $items as $i => $item ) {
			/* At least one per run, so slow hosts make progress */
			if ( $i > 0 && microtime( true ) >= $deadline ) {
				return array_slice( $items, $i );
			}

			$each( $item );
		}

		return [];
	}

	/**
	 * The files a paused run left, else the required files, less those that gave up and those with a copy under their
	 * hashed name. A file already on disk is in the list so sync() can check it's the manifest's.
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_sync_files(): array {
		$remaining = get_transient( self::SYNC_REMAINING );
		$files     = is_array( $remaining ) ? array_filter( $remaining, 'is_string' ) : $this->required_fonts->get_required_font_files();
		$gave_up   = $this->get_failed_files();

		return array_values(
			array_filter(
				$files,
				function ( $file ) use ( $gave_up ) {
					return ! isset( $gave_up[ $file ] ) && ! $this->font_sources->has_hashed_copy( $file );
				}
			)
		);
	}

	/**
	 * The files whose one retry is due, and when the next one still to come is (or 0). A due file already on disk,
	 * copied in by hand, no longer needs its retry.
	 *
	 * @return array{0: string[], 1: int}
	 *
	 * @since 6.19.0
	 */
	protected function get_retries(): array {
		$due  = [];
		$next = 0;

		foreach ( $this->get_failures() as $file => $failure ) {
			$file = (string) $file;
			if ( $failure['gave_up'] > 0 ) {
				continue;
			}

			if ( $failure['retry_at'] > time() ) {
				$next = $next === 0 ? $failure['retry_at'] : min( $next, $failure['retry_at'] );
			} elseif ( $this->is_resolved( $file ) ) {
				$this->clear_failure( $file );
			} else {
				$due[] = $file;
			}
		}

		return [ $due, $next ];
	}

	/**
	 * Stream to a tmp file, verify it against the manifest, then move it into place
	 *
	 * @param string                                     $file
	 * @param string                                     $font_path
	 * @param array{url: string, size: int, sha: string} $entry
	 *
	 * @since 6.19.0
	 */
	protected function save( string $file, string $font_path, array $entry ): bool {
		foreach ( [ $this->data->template_font_location, $this->data->template_tmp_location ] as $dir ) {
			if ( ! $this->misc->create_folder( $dir ) ) {
				return false;
			}

			if ( ! wp_is_writable( $dir ) ) {
				$this->log->error( 'Font folder is not writable', [ 'dir' => $dir ] );

				return false;
			}
		}

		/* A front-end PDF request hasn't loaded wp_tempnam() */
		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$download_path = wp_tempnam( $file, $this->data->template_tmp_location );
		if ( $download_path === '' || ! is_file( $download_path ) ) {
			$this->log->error( 'Font temporary file could not be created', [ 'name' => $file ] );

			return false;
		}

		$res = wp_remote_get(
			$entry['url'],
			[
				'timeout'  => 60,
				'stream'   => true,
				'filename' => $download_path,
			]
		);

		if ( is_wp_error( $res ) ) {
			return $this->fail(
				$download_path,
				'Font Download Failed',
				[
					'name'             => $file,
					'WP_Error_Message' => $res->get_error_message(),
					'WP_Error_Code'    => $res->get_error_code(),
				]
			);
		}

		$res_code = wp_remote_retrieve_response_code( $res );
		if ( $res_code !== 200 ) {
			return $this->fail(
				$download_path,
				'Font API Response Failed',
				[
					'name'          => $file,
					'response_code' => $res_code,
				]
			);
		}

		if ( ! Git_Blob::matches( $download_path, $entry['size'], $entry['sha'] ) ) {
			return $this->fail(
				$download_path,
				'Font Verification Failed',
				[
					'name' => $file,
					'size' => $entry['size'],
					'sha'  => $entry['sha'],
				]
			);
		}

		if ( ! $this->move( $download_path, $font_path ) ) {
			/* A move across filesystems copies, so a failure can leave a partial font behind */
			if ( is_file( $font_path ) && ! Git_Blob::matches( $font_path, $entry['size'], $entry['sha'] ) ) {
				wp_delete_file( $font_path );
			}

			return $this->fail( $download_path, 'Font Move Failed', [ 'name' => $file ] );
		}

		$this->log->notice( 'Font Downloaded', [ 'name' => $file ] );

		return true;
	}

	/**
	 * @since 6.19.0
	 */
	protected function move( string $from, string $to ): bool {
		return @rename( $from, $to ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
	}

	/**
	 * @since 6.19.0
	 */
	protected function fail( string $download_path, string $message, array $context ): bool {
		wp_delete_file( $download_path );
		$this->log->error( $message, $context );

		return false;
	}

	/**
	 * @since 6.19.0
	 */
	protected function is_backing_off( string $file ): bool {
		/* The transient expires with the back-off, so any value means the file failed recently */
		if ( get_site_transient( self::back_off_key( $file ) ) === false ) {
			return false;
		}

		$this->log->warning( 'Font download skipped after a recent failure', [ 'name' => $file ] );

		return true;
	}

	/**
	 * Back off, then: the first failure schedules one retry; the retry's failure gives up and raises the admin notice
	 *
	 * @since 6.19.0
	 */
	protected function record_failure( string $file ): void {
		set_site_transient( self::back_off_key( $file ), time(), self::BACK_OFF );

		$failures = $this->get_failures();
		$failure  = $failures[ $file ] ?? null;

		if ( $failure === null ) {
			$failures[ $file ] = [
				'retry_at'  => time() + wp_rand( 0, $this->retry_jitter ),
				'gave_up'   => 0,
				'dismissed' => false,
			];

			$this->save_failures( $failures );

			/* The sync schedules once, when it's done */
			if ( ! $this->syncing ) {
				$this->schedule_by( $failures[ $file ]['retry_at'] );
			}

			return;
		}

		if ( $failure['gave_up'] === 0 ) {
			/* The first give-up, which the notice lists */
			$failures[ $file ] = [
				'retry_at'  => 0,
				'gave_up'   => time(),
				'dismissed' => false,
			];

			$this->save_failures( $failures );
			$this->log->error( 'Font download failed again, and won\'t be retried automatically', [ 'name' => $file ] );
		}
	}

	/**
	 * Forget a file's failure and back-off, as after it downloads or turns up on disk
	 *
	 * @since 6.19.0
	 */
	protected function clear_failure( string $file ): void {
		$failures = $this->get_failures();
		if ( isset( $failures[ $file ] ) ) {
			$this->delete_back_off( $file );
			unset( $failures[ $file ] );
			$this->save_failures( $failures );
		}
	}

	/**
	 * Let a file be downloaded again before its back-off expires
	 *
	 * @since 6.19.0
	 */
	protected function delete_back_off( string $file ): void {
		delete_site_transient( self::back_off_key( $file ) );
	}

	/**
	 * @param array<string, array{retry_at: int, gave_up: int, dismissed: bool}> $failures
	 *
	 * @since 6.19.0
	 */
	protected function save_failures( array $failures ): void {
		$this->failures = $failures;

		update_network_option( get_main_network_id(), self::FAILURES, $failures );
	}

	/**
	 * Failures by file, read once per request
	 *
	 * @return array<string, array{retry_at: int, gave_up: int, dismissed: bool}>
	 *
	 * @since 6.19.0
	 */
	protected function get_failures(): array {
		if ( $this->failures === null ) {
			$this->failures = [];

			$stored = get_network_option( get_main_network_id(), self::FAILURES, [] );
			foreach ( is_array( $stored ) ? $stored : [] as $file => $failure ) {
				$retry_at = is_array( $failure ) && is_int( $failure['retry_at'] ?? null ) ? $failure['retry_at'] : 0;
				$gave_up  = is_array( $failure ) && is_int( $failure['gave_up'] ?? null ) ? $failure['gave_up'] : 0;

				/* An entry with neither is waiting on nothing */
				if ( is_string( $file ) && ( $retry_at > 0 || $gave_up > 0 ) ) {
					$this->failures[ $file ] = [
						'retry_at'  => $gave_up > 0 ? 0 : $retry_at,
						'gave_up'   => $gave_up,
						'dismissed' => $gave_up > 0 && ( $failure['dismissed'] ?? false ) === true,
					];
				}
			}
		}

		return $this->failures;
	}
}
