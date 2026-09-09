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
	 * The largest file trigger 3 will fetch inside the render request (12 MiB)
	 *
	 * Measured against the pack table: at this size every CJK and Korean Regular face gets an embedded first
	 * render and only the Plane 2 supplements fall to the background.
	 *
	 * @since 7.0
	 */
	public const INLINE_CAP = 12582912;

	/**
	 * How long an entry may sit mid-install before the site is told its queue is not moving
	 *
	 * Well past a slow pack: every file refreshes `phase_since` as it starts, so this only trips when nothing is
	 * running the batch at all.
	 *
	 * @since 7.0
	 */
	public const STALLED_AFTER = 15 * MINUTE_IN_SECONDS;

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
	 * @param array $request `{ entry: '{source}/{entry}', background?: string[], install?: array, force?: bool }` —
	 *                       `background` is optional: a caller that names no files gets the entry's own list,
	 *                       resolved here. `inline` is trigger 3's own concurrent fetch and is deliberately not
	 *                       queued. An entry installed under more than one key passes `installs` instead: one
	 *                       `{ install, background }` pair per row, so the whole entry is claimed once
	 * @param bool  $manual  A Font Manager install: ignores the auto-install gate, `retry_after` and `removed`
	 *
	 * @since 7.0
	 */
	public function enqueue_once( array $request, bool $manual = false ): bool {
		return $this->enqueue( $request, $manual )['queued'];
	}

	/**
	 * Queue every request in a resolver's answer
	 *
	 * What the triggers that are not a render all do with one: each request is claimed on its own, so an entry
	 * already in flight costs nothing and the rest still go.
	 *
	 * @param array[] $requests
	 *
	 * @return int How many entries were queued
	 *
	 * @since 7.0
	 */
	public function enqueue_all( array $requests ): int {
		$queued = 0;

		foreach ( $requests as $request ) {
			if ( $this->enqueue_once( $request ) ) {
				++$queued;
			}
		}

		return $queued;
	}

	/**
	 * Queue what a render's scripts call for, and hand back the files worth fetching before it draws
	 *
	 * Trigger 3's entry point. The split is the queue's to make because only it sees the file sizes the cap is
	 * about, and only it may make it: the gate, the claim and the backoff all apply first, so a losing or
	 * suppressed request gets an empty list and the render falls back to the bundled faces.
	 *
	 * @param array[] $requests `Coverage_Resolver::for_scripts()`'s answer
	 *
	 * @return array[] `{ source, entry, name, size }` per file to fetch in-request
	 *
	 * @since 7.0
	 */
	public function enqueue_for_render( array $requests ): array {
		$inline = [];

		foreach ( $requests as $request ) {
			$inline = array_merge( $inline, $this->enqueue( $request, false )['inline'] );
		}

		return $inline;
	}

	/**
	 * @return array{queued: bool, inline: array[]}
	 *
	 * @since 7.0
	 */
	protected function enqueue( array $request, bool $manual ): array {
		$nothing = [
			'queued' => false,
			'inline' => [],
		];

		if ( ! $manual && ! $this->registry->auto_install_enabled() ) {
			return $nothing;
		}

		[ $source, $entry ] = Font_Sources::split( (string) ( $request['entry'] ?? '' ) );

		if ( $source === '' || $entry === '' ) {
			return $nothing;
		}

		$items  = [];
		$inline = [];
		$force  = ! empty( $request['force'] );

		/* Asked before the entry is read, so a trigger firing at an install already in flight costs one indexed row */
		if ( ! $this->catalog->is_claimable( $source, $entry, $manual ) ) {
			return $nothing;
		}

		if ( ! isset( $request['background'] ) && ! isset( $request['installs'] ) ) {
			$plan = $this->entry_plan( $source, $entry, $force );

			if ( $plan === [] ) {
				return $nothing;
			}

			[ $inline, $request['background'] ] = $this->split_plan( $source, $entry, $plan, (array) ( $request['scripts'] ?? [] ) );

			/* A partly installed entry reaches here, so the inline half needs the same drop the background half gets */
			$inline = $this->pending_inline( $source, $entry, $inline, $force );
		}

		foreach ( $this->requested_installs( $request ) as $requested ) {
			$install = (array) ( $requested['install'] ?? [] );
			$files   = (array) ( $requested['background'] ?? [] );

			foreach ( $this->pending_files( $source, $entry, $files, $force ) as $name ) {
				$item = [
					'source' => $source,
					'entry'  => $entry,
					'name'   => $name,
				];

				if ( $install !== [] ) {
					$item['install'] = $install;
				}

				$items[] = $item;
			}
		}

		if ( $items === [] && $inline === [] ) {
			return $nothing;
		}

		if ( ! $this->catalog->claim( $source, $entry, $manual ) ) {
			return $nothing;
		}

		foreach ( $items as $item ) {
			$this->push_to_queue( $item );
		}

		$this->flush();

		return [
			'queued' => true,
			'inline' => $inline,
		];
	}

	/**
	 * Split an entry's plan into what this render fetches now and what the batch fetches later
	 *
	 * A request that explains itself with scripts is trigger 3's: the faces mPDF reaches for first — every Regular
	 * and every line-break dictionary — are worth the wait, and everything else, bold and italic included, can
	 * arrive after this PDF (mPDF draws bold and italic from the Regular face until they do). A request with no
	 * reason means all of it, in the background, which is what triggers 1, 2 and 4 want.
	 *
	 * A face over the cap is never fetched inline. It is queued like any other file and the miss recorded, so the
	 * health check can tell the admin why this render used the bundled fallback.
	 *
	 * @param array<string, array{size: int, roles: array}> $plan
	 * @param string[]                                      $scripts
	 *
	 * @return array{0: array[], 1: string[]}
	 *
	 * @since 7.0
	 */
	protected function split_plan( string $source, string $entry, array $plan, array $scripts ): array {
		if ( $scripts === [] ) {
			return [ [], array_keys( $plan ) ];
		}

		$inline     = [];
		$background = [];
		$over_cap   = false;

		foreach ( $plan as $name => $file ) {
			if ( ! $this->drawn_first( (array) $file['roles'] ) ) {
				$background[] = $name;

				continue;
			}

			/* A size of 0 is an entry that never declared one, and the cap cannot be applied to an unknown */
			if ( $file['size'] <= 0 || $file['size'] > static::INLINE_CAP ) {
				$background[] = $name;
				$over_cap     = true;

				continue;
			}

			$inline[] = [
				'source' => $source,
				'entry'  => $entry,
				'name'   => (string) $name,
				'size'   => (int) $file['size'],
			];
		}

		if ( $over_cap ) {
			$this->catalog->record_missing_scripts( $source, $entry, $scripts );
		}

		return [ $inline, $background ];
	}

	/**
	 * The inline files that are not already on disk with a row
	 *
	 * @param array[] $inline
	 *
	 * @return array[]
	 *
	 * @since 7.0
	 */
	protected function pending_inline( string $source, string $entry, array $inline, bool $force ): array {
		$pending = array_flip( $this->pending_files( $source, $entry, array_column( $inline, 'name' ), $force ) );

		return array_values(
			array_filter(
				$inline,
				static function ( array $file ) use ( $pending ): bool {
					return isset( $pending[ $file['name'] ] );
				}
			)
		);
	}

	/**
	 * Whether any role this file fills is one the first render needs: a Regular face, or a line-break dictionary
	 *
	 * @param array $roles `{ font_key: { role: variant } }`
	 *
	 * @since 7.0
	 */
	protected function drawn_first( array $roles ): bool {
		foreach ( $roles as $by_role ) {
			foreach ( array_keys( (array) $by_role ) as $role ) {
				if ( $role === 'R' || strpos( (string) $role, 'dict_' ) === 0 ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * The files an entry has and what each one is for, for a caller that named none
	 *
	 * The triggers name an entry and nothing else, so this is where an entry document is read — **after** the
	 * auto-install gate: a site with auto-install off must not pay for it, and for a source that points at its
	 * entry file rather than inlining it, reading it is an HTTPS fetch. The count check comes first for the same
	 * reason, and answers from the cached rows alone.
	 *
	 * @return array<string, array{size: int, roles: array}>
	 *
	 * @since 7.0
	 */
	protected function entry_plan( string $source, string $entry, bool $force ): array {
		$expected = $this->catalog->file_count( $source, $entry );

		/* A 0 means the index declared no count, so it is a reason to ask rather than to assume the entry is done */
		if ( ! $force && $expected > 0 && count( $this->repository->paths_for_entry( $source, $entry ) ) >= $expected ) {
			return [];
		}

		$plan = $this->installer->plan_for( $source . '/' . $entry );

		return is_wp_error( $plan ) ? [] : $plan;
	}

	/**
	 * The installs this request asks for, in one shape
	 *
	 * A trigger asks for one — the entry's own row — and says so with `install` / `background`. The entry route
	 * updating a display entry asks for every install it already has, each with its own variants and therefore its
	 * own file list, and says so with `installs`. Both are one claim on one catalog row, which is the point.
	 *
	 * @return array<int, array{install?: array, background?: array}>
	 *
	 * @since 7.0
	 */
	protected function requested_installs( array $request ): array {
		$installs = (array) ( $request['installs'] ?? [] );

		if ( $installs !== [] ) {
			return $installs;
		}

		return [
			[
				'install'    => (array) ( $request['install'] ?? [] ),
				'background' => (array) ( $request['background'] ?? [] ),
			],
		];
	}

	/**
	 * The files of this request that are not already on disk with a row
	 *
	 * The default, and what every trigger wants: a trigger fires on its own, repeatedly, and must not re-claim a
	 * pack a previous one already placed — the claim is what writes `installing`, so without the drop a fully
	 * installed entry would churn its phase on every render.
	 *
	 * A caller that knows better says `force`. The install route does, on both of its jobs: an update leaves the
	 * files at the same paths and changes only their contents, and a further install of a display entry under its
	 * own key needs rows disjoint from whatever is already there. Neither pays for a download it does not need —
	 * `Font_Installer::already_installed()` skips any file whose recorded hash still matches.
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function pending_files( string $source, string $entry, array $files, bool $force ): array {
		$files = array_values( array_unique( array_map( 'strval', $files ) ) );

		if ( $force ) {
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

		/*
		 * The installer asks this again under the entry lock, and that is the gate; asking here first is what saves
		 * a whole batch of a deleted entry one `resolve()` — an HTTPS fetch per item, for a source that points at
		 * its entry file rather than inlining it. Neither answer is a failure worth recording on the row: it would
		 * be reporting the admin's own change back at them.
		 */
		if ( ! $this->catalog->is_wanted( $source, $entry ) ) {
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
			if ( $this->enqueue_once( [ 'entry' => $candidate['source'] . '/' . $candidate['entry'] ] ) ) {
				++$retried;
			}
		}

		return $retried;
	}

	/**
	 * Whether a batch is being processed right now
	 *
	 * GF keeps this protected, but an outstanding batch nothing is working on is exactly what the status route
	 * means by stuck, so the question has to be askable from outside.
	 *
	 * @since 7.0
	 */
	public function is_running(): bool {
		return (bool) $this->is_process_running();
	}

	/**
	 * Whether an install has stopped moving
	 *
	 * The two ways that happens — a dead cron and a blocked loopback — both leave a dispatched batch nobody picks
	 * up, and neither reports itself. What is observable is a row that says `installing` and has not moved while
	 * nothing is running.
	 *
	 * @since 7.0
	 */
	public function is_stalled(): bool {
		return ! $this->is_running() && $this->catalog->stalled_entries( static::STALLED_AFTER ) !== [];
	}

	/**
	 * Re-dispatch an outstanding batch nothing has picked up
	 *
	 * The first resort behind a stalled install, riding the traffic the Font Manager's poller already generates.
	 * Cheap to be wrong about: GF's `dispatch()` refuses a queue that is already processing or empty before it does
	 * anything, so this is a question as much as an instruction — the return value is the answer.
	 *
	 * @return bool Whether a batch was dispatched
	 *
	 * @since 7.0
	 */
	public function nudge(): bool {
		$dispatched = $this->dispatch();

		return $dispatched !== false && ! is_wp_error( $dispatched );
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
		if ( $this->is_running() ) {
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
