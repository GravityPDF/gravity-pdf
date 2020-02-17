<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Abstract_Form;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class View_Welcome_Screen extends Helper_Abstract_View {

	/**
	 * Set the view's name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_type = 'Welcome';

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var \GFPDF\Helper\Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Setup our class by injecting all our dependancies
	 *
	 * @param array                                          $data_cache An array of data to pass to the view
	 * @param \GFPDF\Helper\Helper_Form|Helper_Abstract_Form $gform      Our abstracted Gravity Forms helper functions
	 *
	 * @since 4.0
	 */
	public function __construct( $data_cache = [], Helper_Abstract_Form $gform ) {

		/* Call our parent constructor */
		parent::__construct( $data_cache );

		/* Assign our internal variables */
		$this->gform = $gform;
	}

	/**
	 * Output the welcome screen
	 *
	 * @since 4.0
	 */
	public function welcome() {

		/* Load any variables we want to pass to our view */
		$args = [
			'forms' => $this->gform->get_forms(),
		];

		/* Render our view */
		$this->load( 'welcome', $args );

	}
}
