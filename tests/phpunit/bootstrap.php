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
define( 'WP_TESTS_CONFIG_FILE_PATH', '/var/www/html/wp-content/plugins/gravity-pdf/tests/phpunit/wp-tests-config.php' );

putenv( 'WORDPRESS_TABLE_PREFIX=phpunit_' );
putenv( 'WORDPRESS_URL=http://example.org/' );

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
	public $form = [];

	/**
	 * @var array GF Entry array
	 */
	public $entry = [];

	/**
	 * @var  array $form_data
	 */
	public $form_data = [];

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

		/* load Gravity PDF objects */
		tests_add_filter( 'after_setup_theme', [ $this, 'create_stubs' ], 20 );

		/* load the WP testing environment */
		require_once( $this->wp_tests_dir . '/includes/bootstrap.php' );

		/* Load Mocks */
		$this->mocks();
	}

	/**
	 * Load Addon Mocks
	 *
	 * @since 6.3
	 */
	public function mocks() {
		require_once 'unit-tests/Mocks/zapier-mock.php';
	}

	/**
	 * Load Gravity Forms and Gravity PDF
	 *
	 * @since 4.0
	 */
	public function load() {
		require_once $this->plugin_dir . '/../gravityforms/gravityforms.php';
		require_once $this->plugin_dir . '/../gravityformspolls/polls.php';
		require_once $this->plugin_dir . '/../gravityformsquiz/quiz.php';
		require_once $this->plugin_dir . '/../gravityformssurvey/survey.php';

		/* set up Gravity Forms database */
		add_filter( 'get_available_languages', function( $language ) {
			return [];
		} );

		remove_filter( 'query', [ 'GFForms', 'filter_query' ] );
		update_option( 'gf_db_version', GFForms::$version );
		GFFormsModel::drop_tables();
		gf_upgrade()->maybe_upgrade();

		require_once $this->plugin_dir . '/pdf.php';
	}

	/**
	 * Create our Gravity Form stubs for use in our tests
	 *
	 * @since 4.0
	 */
	public function create_stubs() {
		global $gfpdf;

		/* Import all JSON forms into Gravity Forms */
		$forms = [
			'all-form-fields.json',
			'form-settings.json',
			'gravityform-1.json',
			'gravityform-2.json',
			'repeater-empty-form.json',
			'repeater-consent-form.json'
		];

		foreach ( $forms as $json ) {
			$form                                 = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/unit-tests/json/' . $json ) ), true );
			$form_id                              = GFAPI::add_form( $form );
			$this->form[ substr( $json, 0, -5 ) ] = GFAPI::get_form( $form_id );
		}

		/* Import our entries */
		$entries = [
			'all-form-fields'     => 'all-form-fields-entries.json',
			'gravityform-1'       => 'gravityform-1-entries.json',
			'repeater-empty-form' => 'repeater-empty-entry.json',
			'repeater-consent-form' => 'repeater-consent-entry.json',
		];

		foreach ( $entries as $id => $json ) {
			$entries   = json_decode( trim( file_get_contents( dirname( __FILE__ ) . '/unit-tests/json/' . $json ) ), true );
			$entry_ids = GFAPI::add_entries( $entries, $this->form[ $id ]['id'] );

			/* Loop through our new entry IDs and get the actual entries */
			$this->entries[ $id ] = [];
			foreach ( $entry_ids as $lid ) {
				$entry                  = GFAPI::get_entry( $lid );
				$this->entries[ $id ][] = $entry;

				/* We only need to run this once */
				if ( ! isset( $this->form_data[ $id ] ) ) {
					$this->form_data[ $id ][] = GPDFAPI::get_form_data( $entry['id'] );
				}
			}
		}

		$gfpdf->data->form_settings = [];
	}

}

$GLOBALS['GFPDF_Test'] = new GravityPDF_Unit_Tests_Bootstrap();
