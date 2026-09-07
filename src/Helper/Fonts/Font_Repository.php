<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF\Helper\Helper_Misc;
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
 * The only reader and writer of the font tables
 *
 * `all()` is the single read behind the registry and the Font Manager alike, so what mPDF registers and what the UI
 * shows cannot drift. It performs no filesystem operation: 6.x globbed the fonts directory on every render, and on
 * NFS-backed hosts each stat is a network round trip.
 *
 * @package GFPDF\Helper\Fonts
 *
 * @since 7.0
 */
class Font_Repository {

	/**
	 * The object cache group the font rows live in
	 *
	 * @since 7.0
	 */
	public const CACHE_GROUP = 'gfpdf_fonts';

	/**
	 * Bumped by every write, so invalidation never enumerates keys
	 *
	 * @since 7.0
	 */
	public const CACHE_STAMP = 'fonts:last_changed';

	/**
	 * The lock the mutating half of ensure_ready() runs under
	 *
	 * @since 7.0
	 */
	public const MIGRATION_LOCK = 'fonts_migrating';

	/**
	 * Font keys nothing may claim
	 *
	 * The bundled keys, the generic families `SetFont()` resolves before any lookup, and the Adobe CJK families mPDF
	 * checks ahead of the font map — a row under any of them would be unreachable. The PDF core-14 names are NOT
	 * here: an upload or import may claim `arial`, and the built-in alias defers to it (§4.1).
	 *
	 * @since 7.0
	 */
	public const RESERVED_KEYS = [
		'gfpdf-arimo',
		'gfpdf-dejavu-symbols',
		'sans',
		'sans-serif',
		'serif',
		'mono',
		'monospace',
		'gb',
		'big5',
		'sjis',
		'uhc',
		'*',
	];

	/**
	 * The face roles a font row may carry, beside the `dict_*` line-break dictionaries
	 *
	 * @since 7.0
	 */
	public const FACE_ROLES = [ 'R', 'B', 'I', 'BI' ];

	/**
	 * The 6.x face keys, mapped to the roles that replace them
	 *
	 * The public API, `Helper_Data::customFontData` and every add-on still speak the left-hand side, so this
	 * outlives the one-time migration.
	 *
	 * @since 7.0
	 */
	public const LEGACY_FACE_ROLES = [
		'regular'     => 'R',
		'bold'        => 'B',
		'italics'     => 'I',
		'bolditalics' => 'BI',
	];

	/**
	 * @var Font_Schema
	 * @since 7.0
	 */
	protected $schema;

	/**
	 * @var Font_Migration
	 * @since 7.0
	 */
	protected $migration;

	/**
	 * @var Font_Lock
	 * @since 7.0
	 */
	protected $lock;

	/**
	 * @var Helper_Misc
	 * @since 7.0
	 */
	protected $misc;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	/**
	 * @var string Absolute path to the uploads fonts directory, with a trailing slash
	 * @since 7.0
	 */
	protected $font_dir;

	/**
	 * @var bool Whether ensure_ready() has already run on this request
	 * @since 7.0
	 */
	protected $is_ready = false;

	/**
	 * @var array{stamp: string, rows: array}|null The rows this request has already decoded
	 * @since 7.0
	 */
	protected $memo;

	public function __construct(
		Font_Schema $schema,
		Font_Migration $migration,
		Font_Lock $lock,
		Helper_Misc $misc,
		LoggerInterface $log,
		string $font_dir
	) {
		$this->schema    = $schema;
		$this->migration = $migration;
		$this->lock      = $lock;
		$this->misc      = $misc;
		$this->log       = $log;
		$this->font_dir  = trailingslashit( $font_dir );

		wp_cache_add_global_groups( [ static::CACHE_GROUP ] );
	}

	/**
	 * Create the tables and migrate 6.x records, once
	 *
	 * Runs before this repository's first query on any request, so nothing on the render path depends on the admin
	 * upgrade routine having fired. Steady state is one autoloaded option read.
	 *
	 * `gfpdf_db_version` is written only after every step succeeds: a pass that throws is retried on the next call
	 * rather than being closed by a version bump.
	 *
	 * @since 7.0
	 */
	public function ensure_ready(): bool {
		if ( $this->is_ready ) {
			return true;
		}

		if ( $this->schema->is_current() ) {
			$this->is_ready = true;

			return true;
		}

		if ( ! $this->schema->ensure() ) {
			return false;
		}

		/* The fonts directory and the tables are network-global, so only one site may mutate them at a time */
		if ( ! $this->lock->acquire( static::MIGRATION_LOCK, 5 * MINUTE_IN_SECONDS ) ) {
			/* The loser renders with whatever is already registered rather than waiting */
			return false;
		}

		/*
		 * The tables exist from here, and the migration reads through `all()`. Mark the repository ready first, or
		 * that read re-enters this method and runs a second, pointless dbDelta before the lock turns it back.
		 */
		$this->is_ready = true;

		try {
			$this->migration->from_option( $this );

			$this->schema->mark_current();
		} catch ( \Throwable $e ) {
			/* Leave the version unwritten so the next call retries, survivors and all */
			$this->is_ready = false;

			$this->log->error( 'Could not migrate the 6.x font records', [ 'exception' => $e->getMessage() ] );
		} finally {
			$this->lock->release( static::MIGRATION_LOCK );
		}

		return $this->is_ready;
	}

