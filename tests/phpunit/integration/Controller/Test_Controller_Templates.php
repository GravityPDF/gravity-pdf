<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Tests\Integration\TestCase;

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   templates
 */
class Test_Controller_Templates extends TestCase {

	public function test_init_registers_ajax_endpoints() {
		global $gfpdf;

		remove_all_actions( 'wp_ajax_gfpdf_upload_template' );
		remove_all_actions( 'wp_ajax_gfpdf_delete_template' );
		remove_all_actions( 'wp_ajax_gfpdf_get_template_options' );

		$controller = new Controller_Templates( $gfpdf->singleton->get_class( 'Model_Templates' ) );
		$controller->init();

		$this->assertNotFalse( has_action( 'wp_ajax_gfpdf_upload_template' ) );
		$this->assertNotFalse( has_action( 'wp_ajax_gfpdf_delete_template' ) );
		$this->assertNotFalse( has_action( 'wp_ajax_gfpdf_get_template_options' ) );
	}

	public function test_constructor_wires_model_back_to_controller() {
		global $gfpdf;

		$model      = $gfpdf->singleton->get_class( 'Model_Templates' );
		$controller = new Controller_Templates( $model );

		$this->assertSame( $controller, $model->getController() );
	}
}
