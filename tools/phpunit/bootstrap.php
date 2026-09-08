<?php

/**
 * If Xdebug is installed disable stack traces for phpunit
 */
if ( function_exists( 'xdebug_disable' ) ) {
	xdebug_disable();
}

/**
 * Override certain pluggable functions so we can unit test them correctly
 *
 * @since 4.0
 */
function auth_redirect() {
	throw new Exception( 'Redirecting' );
}

/* Define custom config to override the URL used for the test site */
define( 'WP_TESTS_CONFIG_FILE_PATH', '/var/www/html/wp-content/plugins/gravity-pdf/tools/phpunit/wp-tests-config.php' );

/**
 * Gravity PDF Unit Tests Bootstrap
 *
 * @since 4.0
 */
class GravityPDF_Unit_Tests_Bootstrap {

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/**
	 * Setup the unit testing environment
	 *
	 * @since 4.0
	 */
	public function __construct() {

		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir, 2 );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: getenv( 'WP_PHPUNIT__DIR' );

		/* load test function so tests_add_filter() is available */
		require_once $this->wp_tests_dir . '/includes/functions.php';

		/* load Gravity PDF */
		tests_add_filter( 'muplugins_loaded', [ $this, 'load' ] );

		/* load the WP testing environment */
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		/* Load Mocks */
		$this->mocks();
	}

	/**
	 * Load Addon Mocks.
	 *
	 * Stubs for add-on classes that aren't installed in the test environment
	 * (Zapier, Chained Selects, Gravity Perks Nested Forms) so tests exercising
	 * those integration code paths can run.
	 *
	 * @since 6.3
	 */
	public function mocks() {
		require_once __DIR__ . '/Mocks/zapier-mock.php';
		require_once __DIR__ . '/Mocks/gf-chained-field-select-mock.php';
		require_once __DIR__ . '/Mocks/gp-field-nested-form-mock.php';
	}

	/**
	 * Load Gravity Forms and Gravity PDF
	 *
	 * @since 4.0
	 */
	public function load() {
		/*
		 * Gravity Forms, its add-ons, and pdf.php's include-time wiring emit
		 * E_DEPRECATED notices on PHP 8.x. Suppress them across the whole
		 * bootstrap; the previous level is restored at the end so deprecations
		 * from code-under-test still surface during the actual test run.
		 */
		$previous_error_level = error_reporting( E_ALL & ~E_DEPRECATED );

		require_once( __DIR__ . '/gravityforms-factory.php' );

		require_once $this->plugin_dir . '/../gravityforms/gravityforms.php';
		require_once __DIR__ . '/stubs/gravity-forms-addons.php';
		require_once( GFCommon::get_base_path() . '/tooltips.php' );

		/* set up Gravity Forms database */
		add_filter( 'get_available_languages', function( $language ) {
			return [];
		} );

		remove_filter( 'query', [ 'GFForms', 'filter_query' ] );
		update_option( 'gf_db_version', GFForms::$version );
		GFFormsModel::drop_tables();
		gf_upgrade()->maybe_upgrade();
		add_filter( 'gform_disable_dom_parser', '__return_true' );

		// Enabling GF Rest API v2.
		global $gf_webapi;
		$gf_webapi = GFWebAPI::get_instance();
		$gf_webapi->update_plugin_settings( [ 'enabled' => '1', 'version' => 'v2' ] );

		require_once $this->plugin_dir . '/pdf.php';

		$this->create_font_tables();

		error_reporting( $previous_error_level );
	}

	/**
	 * Create the font tables for real, once, before the suite starts
	 *
	 * This has to happen here rather than in `ensure_ready()` during a test. WP_UnitTestCase installs filters that
	 * turn CREATE TABLE into a TEMPORARY table, and a temporary table shadows the real one for the connection — so
	 * a mid-test `ensure_ready()` would read empty shadows and its `SHOW TABLES` verify would misfire. Writing
	 * `gfpdf_db_version` here makes `ensure_ready()` a no-op in every test.
	 *
	 * @since 7.0
	 */
	protected function create_font_tables() {
		/* GPDFAPI is not wired up this early, and the schema only logs on failure, which is asserted below */
		$schema = new \GFPDF\Fonts\Font_Schema( new \GFPDF_Vendor\Psr\Log\NullLogger() );

		$schema->drop();
		$schema->ensure();

		$missing = $schema->get_missing_tables();
		if ( count( $missing ) > 0 ) {
			throw new RuntimeException( 'Could not create the Gravity PDF font tables: ' . implode( ', ', $missing ) );
		}

		$schema->mark_current();

		if ( get_option( $schema::VERSION_OPTION ) !== $schema->get_version() ) {
			throw new RuntimeException( 'The Gravity PDF font schema version was not recorded' );
		}
	}

}

new GravityPDF_Unit_Tests_Bootstrap();