	/**
	 * Every font row, with its files, keyed by `font_key`
	 *
	 * Two statements, cached together. On multisite this site's visibility toggles are cached under their own
	 * blog-scoped key and merged in PHP, because the cache group is global.
	 *
	 * @since 7.0
	 */
	public function all(): array {
		$this->ensure_ready();

		$last_changed = $this->get_last_changed();

		/* Decoding the rows is the expensive half, so a second call in the same request reuses the first's work */
		if ( $this->memo !== null && $this->memo['stamp'] === $last_changed ) {
			return $this->apply_site_visibility( $this->memo['rows'], $last_changed );
		}

		$key    = 'fonts:all:' . $last_changed;
		$cached = wp_cache_get( $key, static::CACHE_GROUP );

		if ( is_array( $cached ) ) {
			$this->memo = [
				'stamp' => $last_changed,
				'rows'  => $cached,
			];

			return $this->apply_site_visibility( $cached, $last_changed );
		}

		global $wpdb;

		$font_table = $this->schema->get_font_table();
		$file_table = $this->schema->get_file_table();

		/* phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- table names come from Font_Schema; results are cached above */
		$fonts = $wpdb->get_results( "SELECT * FROM {$font_table} ORDER BY id ASC", ARRAY_A );
		$files = $wpdb->get_results( "SELECT * FROM {$file_table} ORDER BY id ASC", ARRAY_A );
		/* phpcs:enable */

		$rows = [];
		foreach ( (array) $fonts as $font ) {
			$font['id']          = (int) $font['id'];
			$font['coverage']    = (int) $font['coverage'];
			$font['use_otl']     = (int) $font['use_otl'];
			$font['use_kashida'] = (int) $font['use_kashida'];
			$font['blog_id']     = $font['blog_id'] === null ? null : (int) $font['blog_id'];
			$font['meta']        = $font['meta'] === null ? [] : (array) json_decode( $font['meta'], true );
			$font['files']       = [];

			$rows[ $font['font_key'] ] = $font;
		}

		$by_id = [];
		foreach ( $rows as $font_key => $font ) {
			$by_id[ $font['id'] ] = $font_key;
		}

		foreach ( (array) $files as $file ) {
			$font_id = (int) $file['font_id'];
			if ( ! isset( $by_id[ $font_id ] ) ) {
				continue;
			}

			$file['id']      = (int) $file['id'];
			$file['font_id'] = $font_id;
			$file['size']    = (int) $file['size'];
			$file['missing'] = (int) $file['missing'];

			$rows[ $by_id[ $font_id ] ]['files'][ $file['role'] ] = $file;
		}

		wp_cache_set( $key, $rows, static::CACHE_GROUP );

		$this->memo = [
			'stamp' => $last_changed,
			'rows'  => $rows,
		];

		return $this->apply_site_visibility( $rows, $last_changed );
	}

	/**
	 * A single row by its mPDF key, or null
	 *
	 * @since 7.0
	 */
	public function get( string $font_key ): ?array {
		$rows = $this->all();

		return $rows[ $font_key ] ?? null;
	}

