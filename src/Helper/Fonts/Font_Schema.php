<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

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
 * Creates and upgrades the font tables
 *
 * `dbDelta()` is the create-from-scratch path and never drops or renames a column. Anything it cannot express is a
 * `MIGRATIONS` entry, applied in order when the stored version is older than the one it is keyed on.
 *
 * @package GFPDF\Helper\Fonts
 *
 * @since 7.0
 */
class Font_Schema {

	/**
	 * Bump whenever the table definitions below change, or a MIGRATIONS entry is added
	 *
	 * @since 7.0
	 */
	public const VERSION = '7.0.0';

	/**
	 * The option holding the schema version this site last created, GF-style
	 *
	 * @since 7.0
	 */
	public const VERSION_OPTION = 'gfpdf_db_version';

	/**
	 * Short-circuits ensure() after a failed pass, so a site whose DB user cannot CREATE TABLE retries hourly
	 * rather than on every request
	 *
	 * @since 7.0
	 */
	public const FAILED_TRANSIENT = 'gfpdf_font_schema_failed';

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	public function __construct( LoggerInterface $log ) {
		$this->log = $log;
	}

	/**
	 * Ordered ALTERs for changes dbDelta cannot make, keyed on the version that introduced them
	 *
	 * Each callable receives this class and runs only when the stored version is older than its key. Keep them in
	 * ascending version order.
	 *
	 * @return array<string, callable>
	 *
	 * @since 7.0
	 */
	public function get_migrations(): array {
		return [];
	}

	/**
	 * The table prefix the font tables live under
	 *
	 * One set of tables per network, matching the network-global fonts directory.
	 *
	 * @since 7.0
	 */
	public function get_prefix(): string {
		global $wpdb;

		return is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;
	}

	/**
	 * @since 7.0
	 */
	public function get_font_table(): string {
		return $this->get_prefix() . 'gravitypdf_font';
	}

	/**
	 * @since 7.0
	 */
	public function get_file_table(): string {
		return $this->get_prefix() . 'gravitypdf_font_file';
	}

	/**
	 * @since 7.0
	 */
	public function get_site_table(): string {
		return $this->get_prefix() . 'gravitypdf_font_site';
	}

	/**
	 * Every table this schema owns, in creation order
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	public function get_tables(): array {
		$tables = [
			$this->get_font_table(),
			$this->get_file_table(),
		];

		if ( is_multisite() ) {
			$tables[] = $this->get_site_table();
		}

		return $tables;
	}

	/**
	 * The version string stored for the current install shape
	 *
	 * The `-ms` suffix means a single site converted to a network after its 7.0 upgrade mismatches and creates the
	 * site table; an equal-version compare gated behind is_multisite() never would.
	 *
	 * @since 7.0
	 */
	public function get_version(): string {
		return is_multisite() ? static::VERSION . '-ms' : static::VERSION;
	}

	/**
	 * Whether the schema this site holds is already current
	 *
	 * Reads the site option first, then the network copy on multisite, so dbDelta runs once per network rather
	 * than once per sub-site.
	 *
	 * @since 7.0
	 */
	public function is_current(): bool {
		if ( get_option( static::VERSION_OPTION ) === $this->get_version() ) {
			return true;
		}

		return is_multisite() && get_site_option( static::VERSION_OPTION ) === $this->get_version();
	}

	/**
	 * Record that this site's schema is current
	 *
	 * Multisite keeps both copies: the per-site one is autoloaded and read first, the network one keeps a second
	 * sub-site from re-running dbDelta.
	 *
	 * @since 7.0
	 */
	public function mark_current(): void {
		update_option( static::VERSION_OPTION, $this->get_version(), true );

		if ( is_multisite() ) {
			update_site_option( static::VERSION_OPTION, $this->get_version() );
		}
	}

	/**
	 * Create or upgrade the font tables, and verify they exist
	 *
	 * Idempotent: a call on a current schema does nothing but one option read.
	 *
	 * @return bool Whether the tables are present and current
	 *
	 * @since 7.0
	 */
	public function ensure(): bool {
		if ( $this->is_current() ) {
			return true;
		}

		if ( get_transient( static::FAILED_TRANSIENT ) ) {
			return false;
		}

		$stored   = (string) get_option( static::VERSION_OPTION, '' );
		$messages = $this->run_db_delta();

		foreach ( $this->get_migrations() as $version => $migration ) {
			if ( $stored !== '' && version_compare( $this->strip_suffix( $stored ), $version, '<' ) ) {
				$migration( $this );
			}
		}

		$missing = $this->get_missing_tables();
		if ( count( $missing ) > 0 ) {
			$this->log->error(
				'Gravity PDF could not create its font tables',
				[
					'missing'  => $missing,
					'messages' => $messages,
				]
			);

			set_transient( static::FAILED_TRANSIENT, time(), HOUR_IN_SECONDS );

			return false;
		}

		delete_transient( static::FAILED_TRANSIENT );

		return true;
	}

