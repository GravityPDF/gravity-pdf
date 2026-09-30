<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Statics\Cache;
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
 * Purges cached PDFs and sweeps expired ones from the PDF cache in the background
 *
 * @since 7.0
 */
class Model_Pdf_Cache extends Helper_Abstract_Model {

	/**
	 * @since 7.0
	 */
	const SWEEP_STATE_OPTION = 'gfpdf_cache_sweep_state';

	/**
	 * Kept apart from the sweep state, which a running slice overwrites
	 *
	 * @since 7.0
	 */
	const PURGE_REQUEST_OPTION = 'gfpdf_cache_purge_requested';

	/**
	 * @var LoggerInterface
	 *
	 * @since 7.0
	 */
	protected $log;

	/**
	 * @var Helper_Data
	 *
	 * @since 7.0
	 */
	protected $data;

	/**
	 * @var Helper_Misc
	 *
	 * @since 7.0
	 */
	protected $misc;

	/**
	 * @param LoggerInterface $log
	 * @param Helper_Data     $data
	 * @param Helper_Misc     $misc
	 *
	 * @since 7.0
	 */
	public function __construct( LoggerInterface $log, Helper_Data $data, Helper_Misc $misc ) {
		$this->log  = $log;
		$this->data = $data;
		$this->misc = $misc;
	}

	/**
	 * Delete every cached PDF for an entry on the current site
	 *
	 * @param int $entry_id
	 *
	 * @return bool False when the PDFs couldn't be deleted
	 *
	 * @since 7.0
	 */
	public function purge_entry( $entry_id ) {
		$dir = Cache::get_entry_dir( $entry_id );
		if ( ! is_dir( $dir ) ) {
			return true;
		}

		return $this->misc->rmdir( $dir ) === true;
	}

	/**
	 * Stop serving everything cached on the current site, and delete it in the background
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function purge_all() {
		Cache::bump_generation();
		$this->request_purge();
	}

	/**
	 * Delete a network site's cached and one-off PDFs when the site is deleted
	 *
	 * @param \WP_Site $site
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function delete_site( $site ) {
		$dir = $this->data->template_tmp_location . (int) $site->id . '/';
		if ( is_dir( $dir ) ) {
			$this->misc->rmdir( $dir );
		}
	}

	/**
	 * Delete the current site's cached PDFs in the background: every PDF saved before now, not only expired ones
	 *
	 * Supersedes a sweep pass already under way. Bump the generation first, so nothing is served from the cache while
	 * the files are deleted.
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function request_purge() {
		update_option( static::PURGE_REQUEST_OPTION, sprintf( '%.6F', microtime( true ) ), false );

		/* WP won't add an event within 10 minutes of a queued one, so pull a queued slice forward instead */
		$next = wp_next_scheduled( 'gfpdf_cache_sweep' );
		if ( ! $next || $next > time() ) {
			wp_clear_scheduled_hook( 'gfpdf_cache_sweep' );
			wp_schedule_single_event( time(), 'gfpdf_cache_sweep' );
		}