	/**
	 * Insert a font row and its files
	 *
	 * @param array $font `font_key`, `label`, `source`, and optionally `entry`, `coverage`, `meta`, `blog_id`,
	 *                    `use_otl`, `use_kashida`, `version`, `files` (role => [`path`, `size`, `sha256`,
	 *                    `variant`, `missing`])
	 *
	 * @return int The new row's id, or 0 on failure
	 *
	 * @since 7.0
	 */
	public function insert( array $font ): int {
		global $wpdb;

		$now = current_time( 'mysql' );

		$data = [
			'font_key'    => (string) ( $font['font_key'] ?? '' ),
			'label'       => (string) ( $font['label'] ?? '' ),
			'source'      => (string) ( $font['source'] ?? 'custom' ),
			'entry'       => $font['entry'] ?? null,
			'coverage'    => (int) ( $font['coverage'] ?? 0 ),
			'meta'        => isset( $font['meta'] ) && $font['meta'] !== [] ? (string) wp_json_encode( $font['meta'] ) : null,
			'blog_id'     => $font['blog_id'] ?? null,
			'use_otl'     => (int) ( $font['use_otl'] ?? 0 ),
			'use_kashida' => (int) ( $font['use_kashida'] ?? 0 ),
			'version'     => $font['version'] ?? null,
			'created'     => $now,
			'updated'     => $now,
		];

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the font tables have no core API */
		$inserted = $wpdb->insert( $this->schema->get_font_table(), $data );

		if ( ! $inserted ) {
			$this->log->error( 'Could not insert the font row', [ 'font' => $data['font_key'] ] );

			return 0;
		}

		$font_id = (int) $wpdb->insert_id;

		foreach ( (array) ( $font['files'] ?? [] ) as $role => $file ) {
			$this->insert_file( $font_id, (string) $role, $file );
		}

		$this->flush();

		return $font_id;
	}

	/**
	 * Replace a font row's columns, leaving its files alone
	 *
	 * @since 7.0
	 */
	public function update( int $font_id, array $font ): bool {
		global $wpdb;

		$data = array_intersect_key(
			$font,
			array_flip( [ 'font_key', 'label', 'source', 'entry', 'coverage', 'blog_id', 'use_otl', 'use_kashida', 'version' ] )
		);

		if ( isset( $font['meta'] ) ) {
			$data['meta'] = $font['meta'] === [] ? null : (string) wp_json_encode( $font['meta'] );
		}

		if ( $data === [] ) {
			return false;
		}

		$data['updated'] = current_time( 'mysql' );

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the font tables have no core API */
		$updated = $wpdb->update( $this->schema->get_font_table(), $data, [ 'id' => $font_id ] );

		$this->flush();

		return $updated !== false;
	}

	/**
	 * Turn a 6.x-shaped record's face paths into file rows
	 *
	 * 6.x stored absolute paths, which break on every site move; the rows keep the basename and the directory comes
	 * from the package layer. A face that is not on disk is recorded `missing` rather than given a fake size.
	 *
	 * @since 7.0
	 */
	public function build_file_rows( array $font ): array {
		$files = [];

		foreach ( static::LEGACY_FACE_ROLES as $face => $role ) {
			if ( empty( $font[ $face ] ) ) {
				continue;
			}

			$path    = basename( (string) $font[ $face ] );
			$on_disk = is_file( $this->font_dir . $path );

			$files[ $role ] = [
				'path'    => $path,
				'size'    => $on_disk ? (int) filesize( $this->font_dir . $path ) : 0,
				'missing' => $on_disk ? 0 : 1,
			];
		}

		return $files;
	}

	/**
	 * Add or replace one file row
	 *
	 * @since 7.0
	 */
	public function insert_file( int $font_id, string $role, array $file ): bool {
		global $wpdb;

		if ( ! $this->is_valid_role( $role ) ) {
			$this->log->error( 'Refusing to record a font file under an unknown role', [ 'role' => $role ] );

			return false;
		}

		$data = [
			'font_id' => $font_id,
			'role'    => $role,
			'variant' => $file['variant'] ?? null,
			'path'    => (string) ( $file['path'] ?? '' ),
			'sha256'  => $file['sha256'] ?? null,
			'size'    => (int) ( $file['size'] ?? 0 ),
			'missing' => (int) ( $file['missing'] ?? 0 ),
		];

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the font tables have no core API */
		$wpdb->delete(
			$this->schema->get_file_table(),
			[
				'font_id' => $font_id,
				'role'    => $role,
			]
		);

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the font tables have no core API */
		$inserted = $wpdb->insert( $this->schema->get_file_table(), $data );

		$this->flush();

		return (bool) $inserted;
	}

