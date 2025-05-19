<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Templates;
use GFPDF_Vendor\Mpdf\Mpdf;
use GFPDF_Vendor\Mpdf\MpdfException;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


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
	 * @var Helper_Data
	 *
	 * @since 5.1
	 */
	protected $data;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var Helper_Abstract_Options
	 *
	 * @since 5.1
	 */
	protected $options;

	/**
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var Helper_Templates
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
	 * @throws MpdfException
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
