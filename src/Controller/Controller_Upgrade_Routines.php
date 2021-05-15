<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Model\Model_Custom_Fonts;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Controller_Upgrade_Routines
 *
 * @package GFPDF\Controller
 */
class Controller_Upgrade_Routines {

	/**
	 * @var Helper_Abstract_Options
	 */
	protected $options;

	/**
	 * @var Helper_Data
	 */
	protected $data;

	public function __construct( Helper_Abstract_Options $options, Helper_Data $data ) {
		$this->options = $options;
		$this->data    = $data;
	}

	/**
	 * @since 6.0
	 */
	public function init(): void {
		add_action( 'gfpdf_version_changed', [ $this, 'maybe_run_upgrade' ], 10, 2 );
	}

	/**
	 * @since 6.0
	 */
	public function maybe_run_upgrade( string $old_version, string $current_version ): void {
		if ( version_compare( $current_version, '6.0.0-beta1', '>=' ) && version_compare( $old_version, '6.0.0-beta1', '<' ) ) {
			$this->update_background_processing_values();
			$this->upgrade_custom_fonts();
		}
	}

	/**
	 * Update Background Processing values to new Toggle button format
	 *
	 * @since 6.0
	 */
	protected function update_background_processing_values(): void {
		$value     = $this->options->get_option( 'background_processing' );
		$new_value = $value === 'Enable' ? 'Yes' : 'No';

		$this->options->update_option( 'background_processing', $new_value );
	}

	/**
	 * Remove legacy settings in the custom fonts data
	 *
	 * @since 6.0
	 */
	protected function upgrade_custom_fonts() {
		/** @var Model_Custom_Fonts $custom_font_model */
		$custom_font_model = \GPDFAPI::get_mvc_class( 'Model_Custom_Fonts' );

		$fonts = $this->options->get_option( 'custom_fonts', [] );

		foreach ( $fonts as &$font ) {
			if ( isset( $font['shortname'] ) ) {
				unset( $font['shortname'] );
			}

			$font['id'] = $custom_font_model->get_font_short_name( $font['font_name'] );
		}

		$this->options->update_option( 'custom_fonts', $fonts );
	}
}