		if ( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) {
			spawn_cron();
		}
	}

	/**
	 * Delete the cached PDFs in the background when the Cache PDFs setting is turned off
	 *
	 * Also hooked to `add_option_gfpdf_settings`, which passes the option name in place of the old settings
	 *
	 * @param array|string $old_settings
	 * @param array        $new_settings
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function maybe_request_purge( $old_settings, $new_settings ) {
		/* The add hook's option name reads as on */
		if ( Cache::is_enabled_by_setting( (array) $old_settings ) && ! Cache::is_enabled_by_setting( (array) $new_settings ) ) {
			$this->request_purge();
		}
	}

	/**
	 * The current site's sweep progress, and the results of its last complete pass
	 *
	 * @return array{mode: string, cursor: string, pass_started: int, pass: array, last_slice_at: int, last_complete_at: int, last_pass: array, fallback: bool, purge_request: string}
	 *               `pass` and `last_pass` hold files_reaped, bytes_reaped, files_left and bytes_left, and `last_pass`
	 *               also the pass's mode and start time (empty before the first complete pass)
	 *
	 * @since 7.0
	 */
	public function get_sweep_state() {
		$saved = get_option( static::SWEEP_STATE_OPTION, [] );
		$saved = is_array( $saved ) ? $saved : [];

		$last_pass = is_array( $saved['last_pass'] ?? null ) && ! empty( $saved['last_pass'] ) ? $saved['last_pass'] : null;

		$state = [
			'mode'             => ( $saved['mode'] ?? '' ) === 'purge' ? 'purge' : 'expire',
			/* Only ever compared with strcmp(), never used as a path */
			'cursor'           => is_string( $saved['cursor'] ?? null ) ? $saved['cursor'] : '',
			'pass_started'     => (int) ( $saved['pass_started'] ?? 0 ),
			'pass'             => $this->get_sweep_stats( $saved['pass'] ?? [] ),
			'last_slice_at'    => (int) ( $saved['last_slice_at'] ?? 0 ),
			'last_complete_at' => (int) ( $saved['last_complete_at'] ?? 0 ),
			'last_pass'        => $last_pass === null ? [] : [
				'mode'    => ( $last_pass['mode'] ?? '' ) === 'purge' ? 'purge' : 'expire',
				'started' => (int) ( $last_pass['started'] ?? 0 ),
			] + $this->get_sweep_stats( $last_pass ),
			'fallback'         => ! empty( $saved['fallback'] ),
			'purge_request'    => (string) ( $saved['purge_request'] ?? '' ),
		];

		/* A purge requested since this state was saved replaces the pass under way */
		$purge_request = (string) get_option( static::PURGE_REQUEST_OPTION, '' );
		if ( $purge_request !== '' && $purge_request !== $state['purge_request'] ) {
			$state['mode']          = 'purge';
			$state['cursor']        = '';
			$state['pass_started']  = (int) $purge_request;
			$state['pass']          = $this->get_sweep_stats();
			$state['purge_request'] = $purge_request;
		}

		return $state;
	}

	/**
	 * Whether the current site's cache holds any entry's PDFs. Reads the cache root only, never the whole tree.
	 *
	 * @return bool
	 *
	 * @since 7.0
	 */
	public function has_cached_pdfs() {
		$dir = @opendir( Cache::get_root() ); //phpcs:ignore
		if ( $dir === false ) {
			return false;
		}

		try {
			$name = readdir( $dir );
			while ( $name !== false ) {
				if ( preg_match( '/^e\d+$/', $name ) ) {
					return true;
				}

				$name = readdir( $dir );
			}

			return false;
		} finally {
			closedir( $dir );
		}
	}

	/**
	 * @param array $stats
	 *
	 * @return array{files_reaped: int, bytes_reaped: int, files_left: int, bytes_left: int}
	 *
	 * @since 7.0
	 */
	protected function get_sweep_stats( $stats = [] ) {
		$stats = is_array( $stats ) ? $stats : [];

		return [
			'files_reaped' => (int) ( $stats['files_reaped'] ?? 0 ),
			'bytes_reaped' => (int) ( $stats['bytes_reaped'] ?? 0 ),
			'files_left'   => (int) ( $stats['files_left'] ?? 0 ),
			'bytes_left'   => (int) ( $stats['bytes_left'] ?? 0 ),
		];
	}

	/**
	 * Run one sweep slice for the current site. Hooked to `gfpdf_cache_sweep`.
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function run_sweep_slice() {
		$this->sweep_site( $this->get_sweep_deadline(), false );
	}

	/**
	 * Run the hourly sweep slice. A network's main site also sweeps the sites whose own cron hasn't run for a day.
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function run_scheduled_sweep() {
		$deadline = $this->get_sweep_deadline();

		$this->sweep_site( $deadline, false );

		if ( is_multisite() && is_main_site() ) {
			$this->sweep_neglected_sites( $deadline );
		}
	}

	/**
	 * @return float
	 *
	 * @since 7.0
	 */
	protected function get_sweep_deadline() {
		/**
		 * How long one sweep slice can run for, in seconds. At least 1.
		 *
		 * @param int $seconds
		 *
		 * @since 7.0
		 */
		return microtime( true ) + max( 1, (int) apply_filters( 'gfpdf_cache_sweep_time_limit', 15 ) );
	}

	/**
	 * Run a sweep slice for the current site and save its progress. A slice cut short queues the next in 5 minutes.
	 *
	 * @param float $deadline
	 * @param bool  $fallback Whether the main site is sweeping it for a site whose cron isn't running
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	protected function sweep_site( $deadline, $fallback ) {
		$state = $this->get_sweep_state();

		/* Nothing to do on a site that has never cached a PDF */
		if ( ! $state['pass_started'] && ! is_dir( Cache::get_root() ) ) {
			return;
		}

		$swept = $this->sweep( $state, $deadline );
		if ( $swept === null ) {
			return;
		}

		$swept['fallback'] = $fallback;

		update_option( static::SWEEP_STATE_OPTION, $swept, false );

		if ( ! $fallback && $swept['pass_started'] && ! wp_next_scheduled( 'gfpdf_cache_sweep' ) ) {
			wp_schedule_single_event( time() + 5 * MINUTE_IN_SECONDS, 'gfpdf_cache_sweep' );
		}
	}

	/**
	 * Sweep each site in the network whose own cron hasn't run a slice for a day, e.g. `DISABLE_WP_CRON` with a system
	 * cron that only runs the main site. Once taken over, a site is swept from here until its own cron runs.
	 *
	 * @param float $deadline
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	protected function sweep_neglected_sites( $deadline ) {
		$tmp = $this->data->template_tmp_location;

		foreach ( @scandir( $tmp ) ?: [] as $name ) { //phpcs:ignore
			if ( $this->is_past( $deadline ) ) {
				return;
			}

			$blog_id = ctype_digit( (string) $name ) ? (int) $name : 0;
			if ( $blog_id === 0 || $blog_id === get_current_blog_id() || ! is_dir( $tmp . $name . '/cache' ) ) {
				continue;
			}

			/* Missed by delete_site(), e.g. deleted while Gravity PDF was inactive */
			if ( ! get_site( $blog_id ) ) {
				$this->misc->rmdir( $tmp . $name . '/cache' );
				continue;
			}

			switch_to_blog( $blog_id );

			try {
				$state = $this->get_sweep_state();
				if ( $state['fallback'] || $state['last_slice_at'] < time() - DAY_IN_SECONDS ) {
					$this->sweep_site( $deadline, true );
				}
			} finally {
				restore_current_blog();
			}
		}
	}

	/**
	 * Sweep the current site's cache until the walk finishes or the deadline passes
	 *
	 * Reaps PDFs past the TTL plus a grace period (in purge mode, also every PDF saved before the purge was requested),
	 * `.tmp` files abandoned for 5 minutes, unheld locks unused for 10, and directories holding only index.html. The
	 * walk goes in strcmp() order and the cursor records the last file reached, so the next slice resumes past it even
	 * when that file is gone.
	 *
	 * @param array $state    From get_sweep_state()
	 * @param float $deadline A microtime( true ) timestamp
	 *
	 * @return array|null The new state, or null when another sweep of this site holds the lock
	 *
	 * @since 7.0
	 */
	public function sweep( $state, $deadline ) {
		$root = Cache::get_root();

		if ( ! $state['pass_started'] ) {
			$state['pass_started'] = time();
			$state['cursor']       = '';
			$state['pass']         = $this->get_sweep_stats();
		}

		$gc_lock = is_dir( $root ) ? @fopen( $root . 'gc.lock', 'c' ) : null; //phpcs:ignore
		if ( $gc_lock === false ) {
			$this->log->warning( 'Could not open the PDF cache sweep lock', [ 'file' => $root . 'gc.lock' ] );

			return null;
		}

		/* A filesystem that can't lock (anything but contention) is swept without one */
		$wouldblock = 0;
		if ( $gc_lock !== null && ! $this->try_lock( $gc_lock, $wouldblock ) && $wouldblock ) {
			fclose( $gc_lock );

			return null;
		}

		try {
			$now = time();

			/**
			 * How long past the cache TTL a PDF is kept on disk, in seconds, so a path handed out just before it expired
			 * can still be attached or streamed
			 *
			 * @param int $grace
			 *
			 * @since 7.0
			 */
			$expired_before = $now - Cache::get_ttl() - max( 0, (int) apply_filters( 'gfpdf_cache_sweep_grace', HOUR_IN_SECONDS ) );

			$sweep = [
				'deadline'       => $deadline,
				'now'            => $now,
				/* A purge leaves the PDFs saved since it was requested, which the new generation's keys point to */
				'expired_before' => $state['mode'] === 'purge' ? max( $expired_before, $state['pass_started'] ) : $expired_before,
			];

			$cut = $gc_lock !== null && $this->sweep_dir( $root, '', $state, $sweep );

			$state['last_slice_at'] = time();

			if ( ! $cut ) {
				$state['last_pass']        = [
					'mode'    => $state['mode'],
					'started' => $state['pass_started'],
				] + $state['pass'];
				$state['last_complete_at'] = time();
				$state['mode']             = 'expire';
				$state['cursor']           = '';
				$state['pass_started']     = 0;
				$state['pass']             = $this->get_sweep_stats();
			}

			return $state;
		} finally {
			Cache::unlock( $gc_lock );
		}
	}

	/**
	 * @param string $dir      Absolute path, with a trailing slash
	 * @param string $relative $dir relative to the cache root
	 * @param array  $state
	 * @param array  $sweep
	 *
	 * @return bool True when the deadline cut the walk short
	 *
	 * @since 7.0
	 */
	protected function sweep_dir( $dir, $relative, &$state, $sweep ) {
		/* Read first, so this walk's own deletions don't make the directory look new */
		$mtime = (int) @filemtime( $dir ); //phpcs:ignore

		foreach ( $this->get_sweep_children( $dir ) as $name ) {
			$path   = $relative . $name;
			$is_dir = substr( $name, -1 ) === '/';

			/* Everything up to the cursor was walked by an earlier slice of this pass */
			if ( $state['cursor'] !== '' && strcmp( $path, $state['cursor'] ) <= 0 && ! ( $is_dir && strpos( $state['cursor'], $path ) === 0 ) ) {
				continue;
			}

			if ( $is_dir ) {
				if ( $this->sweep_dir( $dir . $name, $path, $state, $sweep ) ) {
					return true;
				}

				continue;
			}

			if ( $this->is_past( $sweep['deadline'] ) ) {
				return true;
			}

			$this->sweep_file( $dir . $name, $path, $state, $sweep );
			$state['cursor'] = $path;
		}

		/* A render may be about to write into a new directory */
		if (
			! in_array( $relative, [ '', 'locks/' ], true ) &&
			$mtime < $sweep['now'] - 5 * MINUTE_IN_SECONDS &&
			array_diff( (array) @scandir( $dir ), [ '.', '..', 'index.html' ] ) === [] //phpcs:ignore
		) {
			@unlink( $dir . 'index.html' ); //phpcs:ignore
			@rmdir( $dir ); //phpcs:ignore
		}

		return false;
	}

	/**
	 * @param float $deadline A microtime( true ) timestamp
	 *
	 * @return bool
	 *
	 * @since 7.0
	 */
	protected function is_past( $deadline ) {
		return microtime( true ) >= $deadline;
	}

	/**
	 * @param string $file
	 * @param string $relative $file relative to the cache root
	 * @param array  $state
	 * @param array  $sweep
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	protected function sweep_file( $file, $relative, &$state, $sweep ) {
		$name = basename( $file );
		if ( $name === 'index.html' || $relative === 'gc.lock' ) {
			return;
		}

		$mtime = @filemtime( $file ); //phpcs:ignore
		$size  = (int) @filesize( $file ); //phpcs:ignore
		if ( $mtime === false ) {
			return;
		}

		if ( strpos( $relative, 'locks/' ) === 0 ) {
			if ( $mtime < $sweep['now'] - 10 * MINUTE_IN_SECONDS ) {
				$this->reap_lock( $file );
			}

			return;
		}

		/* A .tmp file is a PDF being written, unless it has been abandoned */
		$reap   = substr( $name, -4 ) === '.tmp' ? $mtime < $sweep['now'] - 5 * MINUTE_IN_SECONDS : $mtime < $sweep['expired_before'];
		$result = $reap && @unlink( $file ) ? 'reaped' : 'left'; //phpcs:ignore

		++$state['pass'][ 'files_' . $result ];
		$state['pass'][ 'bytes_' . $result ] += $size;
	}

	/**
	 * Delete a lock file nobody holds. A held lock is never deleted, or the next request would lock a new file while
	 * the holder is still rendering.
	 *
	 * @param string $file
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	protected function reap_lock( $file ) {
		$fp = @fopen( $file, 'r' ); //phpcs:ignore
		if ( $fp === false ) {
			return;
		}

		/* On a filesystem that can't lock, lock() never holds one */
		$wouldblock = 0;
		if ( $this->try_lock( $fp, $wouldblock ) || ! $wouldblock ) {
			@unlink( $file ); //phpcs:ignore
		}

		Cache::unlock( $fp );
	}

	/**
	 * @param resource $fp
	 * @param int      $wouldblock Set to 1 when another process holds the lock
	 *
	 * @return bool
	 *
	 * @since 7.0
	 */
	protected function try_lock( $fp, &$wouldblock ) {
		return flock( $fp, LOCK_EX | LOCK_NB, $wouldblock );
	}

	/**
	 * A directory's children in strcmp() order. Directories get a trailing slash, so each sorts where its contents do.
	 *
	 * @param string $dir
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function get_sweep_children( $dir ) {
		$children = [];

		foreach ( @scandir( $dir, SCANDIR_SORT_NONE ) ?: [] as $name ) { //phpcs:ignore
			if ( $name === '.' || $name === '..' ) {
				continue;
			}

			/* A link is never followed out of the tree */
			$children[] = is_dir( $dir . $name ) && ! is_link( $dir . $name ) ? $name . '/' : (string) $name;
		}

		sort( $children, SORT_STRING );

		return $children;
	}
}
