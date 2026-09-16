<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

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
 * The only writer of the catalog table's index columns
 *
 * Mirrors each source's published index into `gravitypdf_font_catalog`. It never touches the status columns, so an
 * install in flight keeps its phase across a sync, and it never touches the font rows, so a sync cannot change what
 * a PDF renders with.
 *
 * The root index is signed and the rest of the tree hangs off it by hash, so one ~300 byte verified request decides
 * whether anything else needs fetching at all.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Catalog_Sync {

	/**
	 * @since 7.0
	 */
	public const SYNC_INTERVAL = 30 * DAY_IN_SECONDS;

	/**
	 * @since 7.0
	 */
	public const RETRY_BACKOFF = 6 * HOUR_IN_SECONDS;

	/**
	 * One missed interval plus two weeks of retries: past this a source is a health issue
	 *
	 * @since 7.0
	 */
	public const HEALTH_AFTER = 45 * DAY_IN_SECONDS;

	/**
	 * @since 7.0
	 */
	public const EVENT = 'gfpdf_font_catalog_sync';

	/**
	 * Per-source sync state; the root URL is the registered record's, never stored here
	 *
	 * @since 7.0
	 */
	public const OPTION = 'gfpdf_font_catalog_root';

	/**
	 * The highest `generated` this site has accepted, per root, so an old signed root cannot be replayed
	 *
	 * Keyed by a hash of the root URL rather than the URL itself, since the URL is configuration that belongs to
	 * the record.
	 *
	 * @since 7.0
	 */
	public const GENERATED_OPTION = 'gfpdf_font_root_generated';

	/**
	 * @since 7.0
	 */
	public const LOCK = 'catalog_sync';

	/**
	 * @since 7.0
	 */
	public const LOCK_TTL = 300;

	/**
	 * @since 7.0
	 */
	public const RESYNC_LOCK = 'catalog_resync';

	/**
	 * How long a forced sync bars the next one — see `resync_stale()`
	 *
	 * Not `LOCK_TTL`: that guards a sync while it runs and is released the moment it ends, so it is no floor at
	 * all on how often one may be *asked* for. An hour bounds a site to one forced root fetch however many files
	 * of a withdrawn pack it walks, and sits well inside `Font_Installer::RETRY_AFTER`, so the retry that follows
	 * reads a catalogue no more than an hour old.
	 *
	 * @since 7.0
	 */
	public const RESYNC_DEBOUNCE = HOUR_IN_SECONDS;

	/**
	 * What the signature covers, prepended by the verifier rather than carried in the file
	 *
	 * Scoping by context is what lets one key sign different kinds of artefact without a signature ever verifying
	 * across purposes.
	 *
	 * @since 7.0
	 */
	public const SIGNATURE_CONTEXT = "gravitypdf/fonts/v1\n";

	/**
	 * A root more than this far ahead of the site clock is refused, so a forged root signed with a leaked key
	 * cannot push the monotonic floor far enough forward to lock legitimate roots out
	 *
	 * @since 7.0
	 */
	public const CLOCK_SKEW = 7 * DAY_IN_SECONDS;

	/**
	 * Rows per multi-row INSERT
	 *
	 * @since 7.0
	 */
	public const CHUNK = 200;

	/**
	 * The index columns, the only ones this class ever names
	 *
	 * @since 7.0
	 */
	public const INDEX_COLUMNS = [
		'source',
		'entry',
		'label',
		'version',
		'notes',
		'released',
		'coverage',
		'position',
		'license',
		'size',
		'files',
		'category',
		'subsets',
		'preview',
		'preview_text',
		'styles',
		'always',
		'scripts',
		'languages',
		'entry_sha256',
		'entry_json',
		'font_keys',
		'package',
	];

	/**
	 * @var Font_Schema
	 * @since 7.0
	 */
	protected $schema;

	/**
	 * @var Catalog_Repository
	 * @since 7.0
	 */
	protected $catalog;

	/**
	 * @var Font_Sources
	 * @since 7.0
	 */
	protected $sources;

	/**
	 * @var Font_Downloader
	 * @since 7.0
	 */
	protected $downloader;

	/**
	 * @var Font_Lock
	 * @since 7.0
	 */
	protected $lock;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	/**
	 * @var string[] base64 ed25519 keys; injected rather than read from the constant so a test can sign its own root
	 * @since 7.0
	 */
	protected $trust_keys;

	/**
	 * @var string Absolute path to the shipped packs index used when a site has never synced
	 * @since 7.0
	 */
	protected $seed_file;

	/**
	 * @var Catalog_Font_Adopter
	 * @since 7.0
	 */
	protected $adopter;

	public function __construct(
		Font_Schema $schema,
		Catalog_Repository $catalog,
		Font_Sources $sources,
		Font_Downloader $downloader,
		Font_Lock $lock,
		LoggerInterface $log,
		Catalog_Font_Adopter $adopter,
		array $trust_keys,
		string $seed_file
	) {
		$this->schema     = $schema;
		$this->catalog    = $catalog;
		$this->sources    = $sources;
		$this->downloader = $downloader;
		$this->lock       = $lock;
		$this->log        = $log;
		$this->trust_keys = $trust_keys;
		$this->adopter    = $adopter;
		$this->seed_file  = $seed_file;
	}

	/**
	 * Sync every registered source
	 *
	 * The callback of the one scheduled event, and what the install and upgrade routines call inline so adoption
	 * can follow in the same request. Returns false when another request holds the lock, which is correct in every
	 * topology: under per-site activation each site's hourly listener may fire.
	 *
	 * False also when the pass reached the end with a source still in error, so a caller is told the catalogue is
	 * not what the store published rather than reading a bare `true` over a pass that wrote nothing. Which source
	 * and why stays in its record's `last_error`, because one boolean cannot carry S of them.
	 *
	 * @since 7.0
	 */
	public function run(): bool {
		/*
		 * The tables are ensured here rather than assumed, because cron is the one caller that can arrive before
		 * any request has built them: `Font_Repository::ensure_ready()` runs on the first query of a *request*,
		 * and a scheduled sync on a fresh install is a request that makes none. Idempotent once they exist, and
		 * deliberately not `ensure_ready()` — the migration passes and the version stamp stay the repository's.
		 */
		if ( ! $this->schema->ensure() ) {
			$this->log->error( 'Cannot sync the font catalogue: its tables are missing and could not be created' );

			return false;
		}

		if ( ! $this->lock->acquire( static::LOCK, static::LOCK_TTL ) ) {
			return false;
		}

		$was_synced = $this->has_synced();
		$synced     = true;

		try {
			foreach ( $this->group_by_root() as $group ) {
				/* Every group runs: one unreachable root must not hide another's updates (see `request()`) */
				$synced = $this->sync_root( $group['root_url'], $group['records'] ) && $synced;
			}
		} finally {
			$this->lock->release( static::LOCK );
		}

		/*
		 * The catch-up for a site whose egress was blocked when it was installed: there was no catalogue for the
		 * install trigger to resolve against, so the first sync that does produce one fires it. Later syncs do not
		 * — by then every trigger has had a catalogue to ask.
		 */
		if ( ! $was_synced && $this->has_synced() ) {
			do_action( 'gfpdf_font_catalog_first_sync' );
		}

		return $synced;
	}

	/**
	 * Whether any source has ever synced on this network
	 *
	 * @since 7.0
	 */
	public function has_synced(): bool {
		foreach ( $this->get_records() as $record ) {
			if ( (int) ( $record['synced'] ?? 0 ) > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Run only when some source is actually due
	 *
	 * Hangs off the existing hourly cleanup event, so there is no new recurring cron to arm, re-arm or clear.
	 *
	 * @since 7.0
	 */
	public function maybe_run(): bool {
		if ( ! $this->is_due() ) {
			return false;
		}

		return $this->run();
	}

	/**
	 * Whether any source wants syncing now
	 *
	 * @since 7.0
	 */
	public function is_due(): bool {
		$now      = time();
		$records  = $this->get_records();
		$interval = static::SYNC_INTERVAL + $this->jitter();

		foreach ( array_keys( $this->sources->all() ) as $id ) {
			$record = array_merge( static::default_record(), $records[ $id ] ?? [] );

			$synced  = (int) $record['synced'];
			$attempt = (int) $record['last_attempt'];

			if ( $synced > 0 && ( $now - $synced ) < $interval ) {
				continue;
			}

			if ( $attempt > 0 && ( $now - $attempt ) < static::RETRY_BACKOFF ) {
				continue;
			}

			return true;
		}

		return false;
	}

	/**
	 * The Refresh button's entry point: compare every root inline and schedule the work when one differs
	 *
	 * A root that cannot be reached does not end the pass. Registered sources may sit on different origins — that
	 * is what `gfpdf_font_sources` is for — and returning on the first failure would let an outage at one of them
	 * hide every other source's updates, and skip scheduling the sync that would have taken them.
	 *
	 * @return array{up_to_date: bool, scheduled: bool, error?: string}
	 *
	 * @since 7.0
	 */
	public function request(): array {
		$changed = false;
		$error   = null;

		foreach ( $this->group_by_root() as $group ) {
			$root = $this->fetch_root( $group['root_url'], $group['records'] );

			if ( is_wp_error( $root ) ) {
				$this->record_failure( $group['records'], $root );

				/*
				 * One origin being unreachable says nothing about another's, so every root is asked before anything
				 * is reported. The failure is on the record either way, which is where the sources listing and
				 * `Catalog_Sync_Check` read it from; this only decides what Refresh itself answers.
				 */
				$error = $error ?? $root->get_error_message();

				continue;
			}

			foreach ( $group['records'] as $record ) {
				if ( $this->source_changed( $record->get_id(), $root ) ) {
					$changed = true;
				}
			}
		}

		if ( ! $changed ) {
			return $error === null
				? [
					'up_to_date' => true,
					'scheduled'  => false,
				]
				: [
					'up_to_date' => false,
					'scheduled'  => false,
					'error'      => $error,
				];
		}

		$this->schedule();

		/* Refresh is an admin waiting on an answer, so this one pays the loopback to get cron moving now */
		spawn_cron();

		return [
			'up_to_date' => false,
			'scheduled'  => true,
		];
	}

	/**
	 * Sync out of turn, because the store no longer has a file the catalogue names
	 *
	 * The store carries no object lock, so bytes a catalogue names really can be pruned. Without this the entry
	 * goes `failed` and retries *the same dead URL*, and nothing re-reads the index — so a site recovers only at
	 * the next `SYNC_INTERVAL`, up to 30 days away, or when an admin presses Refresh. Nor does anyone see it: a
	 * render falls back to the bundled faces, so the symptom is a PDF in the wrong font rather than an error,
	 * which is quieter and worse for being quiet.
	 *
	 * The debounce is the whole safety argument, and `wp_next_scheduled()` is not it — a pending event is gone the
	 * moment cron runs, and the sync's jitter spreads the *scheduled* pass rather than this one, so without
	 * `RESYNC_LOCK` a withdrawn pack is a thundering herd on the root across the install base. The lock is taken
	 * and never released: `Font_Lock::acquire()` hands the next caller a takeover once the TTL is past, which is
	 * a sliding window and exactly what is wanted.
	 *
	 * Not a lever worth attacking, either. It re-reads a signed root and verifies it, so whoever can make fetches
	 * fail wins some extra requests and nothing else.
	 *
	 * @return bool Whether this call is the one that scheduled it
	 *
	 * @since 7.0
	 */
	public function resync_stale(): bool {
		if ( ! $this->lock->acquire( static::RESYNC_LOCK, static::RESYNC_DEBOUNCE ) ) {
			return false;
		}

		$this->log->notice( 'Scheduling a font catalogue sync: the store no longer has a file the catalogue names' );

		$this->schedule();

		return true;
	}

	/**
	 * Queue the sync event for this request
	 *
	 * **Deliberately does not `spawn_cron()`.** Core hooks `_wp_cron()` on `shutdown` — `wp_cron()`, itself on
	 * `init` — and reads the due list *there*, so an event queued at `time()` is spawned at this request's
	 * shutdown with nobody asking, off the critical path. Spawning here as well would block for up to a second
	 * (`wp_remote_post()`'s 0.01s timeout is `ceil()`ed to 1 by the curl transport) and, under
	 * `ALTERNATE_WP_CRON`, would `wp_redirect()` the request it was called from — which on the trigger 3 path is
	 * a PDF mid-render. A caller that really is waiting on the answer spawns for itself; `request()` does.
	 *
	 * @since 7.0
	 */
	protected function schedule(): void {
		/* WP-Cron de-duplicates an identical pending event, so a second caller never queues a second sync */
		if ( ! wp_next_scheduled( static::EVENT ) ) {
			wp_schedule_single_event( time(), static::EVENT );
		}
	}

	/**
	 * Replace one source's index rows
	 *
	 * Every statement is a row-level atomic write and no transaction is taken: a failure part-way leaves a mix of
	 * old and new index rows that the next attempt overwrites, which is why `synced` is only bumped on success.
	 * The status columns are never named, so an install in flight keeps its phase.
	 *
	 * @param array  $entries The index's `entries` array
	 * @param string $sha256  The index hash the root vouched for, recorded so the next sync can skip this source
	 *
	 * @since 7.0
	 */
	public function replace_source( string $id, array $entries, string $sha256, ?array $sync_state = null ): bool {
		global $wpdb;

		$rows = [];
		foreach ( array_values( $entries ) as $position => $entry ) {
			$row = $this->to_row( $id, $entry, $position );

			if ( is_wp_error( $row ) ) {
				/* One bad entry refuses the whole index, so partial or malformed data never reaches the table */
				$this->log->error(
					'Refusing a font source index',
					[
						'source' => $id,
						'error'  => $row->get_error_message(),
					]
				);

				return $this->fail( $id, $row->get_error_message() );
			}

			$rows[] = $row;
		}

		$table = $this->schema->get_catalog_table();

		foreach ( array_chunk( $rows, static::CHUNK ) as $chunk ) {
			if ( ! $this->upsert( $table, $chunk ) ) {
				$this->catalog->flush();

				return $this->fail( $id, $wpdb->last_error );
			}
		}

		if ( ! $this->prune( $table, $id, wp_list_pluck( $rows, 'entry' ) ) ) {
			$this->catalog->flush();

			return $this->fail( $id, $wpdb->last_error );
		}

		/*
		 * The caller decides what this run means. A real sync stamps `synced` and the index hash; the seed writes
		 * rows without claiming a sync it never made, rather than stamping one and reversing it afterwards.
		 */
		if ( $sync_state === null ) {
			$this->verified(
				$id,
				[
					'index_sha256' => $sha256,
					'seeded'       => false,
				]
			);
		} else {
			$this->update_record( $id, $sync_state );
		}

		/* Bumped after the last statement, so no reader caches the old rows under the new stamp */
		$this->catalog->flush();

		/*
		 * A site that ran the 6.x installer, or placed a pack's files by hand, has them registered here rather
		 * than downloading what it already holds. Scoped to this source: the adopter reads the catalogue, and
		 * walking every source once per replaced source would re-read it S times per sync.
		 */
		if ( in_array( 1, array_column( $rows, 'coverage' ), true ) ) {
			$this->adopter->run( $id );
		}

		return true;
	}

	/**
	 * Fill an empty catalog from the index shipped with the plugin
	 *
	 * A fresh install whose first sync fails is not left with nothing to browse. The shipped index is trusted as
	 * code and carries no signature, but its build-time `generated` is older than any published root, so a later
	 * real sync always replaces it under the monotonic rule. The record stays marked `seeded` so the UI can say
	 * "never synced" rather than showing fake freshness.
	 *
	 * @since 7.0
	 */
	public function seed(): bool {
		$record = $this->get_record( 'packs' );

		if ( (int) $record['synced'] > 0 || ! is_readable( $this->seed_file ) ) {
			return false;
		}

		/* phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a file shipped inside the plugin, not a URL */
		$index = json_decode( (string) file_get_contents( $this->seed_file ), true );

		if ( ! is_array( $index ) || ! isset( $index['entries'] ) || ! is_array( $index['entries'] ) ) {
			$this->log->error( 'The shipped font index could not be read', [ 'file' => $this->seed_file ] );

			return false;
		}

		/*
		 * Rows without a sync stamp: the record stays `seeded` with `synced` at zero, so nothing reports freshness
		 * the seed cannot have, and the shipped index's older `generated` means a real sync always replaces it.
		 * The attempt and the error carry through untouched — seeding is a local fallback, not a sync attempt, so
		 * it neither moves the retry clock nor erases the reason the sync that just failed gives the health check.
		 */
		return $this->replace_source(
			'packs',
			$index['entries'],
			'',
			[
				'index_sha256' => '',
				'synced'       => 0,
				'last_attempt' => (int) $record['last_attempt'],
				'last_error'   => (string) $record['last_error'],
				'seeded'       => true,
			]
		);
	}

	/**
	 * Whether a source has gone long enough without a successful sync to be worth reporting
	 *
	 * The one place the threshold lives, shared by `GET /fonts/sources`' `stale` field and the health check.
	 *
	 * @since 7.0
	 */
	public static function is_stale( array $record ): bool {
		$synced = (int) ( $record['synced'] ?? 0 );

		return $synced === 0 || ( time() - $synced ) > static::HEALTH_AFTER;
	}

	/**
	 * The registered records, for a caller that wants to walk them
	 *
	 * @since 7.0
	 */
	public function get_sources(): Font_Sources {
		return $this->sources;
	}

	/**
	 * The registered sources whose catalogue is too old to trust, with their records
	 *
	 * A source that has never synced counts: a site that has never reached the origin is exactly the case worth
	 * telling an admin about.
	 *
	 * @return array<string, array>
	 *
	 * @since 7.0
	 */
	public function stale_sources(): array {
		$stale = [];

		foreach ( array_keys( $this->sources->all() ) as $id ) {
			$record = $this->get_record( (string) $id );

			if ( static::is_stale( $record ) ) {
				$stale[ (string) $id ] = $record;
			}
		}

		return $stale;
	}

	/**
	 * Every source's sync state, keyed by source id
	 *
	 * @since 7.0
	 */
	public function get_records(): array {
		$records = get_site_option( static::OPTION, [] );

		return is_array( $records ) ? $records : [];
	}

	/**
	 * @since 7.0
	 */
	public function get_record( string $id ): array {
		return array_merge( static::default_record(), $this->get_records()[ $id ] ?? [] );
	}

	/**
	 * The shape every source record has before a sync has written one
	 *
	 * @since 7.0
	 */
	public static function default_record(): array {
		return [
			'index_sha256' => '',
			'synced'       => 0,
			'last_attempt' => 0,
			'last_error'   => '',
			'seeded'       => false,
		];
	}

	/**
	 * Registered records grouped by root URL, so two sources sharing a root cost one root request
	 *
	 * @return array<string, array{root_url: string, records: Font_Source[]}>
	 *
	 * @since 7.0
	 */
	protected function group_by_root(): array {
		$groups = [];

		foreach ( $this->sources->all() as $source ) {
			$root = $source->get_root_url();

			if ( ! isset( $groups[ $root ] ) ) {
				$groups[ $root ] = [
					'root_url' => $root,
					'records'  => [],
				];
			}

			$groups[ $root ]['records'][] = $source;
		}

		return $groups;
	}

	/**
	 * Fetch, verify and decode one root index
	 *
	 * @param Font_Source[] $records
	 *
	 * @return array|WP_Error
	 *
	 * @since 7.0
	 */
	protected function fetch_root( string $root_url, array $records ) {
		$body = $this->downloader->fetch(
			$root_url . 'index.json',
			[
				'max_bytes'      => Font_Downloader::MAX_ROOT_BYTES,
				'allow_redirect' => true,
				'request_args'   => $this->shared_request_args( $records ),
			]
		);

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$error = $this->verify_root( $root_url, $body, $records );
		if ( $error !== null ) {
			return $error;
		}

		$root = json_decode( $body, true );

		if ( ! is_array( $root ) || ! isset( $root['sources'] ) || ! is_array( $root['sources'] ) ) {
			return new WP_Error( 'font_root_malformed', sprintf( 'The root index at %s is not readable', $root_url ) );
		}

		$error = $this->check_generated( $root_url, $root );
		if ( $error !== null ) {
			return $error;
		}

		return $root;
	}

	/**
	 * Check the detached signature over the context-prefixed root bytes
	 *
	 * Fails closed. A third-party record without a `public_key` gets origin trust over https alone, which is what
	 * it opted into by not supplying one.
	 *
	 * @param Font_Source[] $records
	 *
	 * @since 7.0
	 */
	protected function verify_root( string $root_url, string $body, array $records ): ?WP_Error {
		$keys = $this->keys_for( $root_url, $records );

		if ( $keys === null ) {
			return null;
		}

		if ( count( $keys ) === 0 ) {
			return new WP_Error( 'font_no_trust_keys', 'This build carries no font signing keys, so the root index cannot be verified' );
		}

		$signature = $this->downloader->fetch(
			$root_url . 'index.json.sig',
			[
				'max_bytes'      => Font_Downloader::MAX_SIGNATURE_BYTES,
				'allow_redirect' => true,
			]
		);

		if ( is_wp_error( $signature ) ) {
			return $signature;
		}

		/* phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decoding an ed25519 signature, not code */
		$raw = base64_decode( trim( (string) $signature ), true );

		if ( $raw === false || strlen( $raw ) !== SODIUM_CRYPTO_SIGN_BYTES ) {
			return new WP_Error( 'font_root_signature_malformed', sprintf( 'The signature for %s is not a valid ed25519 signature', $root_url ) );
		}

		$signed = static::SIGNATURE_CONTEXT . $body;

		foreach ( $keys as $key ) {
			/* phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decoding an ed25519 public key, not code */
			$public = base64_decode( (string) $key, true );

			if ( $public === false || strlen( $public ) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES ) {
				continue;
			}

			if ( sodium_crypto_sign_verify_detached( $raw, $signed, $public ) ) {
				return null;
			}
		}

		return new WP_Error( 'font_root_signature_invalid', sprintf( 'The root index at %s is not signed by a trusted key', $root_url ) );
	}

	/**
	 * Which keys verify this root, or null when the root is not signature-checked at all
	 *
	 * @param Font_Source[] $records
	 *
	 * @return string[]|null
	 *
	 * @since 7.0
	 */
	protected function keys_for( string $root_url, array $records ): ?array {
		if ( $root_url === Font_Sources::get_root_url() ) {
			return $this->trust_keys;
		}

		$keys = [];
		foreach ( $records as $record ) {
			$key = $record->get_public_key();

			if ( $key !== null && $key !== '' ) {
				$keys[] = $key;
			}
		}

		return count( $keys ) > 0 ? $keys : null;
	}

	/**
	 * The root's timestamp must move forward, and must not be implausibly far ahead
	 *
	 * Forward-only is the anti-replay rule: an old signed root pointing at a since-replaced font cannot be served
	 * back to a site that has already seen a newer one. A server-side rollback is therefore "re-sign the old tree
	 * with a newer `generated`", not "serve the old root again".
	 *
	 * @since 7.0
	 */
	protected function check_generated( string $root_url, array $root ): ?WP_Error {
		$generated = strtotime( (string) ( $root['generated'] ?? '' ) );

		if ( $generated === false ) {
			return new WP_Error( 'font_root_malformed', sprintf( 'The root index at %s carries no usable generated timestamp', $root_url ) );
		}

		if ( $generated > ( time() + static::CLOCK_SKEW ) ) {
			return new WP_Error( 'font_root_from_future', sprintf( 'The root index at %s is dated further ahead than clock skew explains', $root_url ) );
		}

		$floor = $this->get_generated_floor( $root_url );

		if ( $generated < $floor ) {
			return new WP_Error( 'font_root_replayed', sprintf( 'The root index at %s is older than one this site has already accepted', $root_url ) );
		}

		$this->set_generated_floor( $root_url, $generated );

		return null;
	}

	/**
	 * @param Font_Source[] $records
	 *
	 * @return bool Whether every record on this root ended the pass without an error
	 *
	 * @since 7.0
	 */
	protected function sync_root( string $root_url, array $records ): bool {
		$root = $this->fetch_root( $root_url, $records );

		if ( is_wp_error( $root ) ) {
			$this->log->error(
				'Font catalog sync failed',
				[
					'root'  => $root_url,
					'error' => $root->get_error_message(),
				]
			);

			$this->record_failure( $records, $root );

			return false;
		}

		$synced = true;

		foreach ( $records as $record ) {
			$id = $record->get_id();

			if ( ! isset( $root['sources'][ $id ] ) ) {
				/* Its rows and its installed fonts are both left alone: installed state never reads the catalogue */
				$this->log->warning( 'The font root does not list a registered source', [ 'source' => $id ] );

				continue;
			}

			/*
			 * Nothing to fetch, and that is a sync: `synced` answers "when did this site last confirm its
			 * catalogue", which is the question `is_stale()` asks. Bumped only where rows are written it tracked
			 * the *store's* last publish instead, so a quiet month past `SYNC_INTERVAL` put every site on
			 * `RETRY_BACKOFF` and raised `Catalog_Sync_Check` against a store answering perfectly — teaching
			 * every admin to ignore the one signal that would show a freeze attack.
			 *
			 * Below `source_changed()`, not above it: the changed path reaches the same stamp through
			 * `replace_source()`, and stamping before the fetch would mark a source fresh whose index then failed
			 * to arrive or failed to parse — which is this defect pointed the other way.
			 */
			if ( ! $this->source_changed( $id, $root ) ) {
				$this->verified( $id );

				continue;
			}

			$synced = $this->sync_source( $record, (array) $root['sources'][ $id ] ) && $synced;
		}

		return $synced;
	}

	/**
	 * @return bool Whether this source's rows are now the index the root vouched for
	 *
	 * @since 7.0
	 */
	protected function sync_source( Font_Source $record, array $listing ): bool {
		$id     = $record->get_id();
		$sha256 = (string) ( $listing['sha256'] ?? '' );

		$body = $this->downloader->fetch(
			sprintf( '%ssources/%s-%s.json', $record->get_root_url(), $id, $sha256 ),
			[
				'sha256'       => $sha256,
				'size'         => $listing['size'] ?? null,
				'request_args' => $record->get_request_args(),
			]
		);

		if ( is_wp_error( $body ) ) {
			$this->log->error(
				'Could not fetch a font source index',
				[
					'source' => $id,
					'error'  => $body->get_error_message(),
				]
			);

			return $this->fail( $id, $body->get_error_message() );
		}

		$index = json_decode( $body, true );

		if ( ! is_array( $index ) || ! isset( $index['entries'] ) || ! is_array( $index['entries'] ) ) {
			return $this->fail( $id, 'The source index is not readable' );
		}

		return $this->replace_source( $id, $index['entries'], $sha256 );
	}

	/**
	 * @since 7.0
	 */
	protected function source_changed( string $id, array $root ): bool {
		$listed = (string) ( $root['sources'][ $id ]['sha256'] ?? '' );

		if ( $listed === '' ) {
			return false;
		}

		return $listed !== $this->get_record( $id )['index_sha256'];
	}

	/**
	 * Turn one index entry into its catalog row, rejecting anything that must not be stored
	 *
	 * @return array|WP_Error
	 *
	 * @since 7.0
	 */
	protected function to_row( string $source, $entry, int $position ) {
		if ( ! is_array( $entry ) ) {
			return new WP_Error( 'font_entry_malformed', 'An index entry is not an object' );
		}

		$id = (string) ( $entry['id'] ?? '' );

		if ( preg_match( Font_Source::ID_PATTERN, $id ) !== 1 ) {
			return new WP_Error( 'font_entry_malformed', sprintf( 'The entry id "%s" is not valid', $id ) );
		}

		$inlined  = $entry['entry'] ?? null;
		$coverage = empty( $entry['coverage'] ) ? 0 : 1;

		if ( is_array( $inlined ) ) {
			/* The flag lives out here, on the index entry, and the document inside knows nothing of it */
			$invalid = Font_Sources::validate_entry( $inlined, $coverage === 1 );

			if ( $invalid !== null ) {
				return new WP_Error( 'font_invalid_entry', sprintf( 'Entry "%s": %s', $id, $invalid ) );
			}

			if ( $coverage === 1 ) {
				$this->warn_unrouted( $source, $id, $entry, $inlined );
			}
		}

		return [
			'source'       => $source,
			'entry'        => $id,
			'label'        => (string) ( $entry['label'] ?? $id ),
			'version'      => (string) ( $entry['version'] ?? '' ),
			'notes'        => $this->nullable_string( $entry['notes'] ?? null, 255 ),
			'released'     => $this->nullable_date( $entry['released'] ?? null ),
			'coverage'     => $coverage,
			'position'     => $position,
			'license'      => (string) ( $entry['license'] ?? '' ),
			'size'         => (int) ( $entry['size'] ?? 0 ),
			'files'        => (int) ( $entry['files'] ?? 0 ),
			'category'     => $this->nullable_string( $entry['category'] ?? null, 32 ),
			'subsets'      => $this->csv( $entry['subsets'] ?? null ),
			'preview'      => $this->nullable_string( $entry['preview'] ?? null, 255 ),
			'preview_text' => $this->nullable_string( $entry['preview_text'] ?? null, 255 ),
			'styles'       => $this->csv( $entry['styles'] ?? null ),
			'always'       => empty( $entry['always'] ) ? 0 : 1,
			'scripts'      => $this->csv( $entry['scripts'] ?? null ),
			'languages'    => $this->csv( $entry['languages'] ?? null ),
			'entry_sha256' => $this->nullable_string( $entry['entry_sha256'] ?? null, 64 ),
			'entry_json'   => is_array( $inlined ) ? wp_json_encode( $inlined ) : null,
			/* Mirrored so the unregistered-font check can map a missing key to its entry without decoding anything */
			'font_keys'    => is_array( $inlined ) ? $this->csv( array_keys( (array) ( $inlined['fonts'] ?? [] ) ) ) : null,
			/*
			 * The offline archive's object name, taken from the row and never rebuilt from `entry`/`version`: an
			 * import matches an uploaded package against this column, so the source owns the filename's grammar
			 * and can change it without a coordinated plugin release. The inlined entry carries the same string
			 * for an airgapped reader, which has the archive and no catalogue; the two are gated equal upstream,
			 * and reading the inlined copy here as a fallback would be the second answer that lets them drift.
			 */
			'package'      => $this->nullable_string( $entry['package'] ?? null, 255 ),
		];
	}

	/**
	 * Say so when a pack claims a language nothing in its own map can answer
	 *
	 * A log line, never a refusal, unlike everything else this method checks: the pack installs, and a template
	 * naming its font renders correctly — it is the documents naming none that are drawn in the wrong one.
	 * `Language_To_Font::unrouted_claims()` holds why that happens.
	 *
	 * Only what the index inlines: one warning per publish is the point, not one per site per install.
	 *
	 * @param array $entry   The index entry, which is where the claim columns live
	 * @param array $inlined The entry document, which is where the routes live
	 *
	 * @since 7.0
	 */
	protected function warn_unrouted( string $source, string $id, array $entry, array $inlined ): void {
		$unrouted = Language_To_Font::unrouted_claims(
			$entry,
			array_keys( (array) ( $inlined['language_to_font'] ?? [] ) ),
			$inlined['unrouted'] ?? null
		);

		if ( $unrouted === [] ) {
			return;
		}

		$this->log->warning(
			'A font pack claims languages its own map does not route',
			[
				'entry'    => Font_Sources::join( $source, $id ),
				'unrouted' => $unrouted,
			]
		);
	}

	/**
	 * @since 7.0
	 */
	protected function upsert( string $table, array $rows ): bool {
		global $wpdb;

		$columns      = static::INDEX_COLUMNS;
		$placeholders = '(' . implode( ', ', array_fill( 0, count( $columns ), '%s' ) ) . ')';

		$values = [];
		$params = [];
		foreach ( $rows as $row ) {
			$values[] = $placeholders;

			foreach ( $columns as $column ) {
				$params[] = $row[ $column ];
			}
		}

		$updates = [];
		foreach ( $columns as $column ) {
			if ( $column === 'source' || $column === 'entry' ) {
				continue;
			}

			$updates[] = "{$column} = VALUES({$column})";
		}

		$sql = sprintf(
			'INSERT INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE %s',
			$table,
			implode( ', ', $columns ),
			implode( ', ', $values ),
			implode( ', ', $updates )
		);

		/* phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders -- table and column names come from this class; every value is prepared */
		$result = $wpdb->query( $wpdb->prepare( $sql, $params ) );

		return $result !== false;
	}

	/**
	 * Drop the rows for entries this index no longer carries
	 *
	 * @param string[] $keep
	 *
	 * @since 7.0
	 */
	protected function prune( string $table, string $source, array $keep ): bool {
		global $wpdb;

		if ( count( $keep ) === 0 ) {
			/* phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- table name comes from Font_Schema */
			return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE source = %s", $source ) ) !== false;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $keep ), '%s' ) );

		/* phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders -- table name comes from Font_Schema; every value is prepared */
		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE source = %s AND entry NOT IN ({$placeholders})",
				array_merge( [ $source ], $keep )
			)
		);
		/* phpcs:enable */

		return $result !== false;
	}

	/**
	 * @param Font_Source[] $records
	 *
	 * @since 7.0
	 */
	protected function record_failure( array $records, WP_Error $error ): void {
		foreach ( $records as $record ) {
			$this->fail( $record->get_id(), $error->get_error_message() );
		}
	}

	/**
	 * Record that this source is current as of now, and clear whatever the last attempt left behind
	 *
	 * `fail()`'s counterpart, and the only writer of `synced`: a source is current either because its index was
	 * replaced or because the root says the one it holds is still the published one.
	 *
	 * @param array $extra Fields the caller owns, merged over this set
	 *
	 * @since 7.0
	 */
	protected function verified( string $id, array $extra = [] ): void {
		$this->update_record(
			$id,
			$extra + [
				'synced'       => time(),
				'last_attempt' => time(),
				'last_error'   => '',
			]
		);
	}

	/**
	 * Record that an attempt on this source failed, and answer false for the caller to return
	 *
	 * `last_attempt` always moves with `last_error`, which is what makes `maybe_run()` wait out `RETRY_BACKOFF`
	 * rather than retrying on every tick.
	 *
	 * @since 7.0
	 */
	protected function fail( string $id, string $message ): bool {
		$this->update_record(
			$id,
			[
				'last_attempt' => time(),
				'last_error'   => $message,
			]
		);

		return false;
	}

	/**
	 * @since 7.0
	 */
	protected function update_record( string $id, array $fields ): void {
		$records        = $this->get_records();
		$records[ $id ] = array_merge( static::default_record(), $records[ $id ] ?? [], $fields );

		update_site_option( static::OPTION, $records );
	}

	/**
	 * Query args shared by every record on one root; the built-ins send none
	 *
	 * @param Font_Source[] $records
	 *
	 * @since 7.0
	 */
	protected function shared_request_args( array $records ): array {
		$args = [];

		foreach ( $records as $record ) {
			$args = array_merge( $args, $record->get_request_args() );
		}

		return $args;
	}

	/**
	 * @since 7.0
	 */
	protected function get_generated_floor( string $root_url ): int {
		$floors = get_site_option( static::GENERATED_OPTION, [] );

		return (int) ( is_array( $floors ) ? ( $floors[ md5( $root_url ) ] ?? 0 ) : 0 );
	}

	/**
	 * @since 7.0
	 */
	protected function set_generated_floor( string $root_url, int $generated ): void {
		$floors = get_site_option( static::GENERATED_OPTION, [] );
		$floors = is_array( $floors ) ? $floors : [];

		$floors[ md5( $root_url ) ] = $generated;

		update_site_option( static::GENERATED_OPTION, $floors );
	}

	/**
	 * A per-site offset so a fleet upgraded together does not sync in lockstep
	 *
	 * Deterministic rather than random, so a site's due time does not move every time the question is asked.
	 *
	 * @since 7.0
	 */
	protected function jitter(): int {
		return crc32( home_url() ) % DAY_IN_SECONDS;
	}

	/**
	 * @since 7.0
	 */
	protected function csv( $value ): ?string {
		if ( is_array( $value ) ) {
			$value = implode( ',', array_map( 'strval', $value ) );
		}

		return $this->nullable_string( $value );
	}

	/**
	 * @since 7.0
	 */
	protected function nullable_string( $value, ?int $limit = null ): ?string {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return null;
		}

		$value = (string) $value;

		if ( $value === '' ) {
			return null;
		}

		/* By character, like `Font_Sources::display_name()`: these columns count characters, and a byte cut lands mid-sequence */
		return $limit === null ? $value : mb_substr( $value, 0, $limit );
	}

	/**
	 * @since 7.0
	 */
	protected function nullable_date( $value ): ?string {
		if ( ! is_string( $value ) || preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) !== 1 ) {
			return null;
		}

		return $value;
	}
}
