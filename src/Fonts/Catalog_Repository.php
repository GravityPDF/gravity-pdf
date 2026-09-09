<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

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
 * The one reader of the font catalog table
 *
 * The catalog is the slim mirror of each source's published index: what is *installable*. What is installed is the
 * font rows, which `Font_Repository` owns and which never consult this table — so a sync can never change what a PDF
 * renders with. Index columns are written by `Catalog_Sync` alone; the status columns, which carry install progress,
 * by `set_status()` alone.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Catalog_Repository {

	/**
	 * Bumped by every catalog write, so a sync invalidates catalog reads without evicting the render path's font rows
	 *
	 * @since 7.0
	 */
	public const CACHE_STAMP = 'catalog:last_changed';

	/**
	 * The status columns: install progress, never the sync's to write
	 *
	 * @since 7.0
	 */
	public const STATUS_COLUMNS = [ 'phase', 'phase_since', 'error', 'retry_after', 'missing_scripts', 'missing_since' ];

	/**
	 * How long a `queued` or `installing` row is believed before `claim()` treats it as a dead batch's leftover
	 *
	 * Long enough that a real pack install — every file refreshing `phase_since` as it starts — never trips it.
	 *
	 * @since 7.0
	 */
	public const STALE_AFTER = 30 * MINUTE_IN_SECONDS;

	/**
	 * Every column a list read selects: the index columns minus `entry_json`, plus the status columns
	 *
	 * Derived rather than typed out again, because a second literal list is one a new column can be left out of
	 * silently. `entry_json` is a LONGTEXT holding a whole entry object, so a browse of ~1,800 rows would drag the
	 * lot through PHP and into the object cache for nothing; only `entry()` and the adopter read it.
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	public static function list_columns(): array {
		return array_merge(
			array_values( array_diff( Catalog_Sync::INDEX_COLUMNS, [ 'entry_json' ] ) ),
			static::STATUS_COLUMNS
		);
	}

	/**
	 * @var Font_Schema
	 * @since 7.0
	 */
	protected $schema;

	/**
	 * @var Font_Sources
	 * @since 7.0
	 */
	protected $sources;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	public function __construct( Font_Schema $schema, Font_Sources $sources, LoggerInterface $log ) {
		$this->schema  = $schema;
		$this->sources = $sources;
		$this->log     = $log;

		wp_cache_add_global_groups( [ Font_Repository::CACHE_GROUP ] );
	}

	/**
	 * One page of a source's entries, in the index's own order
	 *
	 * @param array $args `s`, `category`, `subset`, `coverage`, `page`, `per_page`
	 *
	 * @return array{entries: array[], total: int, pages: int}
	 *
	 * @since 7.0
	 */
	public function search( string $source, array $args = [] ): array {
		global $wpdb;

		$args = array_merge(
			[
				's'        => '',
				'category' => '',
				'subset'   => '',
				'coverage' => null,
				'page'     => 1,
				'per_page' => 24,
			],
			$args
		);

		$per_page = max( 1, min( 100, (int) $args['per_page'] ) );
		$page     = max( 1, (int) $args['page'] );

		$key    = $this->cache_key( 'search', [ $source, $args, $per_page, $page ] );
		$cached = wp_cache_get( $key, Font_Repository::CACHE_GROUP );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$where  = [ 'source = %s' ];
		$params = [ $source ];

		if ( $args['s'] !== '' ) {
			$where[]  = '(label LIKE %s OR entry LIKE %s)';
			$like     = '%' . $wpdb->esc_like( (string) $args['s'] ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		if ( $args['category'] !== '' ) {
			$where[]  = 'category = %s';
			$params[] = (string) $args['category'];
		}

		if ( $args['subset'] !== '' ) {
			$where[]  = 'FIND_IN_SET(%s, subsets)';
			$params[] = (string) $args['subset'];
		}

		if ( $args['coverage'] !== null ) {
			$where[]  = 'coverage = %d';
			$params[] = (int) (bool) $args['coverage'];
		}

		$table   = $this->schema->get_catalog_table();
		$columns = implode( ', ', static::list_columns() );
		$clause  = implode( ' AND ', $where );

		/* phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders -- table and column names come from this class; the placeholders sit inside $clause, which the sniff cannot see through, and every value is in $params; results are cached */
		$total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$clause}", $params )
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT {$columns} FROM {$table} WHERE {$clause} ORDER BY position ASC, entry ASC LIMIT %d OFFSET %d",
				array_merge( $params, [ $per_page, ( $page - 1 ) * $per_page ] )
			),
			ARRAY_A
		);
		/* phpcs:enable */

		$result = [
			'entries' => array_map( [ $this, 'cast_row' ], (array) $rows ),
			'total'   => $total,
			'pages'   => (int) ceil( $total / $per_page ),
		];

		wp_cache_set( $key, $result, Font_Repository::CACHE_GROUP );

		return $result;
	}

	/**
	 * One entry, with its inlined `entry` object decoded when the index carried one
	 *
	 * The only read that selects `entry_json`. A pointed-at entry has `entry_sha256` instead and is fetched by the
	 * installer, never by a route.
	 *
	 * @since 7.0
	 */
	public function entry( string $source, string $id ): ?array {
		global $wpdb;

		$key    = $this->cache_key( 'entry', [ $source, $id ] );
		$cached = wp_cache_get( $key, Font_Repository::CACHE_GROUP );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$table = $this->schema->get_catalog_table();

		/* phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- table name comes from Font_Schema; result is cached */
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE source = %s AND entry = %s", $source, $id ), ARRAY_A );

		if ( ! is_array( $row ) ) {
			return null;
		}

		$row = $this->cast_row( $row );

		$decoded     = $row['entry_json'] === null || $row['entry_json'] === '' ? null : json_decode( $row['entry_json'], true );
		$row['data'] = is_array( $decoded ) ? $decoded : null;

		unset( $row['entry_json'] );

		wp_cache_set( $key, $row, Font_Repository::CACHE_GROUP );

		return $row;
	}

	/**
	 * Every coverage entry across every source
	 *
	 * What `Coverage_Resolver` and `Script_Detector` read: `always`, `scripts` and `languages` are columns, so
	 * neither ever decodes an entry.
	 *
	 * @return array[]
	 *
	 * @since 7.0
	 */
	public function coverage_entries(): array {
		global $wpdb;

		$key    = $this->cache_key( 'coverage', [] );
		$cached = wp_cache_get( $key, Font_Repository::CACHE_GROUP );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$table   = $this->schema->get_catalog_table();
		$columns = implode( ', ', static::list_columns() );

		/* phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- table and column names come from this class; result is cached */
		$rows = $wpdb->get_results( "SELECT {$columns} FROM {$table} WHERE coverage = 1 ORDER BY position ASC, entry ASC", ARRAY_A );

		$rows = array_map( [ $this, 'cast_row' ], (array) $rows );

		wp_cache_set( $key, $rows, Font_Repository::CACHE_GROUP );

		return $rows;
	}

	/**
	 * Every coverage entry of one source, with its inlined entry decoded
	 *
	 * The adopter's read, and the only one besides `entry()` that touches `entry_json`. Deliberately uncached: it
	 * runs after a sync rather than on any request path, and caching a LONGTEXT per row is exactly what the browse
	 * reads avoid.
	 *
	 * @return array[] Each row with a decoded `data`, rows carrying no inlined entry omitted
	 *
	 * @since 7.0
	 */
	public function coverage_entries_for_adoption( string $source ): array {
		global $wpdb;

		$table   = $this->schema->get_catalog_table();
		$columns = implode( ', ', static::list_columns() );

		/* phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders -- table and column names come from this class; the value is prepared */
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT {$columns}, entry_json FROM {$table} WHERE coverage = 1 AND source = %s ORDER BY position ASC, entry ASC", $source ),
			ARRAY_A
		);
		/* phpcs:enable */

		$decoded = [];
		foreach ( (array) $rows as $row ) {
			$data = $row['entry_json'] === null || $row['entry_json'] === '' ? null : json_decode( (string) $row['entry_json'], true );

			/* A pointed-at entry names no files without a fetch, and adoption never fetches */
			if ( ! is_array( $data ) ) {
				continue;
			}

			unset( $row['entry_json'] );

			$row         = $this->cast_row( $row );
			$row['data'] = $data;

			$decoded[] = $row;
		}

		return $decoded;
	}

	/**
	 * The counts and filter vocabulary behind `GET /fonts/sources`
	 *
	 * @return array{total: int, coverage: int, filters: array{category: array<string, int>, subsets: array<string, int>}}
	 *
	 * @since 7.0
	 */
	public function summary( string $source ): array {
		global $wpdb;

		$key    = $this->cache_key( 'summary', [ $source ] );
		$cached = wp_cache_get( $key, Font_Repository::CACHE_GROUP );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$table = $this->schema->get_catalog_table();

		/* phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- table name comes from Font_Schema; result is cached */
		$totals = $wpdb->get_row(
			$wpdb->prepare( "SELECT COUNT(*) AS total, SUM(coverage) AS coverage FROM {$table} WHERE source = %s", $source ),
			ARRAY_A
		);

		$categories = $wpdb->get_results(
			$wpdb->prepare( "SELECT category, COUNT(*) AS n FROM {$table} WHERE source = %s AND category IS NOT NULL AND category != '' GROUP BY category", $source ),
			ARRAY_A
		);

		/* Split in PHP: the column is a CSV, and ~1,800 short rows is cheaper than a join table nothing else needs */
		$subset_rows = $wpdb->get_col(
			$wpdb->prepare( "SELECT subsets FROM {$table} WHERE source = %s AND subsets IS NOT NULL AND subsets != ''", $source )
		);
		/* phpcs:enable */

		$category_counts = [];
		foreach ( (array) $categories as $row ) {
			$category_counts[ (string) $row['category'] ] = (int) $row['n'];
		}

		$subset_counts = [];
		foreach ( (array) $subset_rows as $csv ) {
			foreach ( explode( ',', (string) $csv ) as $subset ) {
				if ( $subset === '' ) {
					continue;
				}

				$subset_counts[ $subset ] = ( $subset_counts[ $subset ] ?? 0 ) + 1;
			}
		}

		$summary = [
			'total'    => (int) ( $totals['total'] ?? 0 ),
			'coverage' => (int) ( $totals['coverage'] ?? 0 ),
			'filters'  => [
				'category' => $category_counts,
				'subsets'  => $subset_counts,
			],
		];

		wp_cache_set( $key, $summary, Font_Repository::CACHE_GROUP );

		return $summary;
	}

	/**
	 * Write one entry's install progress
	 *
	 * A single-row UPDATE naming only the status columns, so two installs writing different entries never clobber
	 * each other and an in-flight phase survives a sync.
	 *
	 * `phase_since` is stamped here rather than by each caller, because every consumer of it — the poller's `stuck`
	 * flag, `claim()`'s staleness arm, the stalled-batch notice — is asking "how long has it been in this phase",
	 * which is only true if nothing can move `phase` without moving it. UTC, like every other time this class and
	 * `Font_Installer` compare.
	 *
	 * @param array $fields Any of STATUS_COLUMNS
	 * @param bool  $bump   Whether to invalidate cached catalog reads; false for the render path's union-only
	 *                      `missing_scripts` write, which must not let anonymous requests churn the object cache
	 *
	 * @since 7.0
	 */
	public function set_status( string $source, string $entry, array $fields, bool $bump = true ): bool {
		global $wpdb;

		$data = array_intersect_key( $fields, array_flip( static::STATUS_COLUMNS ) );

		if ( count( $data ) === 0 ) {
			return false;
		}

		if ( array_key_exists( 'phase', $data ) && ! array_key_exists( 'phase_since', $data ) ) {
			$data['phase_since'] = $data['phase'] === null ? null : gmdate( 'Y-m-d H:i:s' );
		}

		$updated = $wpdb->update(
			$this->schema->get_catalog_table(),
			$data,
			[
				'source' => $source,
				'entry'  => $entry,
			]
		);

		if ( $updated === false ) {
			$this->log->error(
				'Could not write font catalog status',
				[
					'source' => $source,
					'entry'  => $entry,
					'error'  => $wpdb->last_error,
				]
			);

			return false;
		}

		if ( $bump ) {
			$this->flush();
		}

		return true;
	}

	/**
	 * Claim one entry for install, atomically
	 *
	 * One conditional UPDATE is the whole dedup: two triggers firing at once produce one push and no second lock,
	 * because only the statement that actually changed a row may queue work. The arms, in order — never claimed;
	 * failed and past its backoff; and a `queued`/`installing` row whose `phase_since` has gone stale, which is
	 * what recovers the rows of a batch whose process died.
	 *
	 * `removed` is deliberately absent from the background set: a pre-render trigger must not resurrect a pack the
	 * admin deleted. A manual install may, which is the same decision as it ignoring `retry_after`.
	 *
	 * `retry_after` is left alone rather than cleared. The arms above have already decided it is spent, and leaving
	 * it is what lets `Font_Installer::fail()` tell a first failure from a repeat without a counter column. Only
	 * the `failed` arm reads it, so a stale value under `queued` is never consulted.
	 *
	 * @since 7.0
	 */
	public function claim( string $source, string $entry, bool $manual = false ): bool {
		global $wpdb;

		$table = $this->schema->get_catalog_table();
		$now   = gmdate( 'Y-m-d H:i:s' );
		$stale = gmdate( 'Y-m-d H:i:s', time() - static::STALE_AFTER );

		/* phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery -- the table name comes from Font_Schema; every value is prepared */
		if ( $manual ) {
			$sql = $wpdb->prepare(
				"UPDATE {$table} SET phase = 'queued', phase_since = %s, error = NULL
				 WHERE source = %s AND entry = %s
				   AND ( phase IS NULL
				      OR phase IN ( 'failed', 'removed' )
				      OR ( phase IN ( 'queued', 'installing' ) AND phase_since < %s ) )",
				$now,
				$source,
				$entry,
				$stale
			);
		} else {
			$sql = $wpdb->prepare(
				"UPDATE {$table} SET phase = 'queued', phase_since = %s, error = NULL
				 WHERE source = %s AND entry = %s
				   AND ( phase IS NULL
				      OR ( phase = 'failed' AND ( retry_after IS NULL OR retry_after < %s ) )
				      OR ( phase IN ( 'queued', 'installing' ) AND phase_since < %s ) )",
				$now,
				$source,
				$entry,
				$now,
				$stale
			);
		}

		$wpdb->query( $sql );
		/* phpcs:enable */

		if ( $wpdb->rows_affected !== 1 ) {
			return false;
		}

		$this->flush();

		return true;
	}

	/**
	 * The coverage entries a failed install left behind, whose backoff has passed
	 *
	 * Candidates only — `claim()` is still what decides, so this staying slightly stale costs nothing.
	 *
	 * @return array<int, array{source: string, entry: string}>
	 *
	 * @since 7.0
	 */
	public function retryable_entries(): array {
		global $wpdb;

		$table = $this->schema->get_catalog_table();

		/* phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- the table name comes from Font_Schema; the value is prepared */
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT source, entry FROM {$table} WHERE coverage = 1 AND phase = 'failed' AND ( retry_after IS NULL OR retry_after < %s ) ORDER BY position ASC, entry ASC",
				gmdate( 'Y-m-d H:i:s' )
			),
			ARRAY_A
		);
		/* phpcs:enable */

		return array_map(
			static function ( array $row ): array {
				return [
					'source' => (string) $row['source'],
					'entry'  => (string) $row['entry'],
				];
			},
			(array) $rows
		);
	}

	/**
	 * Whether a source is still registered, so a caller can drop work rather than fail a row over the admin's own change
	 *
	 * @since 7.0
	 */
	public function is_registered( string $source ): bool {
		return $this->sources->get( $source ) !== null;
	}

	/**
	 * The download URL for one of an entry's files
	 *
	 * The published index names no host — that is what lets one signed root serve staging and production alike — so
	 * every blob URL is built from the registered record's root.
	 *
	 * @param array $file A `files` value carrying `remote_path`
	 *
	 * @since 7.0
	 */
	public function url_for( string $source, array $file ): ?string {
		$record = $this->sources->get( $source );

		if ( $record === null || ! isset( $file['remote_path'] ) || ! is_string( $file['remote_path'] ) ) {
			return null;
		}

		return $record->get_root_url() . 'files/' . ltrim( $file['remote_path'], '/' );
	}

	/**
	 * The URL of the entry file a row points at, when it points at one
	 *
	 * Same rule as `url_for()` — the root comes from the registered record, never from the index — which is why it
	 * lives here rather than in the one class that fetches entry files.
	 *
	 * @param array $row A catalog row carrying `entry_sha256`
	 *
	 * @since 7.0
	 */
	public function entry_url( string $source, array $row ): ?string {
		$record = $this->sources->get( $source );
		$sha256 = (string) ( $row['entry_sha256'] ?? '' );

		if ( $record === null || $sha256 === '' ) {
			return null;
		}

		return sprintf( '%sentries/%s/%s-%s.json', $record->get_root_url(), $source, (string) $row['entry'], $sha256 );
	}

	/**
	 * A third-party record's query args, so every request built from its root carries them
	 *
	 * @since 7.0
	 */
	public function request_args( string $source ): array {
		$record = $this->sources->get( $source );

		return $record === null ? [] : $record->get_request_args();
	}

	/**
	 * The preview faces for a catalog row, shaped like its `fonts` map
	 *
	 * @param array $fonts The decoded entry's `fonts` map, when the caller already has it. Without it only the
	 *                     Regular face is named: a list row deliberately does not carry `entry_json`, and a browse
	 *                     card shows one face per key anyway.
	 *
	 * @return array<string, array<string, string>> `{ font_key: { role: url } }`
	 *
	 * @since 7.0
	 */
	public function preview_urls( array $row, array $fonts = [] ): array {
		$preview = $row['preview'] ?? '';

		if ( ! is_string( $preview ) || $preview === '' ) {
			return [];
		}

		$record = $this->sources->get( (string) ( $row['source'] ?? '' ) );

		if ( $record === null ) {
			return [];
		}

		$urls = [];
		foreach ( $this->preview_faces( $row, $fonts ) as $font_key => $roles ) {
			foreach ( $roles as $role ) {
				$urls[ $font_key ][ $role ] = sprintf( '%sfiles/%s-%s-%s.woff2', $record->get_root_url(), $preview, $font_key, $role );
			}
		}

		return $urls;
	}

	/**
	 * Invalidate every cached catalog read
	 *
	 * A second stamp in the font group, so neither a sync nor a status write evicts the render path's font rows.
	 *
	 * @since 7.0
	 */
	public function flush(): void {
		wp_cache_set( static::CACHE_STAMP, microtime(), Font_Repository::CACHE_GROUP );
	}

	/**
	 * @since 7.0
	 */
	public function get_last_changed(): string {
		$last_changed = wp_cache_get( static::CACHE_STAMP, Font_Repository::CACHE_GROUP );

		if ( ! is_string( $last_changed ) || $last_changed === '' ) {
			$last_changed = microtime();
			wp_cache_set( static::CACHE_STAMP, $last_changed, Font_Repository::CACHE_GROUP );
		}

		return $last_changed;
	}

	/**
	 * Which font key / role pairs have a preview face
	 *
	 * From the entry's own `fonts` map when the caller decoded one; otherwise from the `font_keys` column, which is
	 * mirrored at sync precisely so this needs no entry.
	 *
	 * @return array<string, string[]>
	 *
	 * @since 7.0
	 */
	protected function preview_faces( array $row, array $fonts ): array {
		$faces = [];

		foreach ( $fonts as $font_key => $roles ) {
			$roles = array_diff( array_keys( (array) $roles ), Font_Sources::NON_ROLE_KEYS );

			if ( count( $roles ) > 0 ) {
				$faces[ (string) $font_key ] = array_values( $roles );
			}
		}

		if ( count( $faces ) > 0 ) {
			return $faces;
		}

		foreach ( array_filter( explode( ',', (string) ( $row['font_keys'] ?? '' ) ) ) as $key ) {
			$faces[ $key ] = [ 'R' ];
		}

		return $faces;
	}

	/**
	 * @since 7.0
	 */
	protected function cache_key( string $kind, array $parts ): string {
		return 'catalog:' . $kind . ':' . md5( (string) wp_json_encode( $parts ) ) . ':' . $this->get_last_changed();
	}

	/**
	 * Columns come back from MySQL as strings; the callers treat them as what they are
	 *
	 * @since 7.0
	 */
	protected function cast_row( array $row ): array {
		foreach ( [ 'coverage', 'position', 'size', 'files', 'always' ] as $column ) {
			if ( isset( $row[ $column ] ) ) {
				$row[ $column ] = (int) $row[ $column ];
			}
		}

		return $row;
	}
}
