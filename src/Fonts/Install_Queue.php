<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Helper\Helper_Abstract_Queue;
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
 * The background work list for font installs
 *
 * A second `GF_Background_Process` beside the PDF queue, on its own action, so it gets its own cron event, process
 * lock and batch store — a font install can never delay a PDF, and the `background_processing` toggle's PDF-queue
 * flush never touches font work.
 *
 * One item per **file**, not per entry: `{ source, entry, name, install? }`. GF chunks between items, so its 60 s
 * process lock always covers a single `gfpdf_font_download_timeout`, a 17 MB CJK face cannot take a whole pack down
 * with it, and the item carries only ids — `Font_Installer::install_file()` re-resolves the URL, hash and size from
 * the catalog row at execution, which is what keeps "every URL is built from a registered root" true at fetch time
 * and makes a poisoned batch row inert.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Install_Queue extends Helper_Abstract_Queue {

	/**
	 * @var string
	 * @since 7.0
	 */
	protected $action = 'gravitypdf_fonts';

	/**
	 * GF persists the attempt count *before* running a task, so a process killed mid-download burns one
	 *
	 * This is the only thing standing between a file that reliably kills PHP — an OOM on a huge face — and an
	 * endless healthcheck loop, because an in-band failure never reaches here: it is recorded on the catalog row
	 * and the task returns normally.
	 *
	 * @since 7.0
	 */
	protected $supports_attempts = true;

	/**
	 * @since 7.0
	 */
	public const MAX_ATTEMPTS = 3;

	/**
	 * Roughly how long `run_inline()` may spend before handing the rest back to the batch
	 *
	 * The escape hatch runs in an admin request, so it yields rather than risking the host's execution limit.
	 *
	 * @since 7.0
	 */
	public const INLINE_BUDGET = 20;

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
	 * @var Font_Installer
	 * @since 7.0
	 */
	protected $installer;

	/**
	 * @var Registry
	 * @since 7.0
	 */
	protected $registry;

	public function __construct(
		Font_Repository $repository,
		Catalog_Repository $catalog,
		Font_Installer $installer,
		Registry $registry,
		LoggerInterface $log
	) {
		parent::__construct( $log );

		$this->repository = $repository;
		$this->catalog    = $catalog;
		$this->installer  = $installer;
		$this->registry   = $registry;

		add_filter( 'gform_max_async_task_attempts', [ $this, 'max_attempts' ], 10, 4 );
	}

	/**
	 * Raise GF's default of one attempt, for this process alone
	 *
	 * Scoped on `$identifier` because the filter is global: the PDF queue's tasks are re-runnable on very different
	 * terms and are not ours to change.
	 *
	 * @param int    $max        GF's default
	 * @param mixed  $task       The task about to run
	 * @param object $batch      The batch it belongs to
	 * @param string $identifier The process the task belongs to
	 *
	 * @return int
	 *
	 * @since 7.0
	 */
	public function max_attempts( $max, $task, $batch, $identifier ) {
		return $identifier === $this->get_identifier() ? static::MAX_ATTEMPTS : $max;
	}

	/**
	 * Queue one entry's outstanding files, once
	 *
	 * The dedup is `Catalog_Repository::claim()`'s conditional UPDATE and nothing else, so two triggers firing on
	 * the same request produce one batch without either taking a lock. Everything before the claim only decides
	 * whether there is anything left to ask for.
	 *
	 * @param array $request `{ entry: '{source}/{entry}', background: string[], install?: array }` — `inline` is
	 *                       trigger 3's own concurrent fetch and is deliberately not queued here
	 * @param bool  $manual  A Font Manager install: ignores the auto-install gate, `retry_after` and `removed`
	 *
	 * @since 7.0
	 */
	public function enqueue_once( array $request, bool $manual = false ): bool {
		if ( ! $manual && ! $this->registry->auto_install_enabled() ) {
			return false;
		}

		$parts  = explode( '/', (string) ( $request['entry'] ?? '' ), 2 );
		$source = $parts[0];
		$entry  = $parts[1] ?? '';

		if ( $source === '' || $entry === '' ) {
			return false;
		}

		$install = (array) ( $request['install'] ?? [] );
		$pending = $this->pending_files( $source, $entry, (array) ( $request['background'] ?? [] ), $install );

		if ( $pending === [] ) {
			return false;
		}

		if ( ! $this->catalog->claim( $source, $entry, $manual ) ) {
			return false;
		}

		foreach ( $pending as $name ) {
			$item = [
				'source' => $source,
				'entry'  => $entry,
				'name'   => $name,
			];

			if ( $install !== [] ) {
				$item['install'] = $install;
			}

			$this->push_to_queue( $item );
		}

		$this->flush();

		return true;
	}

	/**
	 * The files of this request that are not already on disk with a row
	 *
	 * Only for the entry's own install. A manual install carrying an `install` payload is a *further* install of a
	 * display entry under its own key, so its rows are disjoint from whatever is already there and none of its
	 * files may be dropped — the per-file skip in `Font_Installer::place()` is what makes the shared bytes free.
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function pending_files( string $source, string $entry, array $files, array $install ): array {
		$files = array_values( array_unique( array_map( 'strval', $files ) ) );

		if ( $install !== [] ) {
			return $files;
		}

		$claimed = $this->repository->claimed_filenames();
		$pending = [];

		foreach ( $files as $name ) {
			if ( isset( $claimed[ Font_Sources::install_path( $source, $entry, $name ) ] ) ) {
				continue;
			}

			$pending[] = $name;
		}

		return $pending;
	}

	/**
	 * Install one file
	 *
	 * **Always returns `false`.** Anything else makes GF keep the task *and pause the whole process* until the
	 * five-minute healthcheck resumes it, which would turn one unreachable file into a stalled queue. A failure is
	 * already recorded on the entry's catalog row with a backoff, and the hourly `maybe_retry()` is what brings it
	 * back — so dropping the task here loses nothing.
	 *
	 * @param array $item
	 *
	 * @return false
	 *
	 * @since 7.0
	 */
	protected function task( $item ) {
		$source = (string) ( $item['source'] ?? '' );
		$entry  = (string) ( $item['entry'] ?? '' );
		$name   = (string) ( $item['name'] ?? '' );

		if ( $source === '' || $entry === '' || $name === '' ) {
			$this->log->critical( 'Font install queue ran with an invalid item', [ 'item' => $item ] );

			return false;
		}

		$row = $this->catalog->entry( $source, $entry );

		/*
		 * Re-checked here rather than trusted from enqueue time: a delete or a source unregistration between the
		 * two drops the remaining work silently. Neither is a failure worth recording — the row would be reporting
		 * the admin's own change back at them, and `removed` is what stops a trigger resurrecting a deleted pack.
		 */
		if ( $row === null || (string) ( $row['phase'] ?? '' ) === 'removed' || ! $this->catalog->is_registered( $source ) ) {
			$this->log->notice(
				'Skipping a queued font install that is no longer wanted',
				[
					'entry' => $source . '/' . $entry,
					'file'  => $name,
				]
			);

			return false;
		}

		$this->installer->install_file( $source, $entry, $name, (array) ( $item['install'] ?? [] ) );

		return false;
	}

	/**
	 * Re-queue the coverage entries whose backoff has passed
	 *
	 * The hourly listener's job, and the only thing that ever retries a pack that failed on its own — a trigger
	 * fires once, so without this an `emoji` that failed during an origin blip would stay failed until something
	 * else happened to ask for it.
	 *
	 * @return int How many entries were re-queued
	 *
	 * @since 7.0
	 */
	public function maybe_retry(): int {
		$retried = 0;

		foreach ( $this->catalog->retryable_entries() as $candidate ) {
			$id    = $candidate['source'] . '/' . $candidate['entry'];
			$files = $this->installer->files_for( $id );

			if ( is_wp_error( $files ) || $files === [] ) {
				continue;
			}

			if ( $this->enqueue_once(
				[
					'entry'      => $id,
					'background' => $files,
				]
			) ) {
				++$retried;
			}
		}

		return $retried;
	}

	/**
	 * Run the outstanding batches in this request, within a budget
	 *
	 * The escape hatch behind the stalled-batch notice's "Run now", for a site whose cron is dead or whose loopback
	 * is blocked — the two ways a dispatched batch never gets picked up. It takes the same process lock the
	 * background handler does, so it can never run a task the cron is already running, and writes back whatever it
	 * did not reach so the batch survives to be dispatched again.
	 *
	 * @return int How many files it got through
	 *
	 * @since 7.0
	 */
	public function run_inline(): int {
		if ( $this->is_process_running() ) {
			return 0;
		}

		$this->lock_process();
		$done  = 0;
		$until = time() + static::INLINE_BUDGET;

		try {
			foreach ( $this->get_batches() as $batch ) {
				$remaining = (array) $batch->data;

				while ( $remaining !== [] ) {
					if ( time() >= $until ) {
						$this->update( $batch->key, $remaining );

						return $done;
					}

					$this->task( array_shift( $remaining ) );
					++$done;
				}

				$this->delete( $batch->key );
			}
		} finally {
			$this->unlock_process();
		}

		return $done;
	}
}
