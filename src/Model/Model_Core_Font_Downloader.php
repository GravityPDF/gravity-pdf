<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Mpdf\On_Demand_Font_File_Finder;
use GFPDF\Statics\Git_Blob;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use GFFormsModel;

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
class Model_Core_Font_Downloader extends Helper_Abstract_Model {

	/**
	 * Failed downloads by file; a recent one skips the network until the back-off ends
	 *
	 * @since 6.19.0
	 */
	const FAILURES = 'gfpdf_core_font_failures';

	/**
	 * @since 6.19.0
	 */
	const BACK_OFF = 15 * MINUTE_IN_SECONDS;

	/**
	 * Cron event that retries the downloads that failed once
	 *
	 * @since 6.19.0
	 */
	const RETRY_EVENT = 'gfpdf_retry_core_font_downloads';

	/**
	 * Upper bound of the random delay before the retry
	 *
	 * @since 6.19.0
	 */
	const RETRY_JITTER = 5 * MINUTE_IN_SECONDS;

	/**
	 * Cron event that downloads the required fonts
	 *
	 * @since 6.19.0
	 */
	const SYNC_EVENT = 'gfpdf_download_core_fonts';

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
	 * @var array<string, array{url: string, size: int, sha: string}>|null
	 * @since 6.19.0
	 */
	protected $manifest;

	/**
	 * A file's last failure and, once its retry also failed, when it gave up (else 0)
	 *
	 * @var array<string, array{time: int, gave_up: int}>|null
	 * @since 6.19.0
	 */
	protected $failures;

	/**
	 * @var string[]|null
	 * @since 6.19.0
	 */
	protected $selected_font_keys;

	/**
	 * @var float
	 * @since 6.19.0
	 */
	protected $sync_budget = self::SYNC_BUDGET;

	/**
	 * @var int
	 * @since 6.19.0
	 */
	protected $retry_jitter = self::RETRY_JITTER;

	/**
	 * Set while the retry runs, so the back-off doesn't skip the files it retries
	 *
	 * @var bool
	 * @since 6.19.0
	 */
	protected $retrying = false;

	/**
	 * @since 6.19.0
	 */
	public function __construct( Model_Font_Sources $font_sources, Helper_Data $data, Helper_Abstract_Options $options, Helper_Misc $misc, LoggerInterface $log ) {
		$this->font_sources = $font_sources;
		$this->data         = $data;
		$this->options      = $options;
		$this->misc         = $misc;
		$this->log          = $log;
	}

	/**
	 * An mPDF font finder that downloads missing core fonts
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

		return new On_Demand_Font_File_Finder( $font_dirs, $this, is_string( $default_file ) ? $default_file : '' );
	}

	/**
	 * @since 6.19.0
	 */
	public function get_font_path( string $file ): string {
		return $this->data->template_font_location . $file;
	}

	/**
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
	public function is_debug_mode(): bool {
		return $this->options->get_option( 'debug_mode', 'No' ) === 'Yes';
	}

	/**
	 * Downloadable files across all sources, keyed by file name
	 *
	 * @return array<string, array{url: string, size: int, sha: string}>
	 *
	 * @since 6.19.0
	 */
	public function get_manifest(): array {
		if ( $this->manifest === null ) {
			$this->manifest = [];
			foreach ( $this->font_sources->get_sources() as $source ) {
				$this->manifest += $this->font_sources->read_manifest( (string) ( $source['manifest'] ?? '' ) );
			}
		}

		return $this->manifest;
	}

