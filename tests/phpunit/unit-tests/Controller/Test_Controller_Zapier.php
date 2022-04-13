<?php

namespace GFPDF\Controller;

use WP_UnitTestCase;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Class Test_Controller_Zapier
 *
 * @package GFPDF\Controller
 *
 * @group   controller
 * @group   zapier
 */
class Test_Controller_Zapier extends WP_UnitTestCase {

	/**
	 * @var Controller_Zapier
	 */
	protected $controller;

	public function set_up() {
		parent::set_up();

		$this->controller = new Controller_Zapier();
	}

	public function test_add_zapier_support_active_pdfs() {
		$entry = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$body  = $this->controller->add_zapier_support( [], [], $entry );

		$this->assertCount( 8, $body );

		$this->assertArrayHasKey( 'My First PDF Template (copy) PDF URL', $body );
		$this->assertArrayHasKey( 'My First PDF Template (copy) PDF URL - SIGNED 1 WEEK', $body );
		$this->assertArrayHasKey( 'My First PDF Template (copy) PDF URL - SIGNED 1 MONTH', $body );
		$this->assertArrayHasKey( 'My First PDF Template (copy) PDF URL - SIGNED 1 YEAR', $body );

		$this->assertArrayHasKey( 'My First PDF Template (copy) PDF URL - 1', $body );
		$this->assertArrayHasKey( 'My First PDF Template (copy) PDF URL - SIGNED 1 WEEK - 1', $body );
		$this->assertArrayHasKey( 'My First PDF Template (copy) PDF URL - SIGNED 1 MONTH - 1', $body );
		$this->assertArrayHasKey( 'My First PDF Template (copy) PDF URL - SIGNED 1 YEAR - 1', $body );

		$this->assertNotFalse( filter_var( $body['My First PDF Template (copy) PDF URL'], FILTER_VALIDATE_URL ) );
		$this->assertNotFalse( filter_var( $body['My First PDF Template (copy) PDF URL - SIGNED 1 WEEK'], FILTER_VALIDATE_URL ) );
		$this->assertNotFalse( filter_var( $body['My First PDF Template (copy) PDF URL - SIGNED 1 MONTH'], FILTER_VALIDATE_URL ) );
		$this->assertNotFalse( filter_var( $body['My First PDF Template (copy) PDF URL - SIGNED 1 YEAR'], FILTER_VALIDATE_URL ) );

		$this->assertStringNotContainsString( 'signature=', $body['My First PDF Template (copy) PDF URL'] );
		$this->assertStringContainsString( 'signature=', $body['My First PDF Template (copy) PDF URL - SIGNED 1 WEEK'] );
		$this->assertStringContainsString( 'signature=', $body['My First PDF Template (copy) PDF URL - SIGNED 1 MONTH'] );
		$this->assertStringContainsString( 'signature=', $body['My First PDF Template (copy) PDF URL - SIGNED 1 YEAR'] );
	}

	public function test_add_zapier_support_conditional_logic() {
		$entry    = $GLOBALS['GFPDF_Test']->entries['all-form-fields'][0];
		$entry[7] = 'Albania';
		\GFAPI::update_entry( $entry );

		$body = $this->controller->add_zapier_support( [], [], $entry );

		$this->assertCount( 12, $body );

		$this->assertArrayHasKey( 'My First PDF Template PDF URL', $body );
		$this->assertArrayHasKey( 'My First PDF Template PDF URL - SIGNED 1 WEEK', $body );
		$this->assertArrayHasKey( 'My First PDF Template PDF URL - SIGNED 1 MONTH', $body );
		$this->assertArrayHasKey( 'My First PDF Template PDF URL - SIGNED 1 YEAR', $body );
	}

	public function test_add_zapier_support_empty() {
		$body = $this->controller->add_zapier_support( [], [], [ 'id' => 9999 ] );

		$this->assertCount( 0, $body );
	}

}
