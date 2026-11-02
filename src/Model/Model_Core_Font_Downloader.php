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
	 * Set while the sync runs, which schedules its own next run once it's done
	 *
	 * @var bool
	 * @since 6.19.0
	 */
	protected $syncing = false;

	/**
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
		return $this->font_sources->get_font_path( $file );
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
		return $this->font_sources->get_manifest();
	}

	/**
	 * @since 6.19.0
	 */
	public function is_core_font_file( string $file ): bool {
		return $this->font_sources->is_core_font_file( $file );
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
			$this->log->notice( 'Core Font sync paused until its next run', [ 'remaining' => count( $left ) ] );
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
			$this->log->warning( 'Core Font sync could not be scheduled' );
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
	 * The files a paused run left, else the missing required files
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_sync_files(): array {
		$remaining = get_transient( self::SYNC_REMAINING );
		$files     = is_array( $remaining ) ? array_filter( $remaining, 'is_string' ) : $this->get_required_files();

		return array_values(
			array_filter(
				$files,
				function ( $file ) {
					return ! $this->font_sources->is_downloaded( $file );
				}
			)
		);
	}

	/**
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_required_files(): array {
		return $this->required_fonts->get_required_font_files();
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
		/* The transient expires with the back-off, so any value means the file failed recently */
		if ( $this->get_network_transient( self::back_off_key( $file ) ) === false ) {
			return false;
		}

		$this->log->warning( 'Core Font download skipped after a recent failure', [ 'name' => $file ] );

		return true;
	}

	/**
	 * Back off, then: the first failure schedules one retry; the retry's failure gives up and raises the admin notice
	 *
	 * @since 6.19.0
	 */
	protected function record_failure( string $file ): void {
		$this->set_network_transient( self::back_off_key( $file ), time(), self::BACK_OFF );

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
			$this->log->error( 'Core Font download failed again, and won\'t be retried automatically', [ 'name' => $file ] );
		}
	}

	/**
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
	 * @since 6.19.0
	 */
	protected function delete_back_off( string $file ): void {
		$this->delete_network_transient( self::back_off_key( $file ) );
	}

	/**
	 * @return mixed
	 *
	 * @since 6.19.0
	 */
	private function get_network_transient( string $key ) {
		return is_multisite() ? get_site_transient( $key ) : get_transient( $key );
	}

	/**
	 * @param mixed $value
	 *
	 * @since 6.19.0
	 */
	private function set_network_transient( string $key, $value, int $expiration ): void {
		is_multisite() ? set_site_transient( $key, $value, $expiration ) : set_transient( $key, $value, $expiration );
	}

	/**
	 * @since 6.19.0
	 */
	private function delete_network_transient( string $key ): void {
		is_multisite() ? delete_site_transient( $key ) : delete_transient( $key );
	}

	/**
	 * Network-wide like the fonts folder; a single site's isn't autoloaded
	 *
	 * @param array<string, array{retry_at: int, gave_up: int}> $failures
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
	 * Failures by file, read once per request
	 *
	 * @return array<string, array{retry_at: int, gave_up: int}>
	 *
	 * @since 6.19.0
	 */
	protected function get_failures(): array {
		if ( $this->failures === null ) {
			$this->failures = [];

			$stored = is_multisite() ? get_site_option( self::FAILURES, [] ) : get_option( self::FAILURES, [] );
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