	/**
	 * Remove a font row, its files and site toggles, then the files no surviving row records
	 *
	 * The shared delete path: file rows first, then the font row, then the unlink — so a file is only ever unlinked
	 * once nothing records it, and a crash between the steps leaves rows that point at a file rather than a row
	 * pointing at nothing.
	 *
	 * @param bool $unlink_files Whether to remove the files from disk as well as the rows
	 *
	 * @since 7.0
	 */
	public function delete( string $font_key, bool $unlink_files = true ): bool {
		global $wpdb;

		$font = $this->get( $font_key );
		if ( $font === null ) {
			return false;
		}

		$paths = array_values( array_unique( array_column( $font['files'], 'path' ) ) );

		/* phpcs:disable WordPress.DB.DirectDatabaseQuery -- the font tables have no core API */
		$wpdb->delete( $this->schema->get_file_table(), [ 'font_id' => $font['id'] ] );

		if ( is_multisite() ) {
			$wpdb->delete( $this->schema->get_site_table(), [ 'font_id' => $font['id'] ] );
		}

		$wpdb->delete( $this->schema->get_font_table(), [ 'id' => $font['id'] ] );
		/* phpcs:enable */

		$this->flush();

		if ( $unlink_files ) {
			foreach ( $paths as $path ) {
				$this->delete_file( (string) $path );
			}
		}

		return true;
	}

	/**
	 * Remove one file row, then the file itself when nothing else records it
	 *
	 * Row first, then the unlink — the same order as `delete()`, so the "still recorded" guard sees the truth.
	 *
	 * @since 7.0
	 */
	public function delete_file_row( int $font_id, string $role, bool $unlink_file = true ): bool {
		global $wpdb;

		$file_table = $this->schema->get_file_table();

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name comes from Font_Schema */
		$path = (string) $wpdb->get_var( $wpdb->prepare( "SELECT path FROM {$file_table} WHERE font_id = %d AND role = %s", $font_id, $role ) );

		if ( $path === '' ) {
			return false;
		}

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the font tables have no core API */
		$wpdb->delete(
			$file_table,
			[
				'font_id' => $font_id,
				'role'    => $role,
			]
		);

		$this->flush();

		if ( $unlink_file ) {
			$this->delete_file( $path );
		}

		return true;
	}

	/**
	 * Unlink one font file, unless a surviving file row still records it
	 *
	 * Two installs of one display entry share files, so the `path` decides, not a counter.
	 *
	 * @since 7.0
	 */
	public function delete_file( string $path ): bool {
		global $wpdb;

		$file_table = $this->schema->get_file_table();

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name comes from Font_Schema */
		$still_recorded = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$file_table} WHERE path = %s", $path ) );

		if ( $still_recorded > 0 ) {
			return false;
		}

		$result = $this->misc->unlink( $this->font_dir . $path );

