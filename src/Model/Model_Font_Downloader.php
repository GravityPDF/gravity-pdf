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
	 * Failed files awaiting their one retry, and those that gave up, which the admin notice lists
	 *
	 * @since 6.19.0
	 */
	const FAILURES = 'gfpdf_core_font_failures';

	/**
	 * Prefix of the per-file transient holding when a download last failed, which skips the network until it expires
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
	 * Set once a Multisite network's shared fonts folder is pruned on the upgrade to 6.19
	 *
	 * @since 6.19.0
	 */
	const PRUNED = 'gfpdf_core_font_pruned';

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
	 * When each failed file's retry is due (else 0) and, once the retry also failed, when it gave up (else 0)
	 *
	 * @var array<string, array{retry_at: int, gave_up: int}>|null
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
	 * Download a file on the manifest into the fonts folder, skipped if it's there
	 *
	 * @since 6.19.0
	 */
	public function download( string $file ): bool {
		$manifest = $this->font_sources->get_manifest();

		if ( ! isset( $manifest[ $file ] ) ) {
			$this->log->error( 'Font not on the approved list', [ 'name' => $file ] );

			return false;
		}

		$font_path = $this->get_font_path( $file );
		if ( is_file( $font_path ) ) {
			return true;
		}

		if ( ! $this->is_auto_install_enabled( $file ) ) {
			return false;
		}

		if ( $this->is_backing_off( $file ) ) {
			return false;
		}

		if ( ! $this->save( $file, $font_path, $manifest[ $file ] ) ) {
			$this->record_failure( $file );

			return false;
		}

		$this->clear_failure( $file );

		return true;
	}

	/**
	 * Delete the unmodified files (fonts and licences) the Core Font installer downloaded that aren't kept. A file whose
	 * download is turned off stays, as it couldn't come back. Only files of the installer's size are hashed.
	 *
	 * @param string[]|null $keep File names to keep, else the fonts the site's PDFs need
	 *
	 * @return string[] The files removed
	 *
	 * @since 6.19.0
	 */
	public function prune( ?array $keep = null ): array {
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

			$path = $this->get_font_path( $file );
			clearstatcache( true, $path );
			if ( ! is_file( $path ) || filesize( $path ) !== $entry['size'] ) {
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
	 * Every font the Font Manager lists, by name: custom fonts, core fonts with files on disk and listed bundled fonts.
	 * `can_delete` is whether this user may delete the font when it's unused; get_font_usage() says what's in use.
	 *
	 * @return array<int, array{id: string, font_name: string, type: string, regular: string, italics: string, bold: string, bolditalics: string, can_delete: bool, auto_install?: bool}>
	 *
	 * @since 6.19.0
	 */
	public function get_installed_fonts(): array {
		$custom = $this->font_sources->get_custom_fonts();
		$labels = $this->get_font_labels();
		$fonts  = [];

		foreach ( $custom as $font ) {
			$font['type']       = 'custom';
			$font['can_delete'] = true;
			$fonts[]            = $font;
		}

		foreach ( $this->font_sources->get_sources() as $source ) {
			if ( empty( $source['manifest'] ) ) {
				foreach ( $this->font_sources->get_listed_families( $source ) as $key => $family ) {
					$fonts[] = $this->get_font_row( (string) $key, $labels[ $key ] ?? (string) $key, 'bundled', $family, false );
				}
			}
		}

		foreach ( array_diff_key( $this->get_downloaded_families(), $custom ) as $key => $family ) {
			$on_disk = array_filter(
				$family['files'],
				static function ( string $file ) use ( $family ): bool {
					return isset( $family['sizes'][ $file ] );
				}
			);

			$fonts[] = $this->get_font_row( (string) $key, $labels[ $key ] ?? (string) $key, 'core', $on_disk, $this->can_delete_core_fonts() ) + [ 'auto_install' => $this->can_download_all( $family['files'] ) ];
		}

		usort(
			$fonts,
			static function ( array $a, array $b ): int {
				return strcasecmp( $a['font_name'], $b['font_name'] );
			}
		);

		return $fonts;
	}

	/**
	 * Why each font in use can't be deleted, as phrases for the Font Manager. Walks every form, and on Multisite every
	 * site, so the list loads without it.
	 *
	 * @return array<string, string[]> Phrases by font key
	 *
	 * @since 6.19.0
	 */
	public function get_font_usage(): array {
		$custom  = $this->font_sources->get_custom_fonts();
		$locales = $this->font_sources->get_language_font_locales();
		$usage   = [];

		foreach ( $this->required_fonts->get_font_reasons( is_multisite() ) as $key => $reason ) {
			/* A site's custom fonts are its own, so other sites don't count */
			if ( isset( $custom[ $key ] ) ) {
				$reason['sites'] = 0;
			}

			$in_use = $this->get_in_use( $reason, $locales[ $key ] ?? [] );
			if ( count( $in_use ) > 0 ) {
				$usage[ $key ] = $in_use;
			}
		}

		return $usage;
	}

	/**
	 * Every site shares the fonts folder, so only a super admin deletes core fonts on Multisite
	 *
	 * @since 6.19.0
	 */
	public function can_delete_core_fonts(): bool {
		return ! is_multisite() || is_super_admin();
	}

	/**
	 * A source's font in the shape of a custom font record
	 *
	 * @param array $files File names by style (R, B, I, BI)
	 *
	 * @return array{id: string, font_name: string, type: string, regular: string, italics: string, bold: string, bolditalics: string, can_delete: bool}
	 *
	 * @since 6.19.0
	 */
	protected function get_font_row( string $key, string $label, string $type, array $files, bool $can_delete ): array {
		return [
			'id'          => $key,
			'font_name'   => $label,
			'type'        => $type,
			'regular'     => $files['R'] ?? '',
			'italics'     => $files['I'] ?? '',
			'bold'        => $files['B'] ?? '',
			'bolditalics' => $files['BI'] ?? '',
			'can_delete'  => $can_delete,
		];
	}

	/**
	 * Delete an unused downloaded family's files whatever their hash, keeping the licence files
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
		if ( $this->required_fonts->is_font_in_use( $key, is_multisite() ) ) {
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
	 * @return array<string, array{files: array<string, string>, sizes: array<string, int>}>
	 *
	 * @since 6.19.0
	 */
	protected function get_downloaded_families(): array {
		$families = [];

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
						'files' => $files,
						'sizes' => $sizes,
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
	 * A font's get_font_reasons() entry as phrases for the Font Manager
	 *
	 * @param array    $reasons A get_font_reasons() entry
	 * @param string[] $locales The locales it's a language font for
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_in_use( array $reasons, array $locales = [] ): array {
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

		/* Stored empty, so the notice check reads it from the autoloaded options rather than querying for it */
		if ( get_site_option( self::FAILURES ) === false ) {
			$this->save_failures( [] );
		}

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
		$left          = $this->download_within_budget( array_merge( $due, $missing ) );
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
	 * Have the sync run by the time given: queue it, or move one queued later forward. Never two, never later.
	 *
	 * @since 6.19.0
	 */
	protected function schedule_by( int $at ): void {
		$next = wp_next_scheduled( self::SYNC_EVENT );
		if ( $next !== false && $next <= $at ) {
			return;
		}

		if ( $next !== false ) {
			wp_unschedule_event( $next, self::SYNC_EVENT );
		}

		if ( wp_schedule_single_event( $at, self::SYNC_EVENT ) !== true ) {
			$this->log->warning( 'Font sync could not be scheduled' );
		}
	}

	/**
	 * Download each file until the time budget runs out
	 *
	 * @param string[] $files
	 *
	 * @return string[] The files it didn't reach
	 *
	 * @since 6.19.0
	 */
	protected function download_within_budget( array $files ): array {
		$files    = array_values( $files );
		$deadline = microtime( true ) + $this->sync_budget;

		foreach ( $files as $i => $file ) {
			/* At least one file per run, so slow hosts make progress */
			if ( $i > 0 && microtime( true ) >= $deadline ) {
				return array_slice( $files, $i );
			}

			$this->download( $file );
		}

		return [];
	}

	/**
	 * The files a paused run left, else the missing required files, less those that gave up
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
					return ! isset( $gave_up[ $file ] ) && ! $this->font_sources->is_downloaded( $file );
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
			} elseif ( $this->font_sources->is_downloaded( $file ) ) {
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
				'retry_at' => time() + wp_rand( 0, $this->retry_jitter ),
				'gave_up'  => 0,
			];

			$this->save_failures( $failures );

			/* The sync schedules once, when it's done */
			if ( ! $this->syncing ) {
				$this->schedule_by( $failures[ $file ]['retry_at'] );
			}

			return;
		}

		if ( $failure['gave_up'] === 0 ) {
			/* First give-up time, which notice dismissals are compared against */
			$failures[ $file ] = [
				'retry_at' => 0,
				'gave_up'  => time(),
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
	 * Network-wide like the fonts folder, and kept when empty
	 *
	 * @param array<string, array{retry_at: int, gave_up: int}> $failures
	 *
	 * @since 6.19.0
	 */
	protected function save_failures( array $failures ): void {
		$this->failures = $failures;

		/* Autoloaded on a single site, as the notice checks it on most admin pages */
		is_multisite() ? update_site_option( self::FAILURES, $failures ) : update_option( self::FAILURES, $failures, true );
	}

	/**
	 * Failures by file, read once per request
	 *
	 * @return array<string, array{retry_at: int, gave_up: int}>
	 *
	 * @since 6.19.0
	 */
	protected function get_failures(): array {
		if ( $this->failures === null ) {
			$this->failures = [];

			$stored = get_site_option( self::FAILURES, [] );
			foreach ( is_array( $stored ) ? $stored : [] as $file => $failure ) {
				$retry_at = is_array( $failure ) && is_int( $failure['retry_at'] ?? null ) ? $failure['retry_at'] : 0;
				$gave_up  = is_array( $failure ) && is_int( $failure['gave_up'] ?? null ) ? $failure['gave_up'] : 0;

				/* An entry with neither is waiting on nothing */
				if ( is_string( $file ) && ( $retry_at > 0 || $gave_up > 0 ) ) {
					$this->failures[ $file ] = [
						'retry_at' => $gave_up > 0 ? 0 : $retry_at,
						'gave_up'  => $gave_up,
					];
				}
			}
		}

		return $this->failures;
	}
}
