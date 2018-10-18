<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Templates;

/**
 * Handle Debug Mode
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.1
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


/**
 * Class Controller_Debug
 *
 * @package GFPDF\Controller
 *
 * @since   5.1
 */
class Controller_Debug extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var \GFPDF\Helper\Helper_Data
	 *
	 * @since 5.1
	 */
	protected $data;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var \GFPDF\Helper\Helper_Abstract_Options
	 *
	 * @since 5.1
	 */
	protected $options;

	/**
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var \GFPDF\Helper\Helper_Templates
	 *
	 * @since 5.1
	 */
	protected $templates;

	/**
	 * Controller_Debug constructor.
	 *
	 * @param Helper_Data             $data
	 * @param Helper_Abstract_Options $options
	 * @param Helper_Templates        $templates
	 */
	public function __construct( Helper_Data $data, Helper_Abstract_Options $options, Helper_Templates $templates ) {
		$this->data      = $data;
		$this->options   = $options;
		$this->templates = $templates;
	}

	/**
	 * Initialise our class defaults
	 *
	 * @since 5.1
	 */
	public function init() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * @since 5.1
	 */
	public function add_actions() {
		add_action( 'update_option_gfpdf_settings', [ $this, 'maybe_flush_transient_cache' ], 10, 2 );
	}

	/**
	 * @since 5.1
	 */
	public function add_filters() {
		add_filter( 'gfpdf_mpdf_class', [ $this, 'maybe_add_pdf_stats' ] );
	}

	/**
	 * If Debug Mode is toggled on, flush the transient cache
	 *
	 * @param array $oldvalue
	 * @param array $value
	 *
	 * @since 5.1
	 */
	public function maybe_flush_transient_cache( $oldvalue, $value ) {
		if ( isset( $value['debug_mode'] ) && $value['debug_mode'] === 'Yes' && ( ! isset( $oldvalue['debug_mode'] ) || $oldvalue['debug_mode'] !== 'Yes' ) ) {
			$this->templates->flush_template_transient_cache();
		}
	}

	/**
	 * If debug mode enabled, enable PDF stats.
	 *
	 * @param Mpdf $mpdf
	 *
	 * @return Mpdf
	 *
	 * @since 5.1
	 */
	public function maybe_add_pdf_stats( $mpdf ) {
		if ( $this->options->get_option( 'debug_mode', 'No' ) === 'Yes' ) {
			$mpdf->WriteHTML( '<div>Generated in ' . sprintf( '%.2F', ( microtime( true ) - $mpdf->time0 ) ) . ' seconds</div>' );
			$mpdf->WriteHTML( '<div>Peak Memory usage ' . number_format( ( memory_get_peak_usage( true ) / ( 1024 * 1024 ) ), 2 ) . ' MB</div>' );
			$mpdf->WriteHTML( '<div>Number of fonts ' . count( $mpdf->fonts ) . '</div>' );
		}

		return $mpdf;
	}
}