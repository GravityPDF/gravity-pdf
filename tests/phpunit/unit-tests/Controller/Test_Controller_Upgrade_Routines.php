<?php

namespace GFPDF\Controller;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Controller_Upgrade_Routines
 *
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   upgrade
 */
class Test_Controller_Upgrade_Routines extends WP_UnitTestCase {

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
	 * @dataProvider data_provider_6_0_0_upgrade_routine
	 */
	public function test_6_0_0_upgrade_routine( bool $expected, string $old_version, string $current_version ) {
		do_action( 'gfpdf_version_changed', $old_version, $current_version );

		$this->assertSame( $expected, $this->options->get_option( 'vendor_aliasing', false ) );
	}

	public function data_provider_6_0_0_upgrade_routine(): array {
		return [
			[ true, '5.3', '6.0.0-beta1' ],
			[ true, '4.5.2', '6.0.0' ],
			[ true, '5.3.2', '6.0.5' ],
			[ true, '5', '6.2.3-RC2' ],
			[ false, '5.3.2', '5.4.0' ],
			[ false, '6.0.0', '5.3.2' ],
			[ false, '6.0.0', '6.3.1' ],
			[ false, '6.0.0-beta1', '6.0.5' ],
		];
	}

	public function test_6_0_0_background_process_upgrade_routine() {
		/* Check for enabled status */
		$this->options->update_option( 'background_processing', 'Enable' );

		do_action( 'gfpdf_version_changed', '5.3', '6.0.0-beta1' );

		$this->assertSame( 'Yes', $this->options->get_option( 'background_processing' ) );

		/* Check for disabled status */
		$this->options->update_option( 'background_processing', 'Disable' );

		do_action( 'gfpdf_version_changed', '5.3', '6.0.0-beta1' );

		$this->assertSame( 'No', $this->options->get_option( 'background_processing' ) );
	}

}
