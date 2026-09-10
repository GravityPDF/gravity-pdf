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
	 * @since 7.0
	 */
	public function run(): bool {
		if ( ! $this->lock->acquire( static::LOCK, static::LOCK_TTL ) ) {
			return false;
		}

		$was_synced = $this->has_synced();

		try {
			foreach ( $this->group_by_root() as $group ) {
				$this->sync_root( $group['root_url'], $group['records'] );
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

		return true;
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
	 * The Refresh button's entry point: compare the root inline and schedule the work when it differs
	 *
	 * @return array{up_to_date: bool, scheduled: bool, error?: string}
	 *
	 * @since 7.0
	 */
	public function request(): array {
		$changed = false;

		foreach ( $this->group_by_root() as $group ) {
			$root = $this->fetch_root( $group['root_url'], $group['records'] );

			if ( is_wp_error( $root ) ) {
				$this->record_failure( $group['records'], $root );

				return [
					'up_to_date' => false,
					'scheduled'  => false,
					'error'      => $root->get_error_message(),
				];
			}

			foreach ( $group['records'] as $record ) {
				if ( $this->source_changed( $record->get_id(), $root ) ) {
					$changed = true;
				}
			}
		}

		if ( ! $changed ) {
			return [
				'up_to_date' => true,
				'scheduled'  => false,
			];
		}

		/* WP-Cron de-duplicates an identical pending event, so a second Refresh does not queue a second sync */
		if ( ! wp_next_scheduled( static::EVENT ) ) {
			wp_schedule_single_event( time(), static::EVENT );
		}

		spawn_cron();

		return [
			'up_to_date' => false,
			'scheduled'  => true,
		];
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
		$this->update_record(
			$id,
			$sync_state === null
				? [
					'index_sha256' => $sha256,
					'synced'       => time(),
					'last_attempt' => time(),
					'last_error'   => '',
					'seeded'       => false,
				]
				: $sync_state
		);

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
	 * Every source's sync state, keyed by source id
	 *
	 * @since 7.0
	 */
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
	 * @since 7.0
	 */
	protected function sync_root( string $root_url, array $records ): void {
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

			return;
		}

		foreach ( $records as $record ) {
			$id = $record->get_id();

			if ( ! isset( $root['sources'][ $id ] ) ) {
				/* Its rows and its installed fonts are both left alone: installed state never reads the catalogue */
				$this->log->warning( 'The font root does not list a registered source', [ 'source' => $id ] );

				continue;
			}

			$this->update_record( $id, [ 'last_attempt' => time() ] );

			if ( ! $this->source_changed( $id, $root ) ) {
				continue;
			}

			$this->sync_source( $record, (array) $root['sources'][ $id ] );
		}
	}

	/**
	 * @since 7.0
	 */
	protected function sync_source( Font_Source $record, array $listing ): void {
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

			$this->fail( $id, $body->get_error_message() );

			return;
		}

		$index = json_decode( $body, true );

		if ( ! is_array( $index ) || ! isset( $index['entries'] ) || ! is_array( $index['entries'] ) ) {
			$this->fail( $id, 'The source index is not readable' );

			return;
		}

		$this->replace_source( $id, $index['entries'], $sha256 );
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

		$inlined = $entry['entry'] ?? null;

		if ( is_array( $inlined ) ) {
			$invalid = Font_Sources::validate_entry( $inlined );

			if ( $invalid !== null ) {
				return new WP_Error( 'font_invalid_entry', sprintf( 'Entry "%s": %s', $id, $invalid ) );
			}
		}

		return [
			'source'       => $source,
			'entry'        => $id,
			'label'        => (string) ( $entry['label'] ?? $id ),
			'version'      => (string) ( $entry['version'] ?? '' ),
			'notes'        => $this->nullable_string( $entry['notes'] ?? null, 255 ),
			'released'     => $this->nullable_date( $entry['released'] ?? null ),
			'coverage'     => empty( $entry['coverage'] ) ? 0 : 1,
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
		];
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

		return $limit === null ? $value : substr( $value, 0, $limit );
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
