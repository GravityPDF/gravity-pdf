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

		$this->load_test_infrastructure();
	}

	/**
	 * Load Addon Mocks.
	 *
	 * Currently loads the Zapier add-on stub so tests that exercise the Zapier
	 * integration code paths can run without the real add-on installed.
	 *
	 * @since 6.3
	 */
	public function mocks() {
		require_once __DIR__ . '/Mocks/zapier-mock.php';
	}

	/**
	 * Required explicitly because tests/phpunit/Concerns/ is intentionally
	 * not part of the PHPUnit <testsuites> <directory> list (traits are not
	 * tests).
	 *
	 * @since 7.0
	 */
	public function load_test_infrastructure() {
		$root = $this->plugin_dir . '/tests/phpunit';

		require_once $root . '/Concerns/HasGfpdfFixtures.php';
		require_once $root . '/Concerns/UsesFactory.php';
		require_once $root . '/integration/TestCase.php';
		require_once $root . '/integration/AjaxTestCase.php';
	}

	/**
	 * Load Gravity Forms and Gravity PDF
	 *
	 * @since 4.0
	 */
	public function load() {
		error_reporting( E_ALL & ~E_DEPRECATED );

		require_once( __DIR__ . '/gravityforms-factory.php' );

		require_once $this->plugin_dir . '/../gravityforms/gravityforms.php';
		require_once $this->plugin_dir . '/../gravityformspolls/polls.php';
		require_once $this->plugin_dir . '/../gravityformsquiz/quiz.php';
		require_once $this->plugin_dir . '/../gravityformssurvey/survey.php';
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
	}

}

new GravityPDF_Unit_Tests_Bootstrap();