		return $result === true;
	}

	/**
	 * Flag or clear every file row recording a path
	 *
	 * Both installs of a display entry are flagged together, which is why this keys on the path rather than the row.
	 *
	 * @since 7.0
	 */
	public function set_missing( string $path, bool $missing ): void {
		global $wpdb;

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the font tables have no core API */
		$wpdb->update( $this->schema->get_file_table(), [ 'missing' => (int) $missing ], [ 'path' => $path ] );

		$this->flush();
	}

	/**
	 * Reconcile the file rows against the disk
	 *
	 * One `scandir()`, then one `filesize()` per distinct path. Rows are never deleted: a file may come back, and
	 * the flag is cleared when it does.
	 *
	 * @return array{missing: string[], restored: string[]}
	 *
	 * @since 7.0
	 */
	public function verify(): array {
		$on_disk = is_dir( $this->font_dir ) ? scandir( $this->font_dir ) : [];
		$on_disk = array_flip( is_array( $on_disk ) ? $on_disk : [] );

		$missing  = [];
		$restored = [];
		$seen     = [];

		foreach ( $this->all() as $font ) {
			foreach ( $font['files'] as $file ) {
				$path = (string) $file['path'];

				if ( isset( $seen[ $path ] ) ) {
					continue;
				}
				$seen[ $path ] = true;

				$present = isset( $on_disk[ $path ] )
					&& is_file( $this->font_dir . $path )
					&& (int) filesize( $this->font_dir . $path ) === (int) $file['size'];

				if ( ! $present && (int) $file['missing'] === 0 ) {
					$this->set_missing( $path, true );
					$missing[] = $path;
				} elseif ( $present && (int) $file['missing'] === 1 ) {
					$this->set_missing( $path, false );
					$restored[] = $path;
				}
			}
		}

		return [
			'missing'  => $missing,
			'restored' => $restored,
		];
	}

	/**
	 * Whether nothing may claim this key
	 *
	 * @since 7.0
	 */
	public function is_key_reserved( string $font_key ): bool {
		return in_array( $font_key, static::RESERVED_KEYS, true );
	}

	/**
	 * Whether a key is reserved, or already taken by a row
	 *
	 * @since 7.0
	 */
	public function is_key_available( string $font_key ): bool {
		return ! $this->is_key_reserved( $font_key ) && $this->get( $font_key ) === null;
	}

	/**
	 * Derive a free key, suffixing a taken one
	 *
	 * One rule everywhere: when the key is taken or reserved, append a short random suffix and log both spellings,
	 * so every valid file ends up rendered and listed.
	 *
	 * @since 7.0
	 */
	public function unique_key( string $font_key ): string {
		if ( $this->is_key_available( $font_key ) ) {
			return $font_key;
		}

		do {
			$candidate = $font_key . '-' . wp_generate_password( 5, false, false );
		} while ( ! $this->is_key_available( $candidate ) );

		$this->log->notice(
			'Font key already taken, using a suffixed key',
			[
				'requested' => $font_key,
				'used'      => $candidate,
			]
		);

		return $candidate;
	}

	/**
	 * Show or hide a network font on one site
	 *
	 * No row means enabled, so the toggle only ever writes the exception.
	 *
	 * @since 7.0
	 */
	public function set_site_enabled( int $font_id, int $blog_id, bool $enabled ): void {
		global $wpdb;

		if ( ! is_multisite() ) {
			return;
		}

		/* phpcs:disable WordPress.DB.DirectDatabaseQuery -- the font tables have no core API */
		if ( $enabled ) {
			$wpdb->delete(
				$this->schema->get_site_table(),
				[
					'font_id' => $font_id,
					'blog_id' => $blog_id,
				]
			);
		} else {
			$wpdb->replace(
				$this->schema->get_site_table(),
				[
					'font_id' => $font_id,
					'blog_id' => $blog_id,
					'enabled' => 0,
				]
			);
		}
		/* phpcs:enable */

		$this->flush();
	}

	/**
	 * Drop a deleted site's visibility rows
	 *
	 * @since 7.0
	 */
	public function delete_site_rows( int $blog_id ): void {
		global $wpdb;

		if ( ! is_multisite() ) {
			return;
		}

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the font tables have no core API */
		$wpdb->delete( $this->schema->get_site_table(), [ 'blog_id' => $blog_id ] );

		$this->flush();
	}

	/**
	 * @since 7.0
	 */
	public function get_font_dir(): string {
		return $this->font_dir;
	}

	/**
	 * @since 7.0
	 */
	public function get_schema(): Font_Schema {
		return $this->schema;
	}

	/**
	 * Invalidate every cached read
	 *
	 * @since 7.0
	 */
	public function flush(): void {
		$this->memo = null;

		wp_cache_set( static::CACHE_STAMP, microtime(), static::CACHE_GROUP );
	}

	/**
	 * @since 7.0
	 */
	protected function get_last_changed(): string {
		$last_changed = wp_cache_get( static::CACHE_STAMP, static::CACHE_GROUP );

		if ( ! is_string( $last_changed ) || $last_changed === '' ) {
			$last_changed = microtime();
			wp_cache_set( static::CACHE_STAMP, $last_changed, static::CACHE_GROUP );
		}

		return $last_changed;
	}

	/**
	 * Merge this site's visibility toggles onto the network rows
	 *
	 * The cache group is global, so the toggles need their own blog-scoped key or a `switch_to_blog()` would read
	 * another site's.
	 *
	 * @since 7.0
	 */
	protected function apply_site_visibility( array $rows, string $last_changed ): array {
		if ( ! is_multisite() || $rows === [] ) {
			return $rows;
		}

		$blog_id = get_current_blog_id();
		$key     = 'fonts:site:' . $blog_id . ':' . $last_changed;
		$hidden  = wp_cache_get( $key, static::CACHE_GROUP );

		if ( ! is_array( $hidden ) ) {
			global $wpdb;

			$site_table = $this->schema->get_site_table();

			/* phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name comes from Font_Schema; result is cached */
			$hidden = $wpdb->get_col( $wpdb->prepare( "SELECT font_id FROM {$site_table} WHERE blog_id = %d AND enabled = 0", $blog_id ) );
			$hidden = array_map( 'intval', (array) $hidden );

			wp_cache_set( $key, $hidden, static::CACHE_GROUP );
		}

		$hidden = array_flip( $hidden );

		foreach ( $rows as $font_key => $font ) {
			$rows[ $font_key ]['enabled'] = ! isset( $hidden[ $font['id'] ] );
		}

		return $rows;
	}

	/**
	 * `dict_*` is validated by prefix, consistent with `source` being free-form
	 *
	 * @since 7.0
	 */
	protected function is_valid_role( string $role ): bool {
		return in_array( $role, static::FACE_ROLES, true ) || strpos( $role, 'dict_' ) === 0;
	}
}
