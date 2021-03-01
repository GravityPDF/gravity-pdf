<?php

namespace GFPDF\Deprecated;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Vendor_Aliasing
 *
 * @package GFPDF\Deprecated
 *
 * @group   deprecated
 */
class Test_Vendor_Aliasing extends WP_UnitTestCase {

	/**
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 */
	protected $options;

	public function setUp(): void {
		parent::setUp();

		$this->options = \GPDFAPI::get_options_class();
		$this->options->delete_option( 'vendor_aliasing' );
	}

	/**
	 * @since 6.0
	 */
	public function test_maybe_alias_vendor_packages_skipped() {
		$this->assertFalse( Vendor_Aliasing::maybe_alias_vendor_packages() );

		$this->options->update_option( 'vendor_aliasing', true );
		add_filter( 'gfpdf_disable_vendor_aliasing', '__return_true' );

		$this->assertFalse( Vendor_Aliasing::maybe_alias_vendor_packages() );
	}

	/**
	 * @since 6.0
	 */
	public function test_maybe_alias_vendor_packages_run() {
		$this->assertFalse( class_exists( 'Mpdf\\Mpdf' ) );

		$this->options->update_option( 'vendor_aliasing', true );
		$this->assertTrue( Vendor_Aliasing::maybe_alias_vendor_packages() );
		$this->assertTrue( class_exists( 'Mpdf\\Mpdf' ) );
	}
}
