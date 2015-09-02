<?php

/**
 * Override certain pluggable functions so we can unit test them correctly
 * @since 4.0
 */
function auth_redirect() {
    throw new Exception('Redirecting');
}

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
		$this->plugin_dir   = dirname( $this->tests_dir );
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : $this->plugin_dir . '/tmp/wordpress-tests-lib';

		/* load test function so tests_add_filter() is available */
		require_once $this->wp_tests_dir . '/includes/functions.php';

		/* load Gravity PDF */
		tests_add_filter( 'muplugins_loaded', array( $this, 'load' ) );

		/* load the WP testing environment */
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		/* clean up Gravity Forms database when finished */
		register_shutdown_function( array( $this, 'shutdown') );
	}

	/**
	 * Load Gravity Forms and Gravity PDF
	 *
	 * @since 4.0
	 */
	public function load() {
		require_once $this->plugin_dir . '/tmp/gravityforms/gravityforms.php';
		require_once $this->plugin_dir . '/tmp/gravityformspoll/polls.php';
		require_once $this->plugin_dir . '/tmp/gravityformsquiz/quiz.php';
		require_once $this->plugin_dir . '/tmp/gravityformssurvey/survey.php';
		require_once $this->plugin_dir . '/gravity-pdf.php';

		/* set up Gravity Forms database */
		GFForms::setup( true );
	}

	/**
	 * Run clean up when PHP finishes executing
	 *
	 * @since 4.0
	 */
	public function shutdown() {
		RGFormsModel::drop_tables();
	}

}

new GravityPDF_Unit_Tests_Bootstrap();

