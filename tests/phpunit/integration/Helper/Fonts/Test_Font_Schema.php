<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF\Tests\Integration\TestCase;
use GPDFAPI;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * The one suite allowed DDL
 *
 * WP_UnitTestCase turns CREATE TABLE into a TEMPORARY table, which shadows the real one for the connection, so
 * these cases drop both filters and work on the real tables. Its DDL commits the transaction, so rollback will not
 * clean up: tear_down() restores the filters, the canonical tables and the version by hand.
 *
 * @package   GFPDF\Helper\Fonts
 *
 * @group     helper
 * @group     fonts
 */
class Test_Font_Schema extends TestCase {

	/**
	 * @var Font_Schema
	 */
	public $schema;

	public function set_up(): void {
		parent::set_up();

		remove_filter( 'query', [ $this, '_create_temporary_tables' ] );
		remove_filter( 'query', [ $this, '_drop_temporary_tables' ] );

		$this->schema = new Font_Schema( GPDFAPI::get_log_class() );
	}

	public function tear_down(): void {
		$this->schema->drop();
		$this->schema->ensure();

		update_option( $this->schema::VERSION_OPTION, $this->schema->get_version(), true );

		if ( is_multisite() ) {
			update_site_option( $this->schema::VERSION_OPTION, $this->schema->get_version() );
		}

		add_filter( 'query', [ $this, '_create_temporary_tables' ] );
		add_filter( 'query', [ $this, '_drop_temporary_tables' ] );

		parent::tear_down();
	}

	public function test_the_bootstrap_left_the_tables_in_place() {
		$this->assertSame( [], $this->schema->get_missing_tables() );
		$this->assertSame( $this->schema->get_version(), get_option( $this->schema::VERSION_OPTION ) );
	}

	public function test_tables_are_created_from_scratch() {
		$this->schema->drop();

		$this->assertCount( count( $this->schema->get_tables() ), $this->schema->get_missing_tables() );

		$this->assertTrue( $this->schema->ensure() );
		$this->assertSame( [], $this->schema->get_missing_tables() );
	}

	public function test_db_delta_is_idempotent() {
		global $wpdb;

		$this->schema->drop();
		$this->schema->ensure();

		$before = $wpdb->get_results( 'DESCRIBE ' . $this->schema->get_font_table(), ARRAY_A );

		/* A second pass over an existing schema must report no changes at all */
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$changes = [];
		foreach ( $this->schema->get_table_definitions() as $sql ) {
			$changes[] = dbDelta( $sql );
		}

		$this->assertSame( [], array_merge( [], ...$changes ) );
		$this->assertSame( $before, $wpdb->get_results( 'DESCRIBE ' . $this->schema->get_font_table(), ARRAY_A ) );
	}

	public function test_a_current_version_short_circuits_ensure() {
		update_option( $this->schema::VERSION_OPTION, $this->schema->get_version(), true );

		$this->assertTrue( $this->schema->is_current() );

		/* Drop the tables behind its back: a current version means ensure() does no work and cannot notice */
		$this->schema->drop();
		update_option( $this->schema::VERSION_OPTION, $this->schema->get_version(), true );

		$this->assertTrue( $this->schema->ensure() );
		$this->assertNotSame( [], $this->schema->get_missing_tables() );
	}

	public function test_an_older_version_re_runs_db_delta() {
		$this->schema->drop();
		update_option( $this->schema::VERSION_OPTION, '6.9.0', true );

		$this->assertFalse( $this->schema->is_current() );
		$this->assertTrue( $this->schema->ensure() );
		$this->assertSame( [], $this->schema->get_missing_tables() );
	}

	public function test_the_prefix_is_network_wide_on_multisite() {
		global $wpdb;

		$expected = is_multisite() ? $wpdb->base_prefix : $wpdb->prefix;

		$this->assertSame( $expected, $this->schema->get_prefix() );
		$this->assertStringStartsWith( $expected, $this->schema->get_font_table() );
	}

	public function test_the_version_records_the_install_shape() {
		$expected = is_multisite() ? Font_Schema::VERSION . '-ms' : Font_Schema::VERSION;

		$this->assertSame( $expected, $this->schema->get_version() );
	}

	public function test_the_site_table_only_exists_on_multisite() {
		$this->assertSame( is_multisite(), in_array( $this->schema->get_site_table(), $this->schema->get_tables(), true ) );
	}

	public function test_the_ddl_runs_under_strict_sql_mode() {
		global $wpdb;

		$previous = $wpdb->get_var( 'SELECT @@SESSION.sql_mode' );
		$wpdb->query( "SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE'" );

		try {
			$this->schema->drop();

			$this->assertTrue( $this->schema->ensure() );
			$this->assertSame( [], $this->schema->get_missing_tables() );
		} finally {
			$wpdb->query( $wpdb->prepare( 'SET SESSION sql_mode = %s', $previous ) );
		}
	}

	public function test_a_failed_pass_backs_off_for_an_hour() {
		set_transient( Font_Schema::FAILED_TRANSIENT, time(), HOUR_IN_SECONDS );

		$this->schema->drop();

		$this->assertFalse( $this->schema->ensure() );
		$this->assertNotSame( [], $this->schema->get_missing_tables() );

		delete_transient( Font_Schema::FAILED_TRANSIENT );
	}

	public function test_dropping_removes_the_tables_and_both_version_copies() {
		$this->schema->drop();

		$this->assertFalse( get_option( Font_Schema::VERSION_OPTION ) );
		$this->assertNotSame( [], $this->schema->get_missing_tables() );

		if ( is_multisite() ) {
			$this->assertFalse( get_site_option( Font_Schema::VERSION_OPTION ) );
		}
	}
}
