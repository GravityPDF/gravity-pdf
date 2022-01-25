<?php

namespace GFPDF\Tests;

use GFCommon;
use GFPDF_Major_Compatibility_Checks;
use WP_UnitTestCase;

/**
 * Test Gravity PDF Loader Class
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Test the initial boot-up plugin phase
 *
 * @since 4.0
 * @group pre-checks
 */
class Test_Pre_Checks extends WP_UnitTestCase {

	/**
	 * Our Gravity PDF object used in tests
	 *
	 * @var GFPDF_Major_Compatibility_Checks
	 *
	 * @since 4.0
	 */
	public $gravitypdf;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function set_up() {
		/* run parent method */
		parent::set_up();

		/* Setup out loader class */
		$this->gravitypdf = new GFPDF_Major_Compatibility_Checks(
			PDF_PLUGIN_BASENAME,
			PDF_PLUGIN_DIR
		);

		$this->gravitypdf->init();
	}

	/**
	 * Ensure correct constants are called
	 *
	 * @since 4.0
	 */
	public function test_constants() {
		$this->assertTrue( defined( 'PDF_EXTENDED_VERSION' ) );
		$this->assertTrue( defined( 'PDF_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'PDF_PLUGIN_URL' ) );
		$this->assertTrue( defined( 'PDF_PLUGIN_BASENAME' ) );
	}

	/**
	 * Ensure our auto initializer is firing correctly
	 *
	 * @since 4.0
	 */
	public function test_init() {
		/* test the class loaded correctly */
		$this->assertEquals( 10, has_action( 'plugins_loaded', [ $this->gravitypdf, 'plugins_loaded' ] ) );
	}

	/**
	 * Test our min WordPress version is working correctly
	 *
	 * @param string $min_version
	 * @param string $test_wp_version
	 * @param bool $expected
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_version
	 */
	public function test_check_wordpress( $min_version, $test_wp_version, $expected ) {
		/* set up our current WP version and the min version */
		global $wp_version;
		$wp_version                            = $test_wp_version;
		$this->gravitypdf->required_wp_version = $min_version;

		/* run our test */
		$this->assertEquals( $expected, $this->gravitypdf->is_compatible_wordpress_version() );
	}

	/**
	 * Test our min Gravity Forms version is working correctly
	 *
	 * @param string $min_version
	 * @param string $test_gf_version
	 * @param bool $expected
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_version
	 */
	public function test_check_gravityforms( $min_version, $test_gf_version, $expected ) {
		/* set up our current Gravity Forms version and the min version */
		GFCommon::$version                     = $test_gf_version;
		$this->gravitypdf->required_gf_version = $min_version;

		/* run our test */
		$this->assertEquals( $expected, $this->gravitypdf->check_gravity_forms() );
	}

	/**
	 * Ensure we are getting the correct memory (in bytes) based on the PHP ini setting
	 *
	 * @param string $memory
	 * @param string $bytes
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_memory
	 */
	public function test_convert_ini_memory( $memory, $bytes ) {
		$this->assertEquals( $bytes, $this->gravitypdf->convert_ini_memory( $memory ) );
	}

	/**
	 * Ensure we are getting the correct memory (in mb) based on the PHP ini setting
	 *
	 * @param string $memory
	 * @param string $bytes
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_memory
	 */
	public function test_get_ram( $memory, $bytes ) {
		$expected_mb = ( $memory === '-1' ) ? -1 : floor( $bytes / 1024 / 1024 );
		$this->assertEquals( $expected_mb, $this->gravitypdf->get_ram( $memory ) );
	}

	/**
	 * Check if we meet the minimum RAM requirements
	 *
	 * @param string $memory
	 * @param bool $expected
	 *
	 * @since        4.0
	 *
	 * @dataProvider provider_memory_minimum
	 */
	public function test_check_ram( $memory, $expected ) {
		$this->assertEquals( $expected, $this->gravitypdf->check_ram( $memory ) );
	}

	/**
	 * Check that our notice is being correctly called
	 *
	 * @since 4.0
	 */
	public function test_loader_notice() {
		/* trigger a notice (it's a private variable) */
		$this->test_check_ram( '40M', false );

		/* run our autoloader */
		$this->gravitypdf->plugins_loaded();

		/* check the notice was attached */
		$this->assertEquals( 10, has_action( 'admin_notices', [ $this->gravitypdf, 'display_notices' ] ) );
	}

	/**
	 * A data provider for any tests that need to check PHP memory
	 *
	 * @return array Our test data
	 *
	 * @since 4.0
	 */
	public function provider_memory() {
		return [
			[ '60mb', '62914560' ],
			[ '60MB', '62914560' ],
			[ '60m', '62914560' ],
			[ '60M', '62914560' ],
			[ '60kb', '61440' ],
			[ '60KB', '61440' ],
			[ '60k', '61440' ],
			[ '60K', '61440' ],
			[ '1GB', '1073741824' ],
			[ '1G', '1073741824' ],
			[ '1gb', '1073741824' ],
			[ '1g', '1073741824' ],
			[ '-1', '-1' ],
		];
	}

	/**
	 * A data provider to check we meet the minimum memory requirements
	 *
	 * @return array Our test data
	 *
	 * @since 4.0
	 */
	public function provider_memory_minimum() {
		return [
			[ '512M', true ],
			[ '100M', true ],
			[ '75M', true ],
			[ '65M', true ],
			[ '64M', true ],
			[ '63M', false ],
			[ '60M', false ],
			[ '60k', false ],
			[ '3G', true ],
			[ '1G', true ],
			[ '-1', true ],
		];
	}

	/**
	 * A data provider for any tests that need version number checks
	 *
	 * @return array Our test data
	 *
	 * @since 4.0
	 */
	public function provider_version() {
		return [
			[ '5.5.5', '5.5.6', true ],
			[ '5.5.5', '5.5.5', true ],
			[ '5.5.5', '5.5.5.0', true ],
			[ '5.5.5', '5.5.5.1', true ],
			[ '5.5.5', '5.5.4', false ],
			[ '5.5.5', '5.5.4.9', false ],
			[ '4.0', '3.9.1.0', false ],
			[ '4.0', '3.8', false ],
			[ '4.0', '3.5.20', false ],
			[ '4.0', '4.0.0', true ],
			[ '4.0', '4.0.1', true ],
			[ '4.0-RC1', '4.0', true ],
			[ '4.0-RC1', '4.0-RC2', true ],
			[ '4.0-RC2', '4.0-RC1', false ],
			[ '4.0-Beta2', '4.0-Beta1', false ],
		];
	}
}
