<?php

namespace GFPDF\Tests;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Controller\Controller_Settings;
use GFPDF\Model\Model_Settings;
use GFPDF\View\View_Settings;


use WP_UnitTestCase;

/**
 * Test Gravity PDF MVC Abstraction classes
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/**
 * Test the Controller / Model / View Abstract Class
 *
 * @since 4.0
 * @group mvc-abstracts
 */
class Test_MVC_Abstracts extends WP_UnitTestCase {
	/**
	 * Our Gravity PDF controller object
	 *
	 * @var \GFPDF\Controller\Controller_Settings
	 *
	 * @since 4.0
	 */
	public $controller;

	/**
	 * Our Gravity PDF model object
	 *
	 * @var \GFPDF\Model\Model_Settings
	 *
	 * @since 4.0
	 */
	public $model;

	/**
	 * Our Gravity PDF view object
	 *
	 * @var \GFPDF\View\View_Settings
	 *
	 * @since 4.0
	 */
	public $view;

	/**
	 * The WP Unit Test Set up function
	 *
	 * @since 4.0
	 */
	public function setUp() {
		global $gfpdf;

		/* run parent method */
		parent::setUp();

		/* Setup out loader class */
		$this->model      = new Model_Settings( $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );
		$this->view       = new View_Settings( [], $gfpdf->gform, $gfpdf->log, $gfpdf->options, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );
		$this->controller = new Controller_Settings( $this->model, $this->view, $gfpdf->gform, $gfpdf->log, $gfpdf->notices, $gfpdf->data, $gfpdf->misc, $gfpdf->templates );
	}

	/**
	 * Test the abstract controller methods
	 *
	 * @since 4.0
	 */
	public function test_abstract_controller() {
		/* ensure an init method exists */
		$this->assertTrue( method_exists( $this->controller, 'init' ) );

		/* get if model / view uses our abstract classes */
		$this->assertTrue( $this->controller->model instanceof Helper_Abstract_Model );
		$this->assertTrue( $this->controller->view instanceof Helper_Abstract_View );

		/* double check the controller stored the model / view correctly */
		$this->assertTrue( $this->controller->model instanceof Model_Settings );
		$this->assertTrue( $this->controller->view instanceof View_Settings );
	}

	/**
	 * Test the abstract model methods
	 *
	 * @since 4.0
	 */
	public function test_abstract_model() {
		/* ensure the following methods exist */
		$this->assertTrue( method_exists( $this->model, 'setController' ) );
		$this->assertTrue( method_exists( $this->model, 'getController' ) );

		/* ensure the returned controller uses our abstract class and is stored correctly */
		$this->assertTrue( $this->model->getController() instanceof Helper_Abstract_Controller );
		$this->assertTrue( $this->model->getController() instanceof Controller_Settings );
	}

	/**
	 * Test the abstract view methods
	 *
	 * @since 4.0
	 */
	public function test_abstract_view() {
		/*
		 * Test our load function produces the correct output
		 */
		ob_start();
		$results = $this->view->uninstaller( [] );
		$string  = ob_get_clean();

		/* check results are accurate */
		$this->assertTrue( $results );
		$this->assertNotEmpty( $string );

		/* check for error */
		$error = $this->view->load_none_existant_file( [] );
		$this->assertTrue( is_wp_error( $error ) );

		/*
		 * Test our get_view_dir_path() string works correctly
		 */
		$results = $this->view->get_view_dir_path();

		$this->assertFileExists( $results . 'general.php' );
		$this->assertFileNotExists( $results . 'generic-file-that-isnt-included.php' );
	}
}
