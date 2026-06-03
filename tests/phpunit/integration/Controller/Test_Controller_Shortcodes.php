<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   shortcodes
 */
class Test_Controller_Shortcodes extends TestCase {

	public function test_init_registers_filters_and_shortcode() {
		global $gfpdf;

		foreach (
			[
				'gform_admin_pre_render',
				'gform_confirmation',
				'gform_pre_replace_merge_tags',
				'gravityview/fields/custom/content_before',
			] as $hook
		) {
			remove_all_filters( $hook );
		}
		remove_shortcode( 'gravitypdf' );

		$controller = new Controller_Shortcodes(
			$gfpdf->singleton->get_class( 'Model_Shortcodes' ),
			$gfpdf->singleton->get_class( 'View_Shortcodes' ),
			$gfpdf->log
		);
		$controller->init();

		$this->assertNotFalse( has_filter( 'gform_admin_pre_render' ) );
		$this->assertNotFalse( has_filter( 'gform_confirmation' ) );
		$this->assertNotFalse( has_filter( 'gform_pre_replace_merge_tags' ) );
		$this->assertNotFalse( has_filter( 'gravityview/fields/custom/content_before' ) );
		$this->assertTrue( shortcode_exists( 'gravitypdf' ) );
	}

	public function test_constructor_wires_model_and_view_back_to_controller() {
		global $gfpdf;

		$model      = $gfpdf->singleton->get_class( 'Model_Shortcodes' );
		$view       = $gfpdf->singleton->get_class( 'View_Shortcodes' );
		$controller = new Controller_Shortcodes( $model, $view, $gfpdf->log );

		$this->assertSame( $controller, $model->getController() );
		$this->assertSame( $controller, $view->getController() );
	}
}
