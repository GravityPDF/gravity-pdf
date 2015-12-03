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
	 * @var array GF Form array
	 */
	public $form = array();

	/**
	 * @var array GF Entry array
	 */
	public $entry = array();

	/**
	 * @var  array $form_data
	 */
	public $form_data = array();

	/**
	 * Setup the unit testing environment
	 *
	 * @since 4.0
	 */
	public function __construct() {

		$this->tests_dir    = dirname( __FILE__ );
		$this->plugin_dir   = dirname( $this->tests_dir ) . '/..';
		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : $this->plugin_dir . '/tmp/wordpress-tests-lib';

		/* load test function so tests_add_filter() is available */
		require_once $this->wp_tests_dir . '/includes/functions.php';

		/* load Gravity PDF */
		tests_add_filter( 'muplugins_loaded', array( $this, 'load' ) );

		/* load Gravity PDF objects */
		tests_add_filter( 'plugins_loaded', array( $this, 'create_stubs' ), 20 );

		/* load the WP testing environment */
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );
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

		/* set up Gravity Forms database */
		RGFormsModel::drop_tables();
		@GFForms::setup( true );

		require_once $this->plugin_dir . '/pdf.php';
	}

	/**
	 * Create our Gravity Form stubs for use in our tests
	 *
	 * @since 4.0
	 */
	public function create_stubs() {

		/* Import all JSON forms into Gravity Forms */
		$forms = array(
			'all-form-fields.json',
			'form-settings.json',
			'gravityform-1.json',
			'gravityform-2.json',
		);

		foreach ( $forms as $json ) {
			$form                                  = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/unit-tests/json/' . $json ) ), true );
			$form_id                               = GFAPI::add_form( $form );
			$this->form[ substr( $json, 0, - 5 ) ] = GFAPI::get_form( $form_id );
		}

		/* Import our entries */
		$entries = array(
			'all-form-fields' => 'all-form-fields-entries.json',
			'form-settings'   => '',
			'gravityform-1'   => 'gravityform-1-entries.json',
		);


		foreach ( $entries as $id => $json ) {
			$entries   = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/unit-tests/json/' . $json ) ), true );
			$entry_ids = GFAPI::add_entries( $entries, $this->form[ $id ]['id'] );

			/* Loop through our new entry IDs and get the actual entries */
			$this->entries[ $id ] = array();
			foreach ( $entry_ids as $lid ) {
				$entry                  = GFAPI::get_entry( $lid );
				$this->entries[ $id ][] = $entry;

				/* We only need to run this once */
				if ( ! isset( $this->form_data[ $id ] ) ) {
					$this->form_data[ $id ][] = GFPDFEntryDetail::lead_detail_grid_array( $this->form[ $id ], $entry );
				}
			}
		}
	}

}

$GLOBALS['GFPDF_Test'] = new GravityPDF_Unit_Tests_Bootstrap();
