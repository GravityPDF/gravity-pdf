<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Tests\Integration\TestCase;
use ReflectionMethod;

/**
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   core-fonts
 */
class Test_Controller_Save_Core_Fonts extends TestCase {

	/**
	 * @var Controller_Save_Core_Fonts
	 */
	private $controller;

	public function set_up() {
		global $gfpdf;

		parent::set_up();

		$this->controller = new Controller_Save_Core_Fonts( $gfpdf->log, $gfpdf->data, $gfpdf->misc );
	}

	public function tear_down() {
		unset( $_POST['font_name'] );

		parent::tear_down();
	}

	public function test_init_registers_ajax_endpoint() {
		remove_all_actions( 'wp_ajax_gfpdf_save_core_font' );

		$this->controller->init();

		$this->assertNotFalse( has_action( 'wp_ajax_gfpdf_save_core_font', [ $this->controller, 'save_core_font' ] ) );
	}

	public function test_download_returns_false_when_font_name_missing() {
		unset( $_POST['font_name'] );

		$this->assertFalse( $this->invoke_download() );
	}

	public function test_download_returns_false_when_font_name_not_on_approved_list() {
		$_POST['font_name'] = 'NotARealFont.ttf';

		$this->assertFalse( $this->invoke_download() );
	}

	private function invoke_download() {
		return ( new ReflectionMethod( $this->controller, 'download_and_save_font' ) )->invoke( $this->controller );
	}
}
