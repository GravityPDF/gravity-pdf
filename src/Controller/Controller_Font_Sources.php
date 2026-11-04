<?php

declare( strict_types=1 );

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
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
 * Registers the font sources with mPDF and the font list
 *
 * @since 6.19.0
 */
class Controller_Font_Sources extends Helper_Abstract_Controller implements Helper_Interface_Filters {

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
	 * Register the font source filters
	 *
	 * @since 6.19.0
	 */
	public function init(): void {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Flush the font sources' cached language fonts when a language pack installs
	 *
	 * @since 6.19.0
	 */
	public function add_actions(): void {
		/* A language pack may change the language fonts */
		add_action( 'upgrader_process_complete', [ $this->model, 'flush' ], 10, 0 );
	}

	/**
	 * @since 6.19.0
	 */
	public function add_filters(): void {
		/* Priority 5, so a custom font (10) with the same ID overrides a source's */
		add_filter( 'mpdf_font_data', [ $this->model, 'register_font_data' ], 5 );
		add_filter( 'gfpdf_mpdf_class_config', [ $this->model, 'register_font_directories' ] );
		/* Priority 5, so a config filter at the default priority can still choose another font */
		add_filter( 'gfpdf_mpdf_class_config', [ $this->model, 'set_default_font' ], 5, 4 );
		add_filter( 'gfpdf_font_list', [ $this->model, 'add_font_list_groups' ], 5 );
	}
}