	/**
	 * Which of this schema's tables the database does not have
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	public function get_missing_tables(): array {
		global $wpdb;

		$missing = [];
		foreach ( $this->get_tables() as $table ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

			if ( $found !== $table ) {
				$missing[] = $table;
			}
		}

		return $missing;
	}

	/**
	 * @return string[] dbDelta's own messages, for the log when a table doesn't appear
	 *
	 * @since 7.0
	 */
	protected function run_db_delta(): array {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$messages = [];
		foreach ( $this->get_table_definitions() as $sql ) {
			$messages[] = dbDelta( $sql );
		}

		return array_merge( [], ...$messages );
	}

	/**
	 * Drop every table this schema owns
	 *
	 * @since 7.0
	 */
	public function drop(): void {
		global $wpdb;

		foreach ( $this->get_tables() as $table ) {
			/* phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names come from this class */
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		delete_option( static::VERSION_OPTION );

		if ( is_multisite() ) {
			delete_site_option( static::VERSION_OPTION );
		}
	}

	/**
	 * The CREATE TABLE statements, one per table
	 *
	 * dbDelta parses these itself and silently skips a line it cannot read, so the formatting is load-bearing: two
	 * spaces after `PRIMARY KEY`, no space before an index's `(`, and `UNIQUE KEY` rather than `UNIQUE INDEX`.
	 * Every DATETIME defaults to WP's sentinel, which is valid under NO_ZERO_DATE, and is written in PHP —
	 * dbDelta cannot parse an `ON UPDATE` clause.
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	public function get_table_definitions(): array {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$font_table      = $this->get_font_table();
		$file_table      = $this->get_file_table();

		$sql = [];

		$sql[] = "CREATE TABLE {$font_table} (
			id bigint(20) unsigned NOT NULL auto_increment,
			font_key varchar(191) NOT NULL default '',
			label varchar(255) NOT NULL default '',
			source varchar(32) NOT NULL default '',
			entry varchar(64) default NULL,
			coverage tinyint(1) NOT NULL default 0,
			meta longtext,
			blog_id bigint(20) unsigned default NULL,
			use_otl tinyint(3) unsigned NOT NULL default 0,
			use_kashida tinyint(3) unsigned NOT NULL default 0,
			version varchar(32) default NULL,
			created datetime NOT NULL default '1000-01-01 00:00:00',
			updated datetime NOT NULL default '1000-01-01 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY font_key (font_key),
			KEY coverage (coverage),
			KEY source_entry (source,entry)
		) ENGINE=InnoDB {$charset_collate};";

		$sql[] = "CREATE TABLE {$file_table} (
			id bigint(20) unsigned NOT NULL auto_increment,
			font_id bigint(20) unsigned NOT NULL default 0,
			role varchar(16) NOT NULL default '',
			variant varchar(32) default NULL,
			path varchar(255) NOT NULL default '',
			sha256 char(64) default NULL,
			size int(10) unsigned NOT NULL default 0,
			missing tinyint(1) NOT NULL default 0,
			PRIMARY KEY  (id),
			UNIQUE KEY font_id_role (font_id,role),
			KEY path (path(191))
		) ENGINE=InnoDB {$charset_collate};";

		if ( is_multisite() ) {
			$site_table = $this->get_site_table();

			$sql[] = "CREATE TABLE {$site_table} (
				font_id bigint(20) unsigned NOT NULL default 0,
				blog_id bigint(20) unsigned NOT NULL default 0,
				enabled tinyint(1) NOT NULL default 1,
				PRIMARY KEY  (font_id,blog_id),
				KEY blog_id (blog_id)
			) ENGINE=InnoDB {$charset_collate};";
		}

		return $sql;
	}

	/**
	 * @since 7.0
	 */
	protected function strip_suffix( string $version ): string {
		return str_replace( '-ms', '', $version );
	}
}