	/**
	 * Whether a file is a font on the manifest, not one of its licence files
	 *
	 * @since 6.19.0
	 */
	public function is_core_font_file( string $file ): bool {
		return isset( $this->get_manifest()[ $file ] ) && in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), [ 'ttf', 'otf' ], true );
	}

	/**
	 * Download a file on the manifest into the fonts folder, skipped if it's there unless forced
	 *
	 * @param bool $force Replace a copy already in the fonts folder
	 *
	 * @since 6.19.0
	 */
	public function download( string $file, bool $force = false ): bool {
		$font_path = $this->get_font_path( $file );
		$manifest  = $this->get_manifest();

		if ( ! isset( $manifest[ $file ] ) ) {
			$this->log->error( 'Core Font not on the approved list', [ 'name' => $file ] );

			return false;
		}

		if ( ! $force && is_file( $font_path ) ) {
			return true;
		}

		/**
		 * Filter whether a core font may be downloaded, e.g. on hosts that block outbound requests
		 *
		 * @param bool   $download Whether to download the file
		 * @param string $file     The file name
		 *
		 * @since 6.19.0
		 */
		if ( ! apply_filters( 'gfpdf_core_font_download', true, $file ) ) {
			return false;
		}

		if ( ! $this->retrying && $this->is_backing_off( $file ) ) {
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
	 * Retry each file that has failed once, rescheduling when the time budget runs out
	 *
	 * @since 6.19.0
	 */
	public function retry_failed(): void {
		$files = array_keys(
			array_filter(
				$this->get_failures(),
				static function ( array $failure ): bool {
					return $failure['gave_up'] === 0;
				}
			)
		);

		/* The back-off would skip a file that failed minutes ago */
		$this->retrying = true;
		try {
			$left = $this->each_within_budget( $files, [ $this, 'download' ] );
		} finally {
			$this->retrying = false;
		}

		if ( $left !== [] ) {
			$this->schedule_once( self::RETRY_EVENT, time(), 'Core Font download retry could not be scheduled' );
		}
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
	 * Forget the failures and font selections read this request
	 *
	 * @since 6.19.0
	 */
	public function flush(): void {
		$this->failures           = null;
		$this->selected_font_keys = null;
	}

	/**
	 * A font key's files on the core manifest (none for bundled and custom fonts)
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_font_files( string $key ): array {
		$family = $this->font_sources->get_font_data()[ $this->normalise_font( $key ) ] ?? null;

		$files = [];
		foreach ( [ 'R', 'B', 'I', 'BI' ] as $style ) {
			$file = is_array( $family ) ? ( $family[ $style ] ?? '' ) : '';
			if ( is_string( $file ) && $file !== '' && $this->is_core_font_file( $file ) ) {
				$files[] = $file;
			}
		}

		return array_values( array_unique( $files ) );
	}

	/**
	 * Files that should be on disk: default, language, required-source and PDF-selected fonts
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_required_font_files(): array {
		$keys = array_merge(
			[ $this->normalise_font( $this->options->get_option( 'default_font', '' ) ) ],
			$this->font_sources->get_language_font_keys(),
			$this->get_selected_font_keys()
		);

		$files = [];
		foreach ( array_unique( $keys ) as $key ) {
			$files = array_merge( $files, $this->get_font_files( $key ) );
		}

		foreach ( $this->font_sources->get_sources() as $source ) {
			if ( ! empty( $source['required'] ) ) {
				$files = array_merge( $files, array_keys( $this->font_sources->read_manifest( (string) ( $source['manifest'] ?? '' ) ) ) );
			}
		}

		return array_values( array_unique( $files ) );
	}

	/**
	 * The registered fonts among a PDF's settings, so template font pickers count too
	 *
	 * @param array $pdf PDF settings
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_pdf_fonts( array $pdf ): array {
		$fontdata = $this->font_sources->get_font_data();
		$fonts    = [];

		foreach ( $pdf as $value ) {
			$font = $this->normalise_font( $value );
			if ( $font !== '' && isset( $fontdata[ $font ] ) ) {
				$fonts[] = $font;
			}
		}

		return array_values( array_unique( $fonts ) );
	}

	/**
	 * Whether any font a PDF's settings name has downloadable files missing
	 *
	 * @param array $pdf PDF settings
	 *
	 * @since 6.19.0
	 */
	public function pdf_needs_download( array $pdf ): bool {
		foreach ( $this->get_pdf_fonts( $pdf ) as $font ) {
			if ( $this->needs_download( $font ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether any of a font's downloadable files is missing from the fonts folder
	 *
	 * @param mixed $font A font key
	 *
	 * @since 6.19.0
	 */
	public function needs_download( $font ): bool {
		foreach ( $this->get_font_files( $this->normalise_font( $font ) ) as $file ) {
			if ( ! $this->is_downloaded( $file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Installed required font files per downloadable source, and whether its manifest could be read
	 *
	 * @return array<string, array{installed: int, total: int, manifest: bool}>
	 *
	 * @since 6.19.0
	 */
	public function get_installed_counts(): array {
		$required = $this->get_required_font_files();
		$counts   = [];

		foreach ( $this->font_sources->get_sources() as $id => $source ) {
			if ( empty( $source['manifest'] ) ) {
				continue;
			}

			$manifest = $this->font_sources->read_manifest( (string) $source['manifest'] );
			$files    = array_intersect( $required, array_keys( $manifest ) );

			$counts[ (string) $id ] = [
				'installed' => count( array_filter( $files, [ $this, 'is_downloaded' ] ) ),
				'total'     => count( $files ),
				'manifest'  => count( $manifest ) > 0,
			];
		}

		return $counts;
	}

	/**
	 * Queue a single background sync
	 *
	 * @since 6.19.0
	 */
	public function schedule_sync(): void {
		/* The trigger may have changed the required set */
		delete_transient( self::SYNC_REMAINING );

		$this->queue_sync();
	}

	/**
	 * Download the missing required files, rescheduling when the time budget runs out
	 *
	 * @since 6.19.0
	 */
	public function sync(): void {
		$missing = $this->get_sync_files();
		delete_transient( self::SYNC_REMAINING );

		$remaining = $this->each_within_budget( $missing, [ $this, 'download' ] );
		if ( $remaining !== [] ) {
			$this->log->notice( 'Core Font sync paused until its next run', [ 'remaining' => count( $remaining ) ] );

			set_transient( self::SYNC_REMAINING, $remaining, DAY_IN_SECONDS );
			$this->queue_sync();
		}
	}

	/**
	 * @since 6.19.0
	 */
	protected function queue_sync(): void {
		$this->schedule_once( self::SYNC_EVENT, time(), 'Core Font sync could not be scheduled' );
	}

	/**
	 * Schedule a single event unless one is queued, logging the warning if WordPress refuses it
	 *
	 * @since 6.19.0
	 */
	protected function schedule_once( string $event, int $at, string $warning ): void {
		if ( wp_next_scheduled( $event ) === false && wp_schedule_single_event( $at, $event ) !== true ) {
			$this->log->warning( $warning );
		}
	}

	/**
	 * Run $action on each file until the time budget runs out
	 *
	 * @param string[] $files
	 *
	 * @return string[] The files it didn't reach
	 *
	 * @since 6.19.0
	 */
	protected function each_within_budget( array $files, callable $action ): array {
		$files    = array_values( $files );
		$deadline = microtime( true ) + $this->sync_budget;

		foreach ( $files as $i => $file ) {
			/* At least one file per run, so slow hosts make progress */
			if ( $i > 0 && microtime( true ) >= $deadline ) {
				return array_slice( $files, $i );
			}

			$action( $file );
		}

		return [];
	}

	/**
	 * The files a paused run left, else the missing required files
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_sync_files(): array {
		$remaining = get_transient( self::SYNC_REMAINING );
		$files     = is_array( $remaining ) ? array_filter( $remaining, 'is_string' ) : $this->get_required_font_files();

		return array_values(
			array_filter(
				$files,
				function ( $file ) {
					return ! $this->is_downloaded( $file );
				}
			)
		);
	}

	/**
	 * @since 6.19.0
	 */
	protected function is_downloaded( string $file ): bool {
		return is_file( $this->get_font_path( $file ) );
	}

	/**
	 * @param mixed $font
	 *
	 * @since 6.19.0
	 */
	protected function normalise_font( $font ): string {
		return is_string( $font ) ? strtolower( trim( $font ) ) : '';
	}

	/**
	 * The fonts selected in every form's PDFs
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_selected_font_keys(): array {
		if ( $this->selected_font_keys === null ) {
			$keys     = [];
			$form_ids = GFFormsModel::get_form_ids( true, false );

			/* One query for every active form */
			foreach ( count( $form_ids ) > 0 ? GFFormsModel::get_form_meta_by_id( $form_ids ) : [] as $form ) {
				$pdfs = is_array( $form ) && is_array( $form['gfpdf_form_settings'] ?? null ) ? $form['gfpdf_form_settings'] : [];
				foreach ( $pdfs as $pdf ) {
					if ( is_array( $pdf ) ) {
						$keys = array_merge( $keys, $this->get_pdf_fonts( $pdf ) );
					}
				}
			}

			$this->selected_font_keys = array_values( array_unique( $keys ) );
		}

		return $this->selected_font_keys;
	}

	/**
	 * Stream to a tmp file, verify it against the manifest, then move it into place
	 *
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
				$this->log->error( 'Core Font folder is not writable', [ 'dir' => $dir ] );

				return false;
			}
		}

		$download_path = wp_tempnam( $file, $this->data->template_tmp_location );
		if ( $download_path === '' || ! is_file( $download_path ) ) {
			$this->log->error( 'Core Font temporary file could not be created', [ 'name' => $file ] );

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
				'Core Font Download Failed',
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
				'Core Font API Response Failed',
				[
					'name'          => $file,
					'response_code' => $res_code,
				]
			);
		}

		if ( ! Git_Blob::matches( $download_path, $entry['size'], $entry['sha'] ) ) {
			return $this->fail(
				$download_path,
				'Core Font Verification Failed',
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

			return $this->fail( $download_path, 'Core Font Move Failed', [ 'name' => $file ] );
		}

		$this->log->notice( 'Core Font Downloaded', [ 'name' => $file ] );

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
		$failure = $this->get_failures()[ $file ] ?? null;
		if ( $failure === null || $failure['time'] <= time() - self::BACK_OFF ) {
			return false;
		}

		$this->log->warning( 'Core Font download skipped after a recent failure', [ 'name' => $file ] );

		return true;
	}

	/**
	 * The first failure schedules one retry; the retry's failure gives up and raises the admin notice
	 *
	 * @since 6.19.0
	 */
	protected function record_failure( string $file ): void {
		$failures = $this->get_failures();
		$gave_up  = $failures[ $file ]['gave_up'] ?? null;

		if ( $gave_up === 0 ) {
			/* First give-up time, which notice dismissals are compared against */
			$gave_up = time();
			$this->log->error( 'Core Font download failed again, and won\'t be retried automatically', [ 'name' => $file ] );
		}

		$failures[ $file ] = [
			'time'    => time(),
			'gave_up' => $gave_up ?? 0,
		];

		$this->save_failures( $failures );

		if ( $gave_up === null ) {
			$this->schedule_retry();
		}
	}

	/**
	 * @since 6.19.0
	 */
	protected function clear_failure( string $file ): void {
		$failures = $this->get_failures();
		if ( isset( $failures[ $file ] ) ) {
			unset( $failures[ $file ] );
			$this->save_failures( $failures );
		}
	}

	/**
	 * Network-wide like the fonts folder; a single site's isn't autoloaded
	 *
	 * @param array<string, array{time: int, gave_up: int}> $failures
	 *
	 * @since 6.19.0
	 */
	protected function save_failures( array $failures ): void {
		$this->failures = $failures;

		if ( $failures === [] ) {
			is_multisite() ? delete_site_option( self::FAILURES ) : delete_option( self::FAILURES );
		} elseif ( is_multisite() ) {
			update_site_option( self::FAILURES, $failures );
		} else {
			update_option( self::FAILURES, $failures, false );
		}
	}

	/**
	 * One retry a random few minutes out, so sites don't retry in step
	 *
	 * @since 6.19.0
	 */
	protected function schedule_retry(): void {
		$this->schedule_once( self::RETRY_EVENT, time() + wp_rand( 0, $this->retry_jitter ), 'Core Font download retry could not be scheduled' );
	}

	/**
	 * Failures by file, read once per request
	 *
	 * @return array<string, array{time: int, gave_up: int}>
	 *
	 * @since 6.19.0
	 */
	protected function get_failures(): array {
		if ( $this->failures === null ) {
			$this->failures = [];

			$stored = is_multisite() ? get_site_option( self::FAILURES, [] ) : get_option( self::FAILURES, [] );
			foreach ( is_array( $stored ) ? $stored : [] as $file => $failure ) {
				if ( ! is_string( $file ) || ! is_array( $failure ) || ! is_int( $failure['time'] ?? null ) ) {
					continue;
				}

				$this->failures[ $file ] = [
					'time'    => $failure['time'],
					'gave_up' => is_int( $failure['gave_up'] ?? null ) ? $failure['gave_up'] : 0,
				];
			}
		}

		return $this->failures;
	}
}
