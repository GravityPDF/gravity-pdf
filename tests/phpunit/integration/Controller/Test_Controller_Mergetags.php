<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   mergetags
 */
class Test_Controller_Mergetags extends TestCase {

	public function test_init_registers_mergetag_filters() {
		global $gfpdf;

		foreach (
			[
				'gform_replace_merge_tags',
				'gform_custom_merge_tags',
				'gform_field_map_choices',
				'gform_addon_field_value',
				'gform_mailchimp_field_value',
				'gpgs_row_value',
			] as $hook
		) {
			remove_all_filters( $hook );
		}

		$controller = new Controller_Mergetags( $gfpdf->singleton->get_class( 'Model_Mergetags' ) );
		$controller->init();

		$this->assertNotFalse( has_filter( 'gform_replace_merge_tags' ) );
		$this->assertNotFalse( has_filter( 'gform_custom_merge_tags' ) );
		$this->assertNotFalse( has_filter( 'gform_field_map_choices' ) );
		$this->assertNotFalse( has_filter( 'gform_addon_field_value' ) );
		$this->assertNotFalse( has_filter( 'gform_mailchimp_field_value' ) );
		$this->assertNotFalse( has_filter( 'gpgs_row_value' ) );
	}

	public function test_constructor_wires_model_back_to_controller() {
		global $gfpdf;

		$model      = $gfpdf->singleton->get_class( 'Model_Mergetags' );
		$controller = new Controller_Mergetags( $model );

		$this->assertSame( $controller, $model->getController() );
	}
}
