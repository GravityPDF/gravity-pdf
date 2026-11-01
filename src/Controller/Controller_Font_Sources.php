<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Model\Model_Font_Sources;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the font sources with mPDF and the font list, and adopts the custom font records they replace
 *
 * @since 6.19.0
 */
class Controller_Font_Sources extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {

	/**
	 * @var Model_Font_Sources
	 *
	 * @since 6.19.0
	 */
	public $model;

	public function __construct( Model_Font_Sources $model ) {
		$this->model = $model;
		$this->model->setController( $this );
	}

	/**
	 * @return void
	 *
	 * @since 6.19.0
	 */
	public function init() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * @return void
	 *
	 * @since 6.19.0
	 */
	public function add_actions() {
		add_action( 'admin_init', [ $this->model, 'maybe_adopt_changed_sources' ] );
	}

	/**
	 * @return void
	 *
	 * @since 6.19.0
	 */
	public function add_filters() {
		/* Before the custom fonts, at 10, so a custom font with the same ID overrides a source's */
		add_filter( 'mpdf_font_data', [ $this->model, 'register_font_data' ], 5 );
		add_filter( 'gfpdf_mpdf_class_config', [ $this->model, 'register_font_directories' ] );
		add_filter( 'gfpdf_font_list', [ $this->model, 'add_font_list_groups' ], 5 );
	}
}
