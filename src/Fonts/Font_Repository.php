<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

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
 * shows cannot drift. In steady state it performs no filesystem operation: 6.x globbed the fonts directory on every
 * render, and on NFS-backed hosts each stat is a network round trip.
 *
 * The exception is `ensure_ready()`, which runs once per site and does scan the directory. It has to sit here
 * rather than on the admin upgrade routine: that routine only fires on an admin page load, so a site that
 * auto-updates and serves PDFs by REST or cron would render before it, and whichever request touches fonts first
 * pays for the migration instead. Once `gfpdf_db_version` is written it is one autoloaded option read forever.
 *
 * @package GFPDF\Fonts
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
	 * The literal words the `/fonts/{id}` route reserves, which are therefore keys nothing may mint
	 *
	 * Here rather than beside the route because a key outlives the route table: `/fonts/settings` is not registered
	 * yet, and a site that minted `settings` before it ships would own a font it could never edit or delete.
	 * `Rest_Custom_Fonts::id_route()` reads this same list, so the two can never disagree.
	 *
	 * @since 7.0
	 */
	public const RESERVED_ROUTE_KEYS = [ 'sources', 'status', 'settings', 'updates', 'browse', 'sync' ];

	/**
	 * The face roles a font row may carry, beside the `dict_*` line-break dictionaries
	 *
	 * @since 7.0
	 */
	public const FACE_ROLES = [ 'R', 'B', 'I', 'BI' ];

	/**
	 * What a font key may contain
	 *
	 * mPDF keys are plain array keys, so this is the plugin's rule rather than a format requirement. It is here,
	 * not on the model, because both the REST validation callback and the loose-font importer derive keys and must
	 * agree on what they are allowed to produce.
	 *
	 * @since 7.0
	 */
	public const KEY_CHARS = 'a-z0-9_\\-';

	/**
	 * @since 7.0
	 */
	public const KEY_PATTERN = '/^[' . self::KEY_CHARS . ']+$/';

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
	 * @var Font_Population_Pass[] Run in order by ensure_ready(), once per site
	 * @since 7.0
	 */
	protected $passes = [];

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
	 * Add a pass that populates the tables from something already on the site
	 *
	 * Added after construction rather than injected, because each pass reads through this repository — passing
	 * them to the constructor would be a cycle. Order is the order they run in.
	 *
	 * @since 7.0
	 */
	public function add_population_pass( Font_Population_Pass $pass ): void {
		$this->passes[] = $pass;
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

			foreach ( $this->passes as $pass ) {
				$pass->run();
			}

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
	 * @param bool $unlink_files Whether to remove the files from disk as well as the rows
	 *
	 * @since 7.0
	 */
	public function delete( string $font_key, bool $unlink_files = true ): bool {
		$font = $this->get( $font_key );

		if ( $font === null ) {
			return false;
		}

		$this->delete_rows( [ $font ], $unlink_files );

		return true;
	}

	/**
	 * Remove every row one catalogue entry installed
	 *
	 * Not a loop over `delete()`: that reads the whole table back to find each row and bumps the cache stamp on the
	 * way out, so an entry installed under several keys paid a full re-select per key. Here the rows are read once
	 * and the unlink pass runs after the last of them — which is also the only moment the "still recorded" guard
	 * tells the truth about a file two installs of the same family share.
	 *
	 * @return string[] The font keys removed
	 *
	 * @since 7.0
	 */
	public function delete_entry( string $source, string $entry, bool $unlink_files = true ): array {
		$rows = $this->rows_for_entry( $source, $entry );

		$this->delete_rows( $rows, $unlink_files );

		return array_keys( $rows );
	}

	/**
	 * The shared delete path: file rows first, then the site toggles and the font row, then the unlink
	 *
	 * A file is only ever unlinked once nothing records it, and a crash between the steps leaves rows that point at
	 * a file rather than a row pointing at nothing. One flush covers the whole set.
	 *
	 * @param array[] $fonts Rows as `all()` returns them
	 *
	 * @since 7.0
	 */
	protected function delete_rows( array $fonts, bool $unlink_files ): void {
		global $wpdb;

		if ( $fonts === [] ) {
			return;
		}

		$paths = [];

		/* phpcs:disable WordPress.DB.DirectDatabaseQuery -- the font tables have no core API */
		foreach ( $fonts as $font ) {
			$paths = array_merge( $paths, array_column( $font['files'], 'path' ) );

			$wpdb->delete( $this->schema->get_file_table(), [ 'font_id' => $font['id'] ] );

			if ( is_multisite() ) {
				$wpdb->delete( $this->schema->get_site_table(), [ 'font_id' => $font['id'] ] );
			}

			$wpdb->delete( $this->schema->get_font_table(), [ 'id' => $font['id'] ] );
		}
		/* phpcs:enable */

		$this->flush();

		if ( ! $unlink_files ) {
			return;
		}

		foreach ( array_unique( $paths ) as $path ) {
			$this->delete_file( (string) $path );
		}
	}

	/**
	 * The path one role of one font row records, or null when it records none
	 *
	 * A single-row read rather than a scan of `all()`: every write flushes the memo `all()` answers from, so a
	 * caller replacing several roles in a row would re-select both whole tables between each one.
	 *
	 * @since 7.0
	 */
	public function path_for_role( int $font_id, string $role ): ?string {
		global $wpdb;

		$file_table = $this->schema->get_file_table();

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name comes from Font_Schema */
		$path = (string) $wpdb->get_var( $wpdb->prepare( "SELECT path FROM {$file_table} WHERE font_id = %d AND role = %s", $font_id, $role ) );

		return $path !== '' ? $path : null;
	}

	/**
	 * The hash some file row claims for a path, or '' when no row records it
	 *
	 * The installer's cheapest "already on disk" answer, which is why it is a single indexed read and not a
	 * `claimed_filenames()`-shaped map: the caller has one path and wants one hash.
	 *
	 * @since 7.0
	 */
	public function recorded_hash( string $path ): string {
		global $wpdb;

		$file_table = $this->schema->get_file_table();

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name comes from Font_Schema */
		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT sha256 FROM {$file_table} WHERE path = %s LIMIT 1", $path ) );
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
		$path       = $this->path_for_role( $font_id, $role );

		if ( $path === null ) {
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
	 * The font and face role recording a path, or null when nothing does
	 *
	 * One indexed read like `recorded_hash()`, for the same reason: the caller has one path. `LIMIT 1` because two
	 * installs of a display entry share their files, and a shared file fills the same role in both.
	 *
	 * @return array{font_key: string, role: string}|null
	 *
	 * @since 7.0
	 */
	public function file_for_path( string $path ): ?array {
		global $wpdb;

		$font_table = $this->schema->get_font_table();
		$file_table = $this->schema->get_file_table();

		/* phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names come from Font_Schema */
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT f.font_key, ff.role
				   FROM {$file_table} ff
				   INNER JOIN {$font_table} f ON f.id = ff.font_id
				  WHERE ff.path = %s
				  LIMIT 1",
				$path
			),
			ARRAY_A
		);
		/* phpcs:enable */

		return $row === null ? null : [
			'font_key' => (string) $row['font_key'],
			'role'     => (string) $row['role'],
		];
	}

	/**
	 * Flag or clear every file row recording a path
	 *
	 * Both installs of a display entry are flagged together, which is why this keys on the path rather than the row.
	 *
	 * Only a row that actually changed flushes — `wpdb::update()` counts changed rows, not matched ones. The
	 * render-time path asks again on every render for as long as a file stays gone, and a flush there costs the
	 * whole font cache and a re-read of both tables.
	 *
	 * @since 7.0
	 */
	public function set_missing( string $path, bool $missing ): void {
		global $wpdb;

		/* phpcs:ignore WordPress.DB.DirectDatabaseQuery -- the font tables have no core API */
		$updated = $wpdb->update( $this->schema->get_file_table(), [ 'missing' => (int) $missing ], [ 'path' => $path ] );

		if ( ! $updated ) {
			return;
		}

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
		return in_array( $font_key, static::RESERVED_KEYS, true )
			|| in_array( $font_key, static::RESERVED_ROUTE_KEYS, true );
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
	 * Which site a row created right now belongs to
	 *
	 * Stated once because four places insert rows. Font *files* are network-global, so this is only ever about
	 * which site's Font Manager lists the row, never about which site can render it.
	 *
	 * @return int|null
	 *
	 * @since 7.0
	 */
	public function current_blog_id(): ?int {
		return is_multisite() ? get_current_blog_id() : null;
	}

	/**
	 * Every row one catalog entry installed, keyed by font key
	 *
	 * The install writes rows one file at a time and the entry's keys are only all present once the last one
	 * lands, so the caller that needs the whole set — the cache warm — asks for it here rather than accumulating
	 * it across calls it may not have made.
	 *
	 * @return array<string, array>
	 *
	 * @since 7.0
	 */
	public function rows_for_entry( string $source, string $entry ): array {
		$rows = [];

		foreach ( $this->all() as $font_key => $row ) {
			if ( (string) $row['source'] === $source && (string) ( $row['entry'] ?? '' ) === $entry ) {
				$rows[ (string) $font_key ] = $row;
			}
		}

		return $rows;
	}

	/**
	 * Every filename some font row already records
	 *
	 * The three passes that populate the tables — migration, legacy adoption, loose import — all need this to avoid
	 * registering a file twice, so it lives with the rows rather than in each of them.
	 *
	 * @return array<string, true>
	 *
	 * @since 7.0
	 */
	public function claimed_filenames(): array {
		$claimed = [];

		foreach ( $this->all() as $row ) {
			foreach ( $row['files'] as $file ) {
				$claimed[ (string) $file['path'] ] = true;
			}
		}

		return $claimed;
	}

	/**
	 * The font key a piece of text derives to
	 *
	 * The legacy rule first — lowercase, spaces removed — so a template written against 6.x keeps resolving, and
	 * anything it leaves outside `KEY_PATTERN` collapses to `-` rather than vanishing, since silently deleting
	 * characters can reduce two different names to one key. `font` is the floor: a label that is entirely
	 * non-Latin still has to produce something `unique_key()` can suffix.
	 *
	 * Not `Model_Custom_Fonts::get_font_short_name()`, which is only the first line of this and is frozen: the 6.x
	 * ids in the wild were minted by it and `Controller_Upgrade_Routines` re-derives them to match, so it cannot
	 * start collapsing characters now.
	 *
	 * @since 7.0
	 */
	public function derive_key( string $text ): string {
		$legacy = mb_strtolower( str_replace( ' ', '', $text ), 'UTF-8' );

		if ( preg_match( static::KEY_PATTERN, $legacy ) ) {
			return $legacy;
		}

		$collapsed = trim( strtolower( (string) preg_replace( '/[^A-Za-z0-9_\-]+/', '-', $legacy ) ), '-' );

		return $collapsed !== '' ? $collapsed : 'font';
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
	public function get_last_changed(): string {
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
