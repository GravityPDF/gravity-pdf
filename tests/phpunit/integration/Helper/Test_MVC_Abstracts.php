<?php

declare( strict_types=1 );

namespace GFPDF\Helper;
use GFPDF\Controller\Controller_Settings;
use GFPDF\Model\Model_Settings;
use GFPDF\View\View_Settings;
use GFPDF\Tests\Integration\TestCase;


/**
 * Test Gravity PDF MVC Abstraction classes
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/**
 * Test the Controller / Model / View Abstract Class
 *
 * @since 4.0
 * @group mvc-abstracts
 */
class Test_MVC_Abstracts extends TestCase {
	/**
	 * Our Gravity PDF controller object
	 *
	 * @var Controller_Settings
	 *
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Gravity PDF model object
	 *
	 * @var Model_Settings
	 *
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our Gravity PDF view object
	 *
	 * @var View_Settings
	 *
	 * @since 4.0
	 */
	public $view;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function set_up(): void {
		global $gfpdf;

		/* run parent method */
		parent::set_up();

		/* Setup out loader class */
		$this->model      = new Model_Settings( $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );
		$this->view       = new View_Settings( [], $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );
		$this->controller = new Controller_Settings( $this->model, $this->view, $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->data, $gfpdf->misc );
	}

	/**
	 * Test the abstract controller wires the model/view pair and registers its hooks on init.
	 *
	 * @since 4.0
	 */
	public function test_abstract_controller() {
		$this->assertInstanceOf( Model_Settings::class, $this->controller->model );
		$this->assertInstanceOf( View_Settings::class, $this->controller->view );

		$this->assertFalse( has_action( 'gfpdf_settings_sub_menu', [ $this->controller->view, 'sub_menu' ] ) );
		$this->assertFalse( has_action( 'wp_ajax_gfpdf_deactivate_license', [ $this->controller->model, 'process_license_deactivation' ] ) );
		$this->assertFalse( has_filter( 'gform_tooltips', [ $this->controller->view, 'add_tooltips' ] ) );

		$this->controller->init();

		$this->assertSame( 10, has_action( 'gfpdf_settings_sub_menu', [ $this->controller->view, 'sub_menu' ] ) );
		$this->assertSame( 10, has_action( 'wp_ajax_gfpdf_deactivate_license', [ $this->controller->model, 'process_license_deactivation' ] ) );
		$this->assertSame( 10, has_filter( 'gform_tooltips', [ $this->controller->view, 'add_tooltips' ] ) );
	}

	/**
	 * Test the abstract model's controller back-reference is the same concrete controller wired in setUp.
	 *
	 * @since 4.0
	 */
	public function test_abstract_model() {
		$this->assertSame( $this->controller, $this->model->getController() );
	}

	/**
	 * Test the abstract view methods
	 *
	 * @since 4.0
	 */
	public function test_abstract_view() {
		$this->setExpectedDeprecated( 'GFPDF\View\View_Settings::tabs' );

		/*
		 * Test our load function produces the correct output
		 */
		$this->assertNotEmpty( $this->view->tabs() );

		/* check for error */
		$error = $this->view->load_none_existant_file( [] );
		$this->assertInstanceOf( \WP_Error::class, $error );

		/*
		 * Test our get_view_dir_path() string works correctly
		 */
		$results = $this->view->get_view_dir_path();

		$this->assertFileExists( $results . 'general.php' );
		$this->assertFileDoesNotExist( $results . 'generic-file-that-isnt-included.php' );
	}
}
