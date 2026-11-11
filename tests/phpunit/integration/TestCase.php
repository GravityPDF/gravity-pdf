<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Integration;

use GFPDF\Tests\Concerns\HasGfpdfFixtures;
use GFPDF\Tests\Concerns\UsesFactory;
use WP_UnitTestCase;

abstract class TestCase extends WP_UnitTestCase {

	use HasGfpdfFixtures;
	use UsesFactory;

	public function set_up(): void {
		parent::set_up();
		$this->gfpdf()->data->form_settings = [];

		/* Signing grants are request-scoped, so give each test a fresh registry */
		$this->gfpdf()->singleton->add_class( new \GFPDF\Model\Model_Signed_Url_Trust() );

		/*
		 * The gfpdf_settings_user_data_{user ID} transient is written by Test_Options_API
		 * and a few others. Single-site relies on the WP test transaction to
		 * roll it back, but a persistent object cache (Redis/Memcache) bypasses
		 * that transaction and the value survives across tests, causing
		 * is_gfpdf_page()-driven branches to misbehave. Promote the clean-up
		 * that Test_PDF / Test_Model_Pdf already do ad-hoc to a blanket reset.
		 */
		delete_transient( 'gfpdf_settings_user_data_' . get_current_user_id() );

		/* Deprecation holds its detections for the request; a test process is many requests' worth of site state */
		\GFPDF\Statics\Deprecation::flush_cache();
	}

	public function tear_down(): void {
		$this->remove_pdf_cache();
		parent::tear_down();
	}

	/**
	 * The test suite turns this into DROP TEMPORARY TABLE, so the test's transaction survives
	 */
	protected function drop_site_options_table( int $site_id ): void {
		global $wpdb;

		$wpdb->query( 'DROP TABLE ' . $wpdb->get_blog_prefix( $site_id ) . 'options' ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Run the callback with every Model_Site_Options read failing, as a database error would
	 *
	 * @return mixed What the callback returns
	 */
	protected function with_failing_site_option_reads( callable $callback ) {
		$fail = static function ( $query ) {
			return self::is_site_option_query( $query ) || ( is_string( $query ) && strpos( $query, 'SHOW TABLES' ) === 0 ) ? 'SELECT * FROM gpdf_no_such_table' : $query;
		};

		add_filter( 'query', $fail, 20 );
		try {
			return $this->without_db_errors( $callback );
		} finally {
			remove_filter( 'query', $fail, 20 );
		}
	}

	/**
	 * @return string[] The Model_Site_Options queries the callback ran
	 */
	protected function site_option_queries( callable $callback ): array {
		$queries = [];
		$capture = static function ( $query ) use ( &$queries ) {
			if ( self::is_site_option_query( $query ) ) {
				$queries[] = $query;
			}

			return $query;
		};

		add_filter( 'query', $capture );
		try {
			$callback();
		} finally {
			remove_filter( 'query', $capture );
		}

		return $queries;
	}

	/**
	 * @param mixed $query
	 */
	protected static function is_site_option_query( $query ): bool {
		return is_string( $query ) && strpos( $query, ' AS site_id' ) !== false;
	}

	/**
	 * Run the callback with `$wpdb` errors suppressed, as tests of failed queries expect them
	 *
	 * @return mixed What the callback returns
	 */
	protected function without_db_errors( callable $callback ) {
		global $wpdb;

		$suppress = $wpdb->suppress_errors( true );
		try {
			return $callback();
		} finally {
			$wpdb->suppress_errors( $suppress );
		}
	}

	public static function tear_down_after_class(): void {
		static::cleanup_class_fixtures();
		parent::tear_down_after_class();
	}
}
