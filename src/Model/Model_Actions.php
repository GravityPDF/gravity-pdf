<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Options_Fields;
use GPDFAPI;

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
 * Model_Actions
 *
 * Handles the grunt work of our one-time actions
 *
 * @since 4.0
 */
class Model_Actions extends Helper_Abstract_Model {

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var Helper_Notices
	 *
	 * @since 4.0
	 */
	protected $notices;

	/**
	 * Setup our class by injecting all our dependencies
	 *
	 * @param Helper_Data             $data    Our plugin data store
	 * @param Helper_Abstract_Options $options Our options class which allows us to access any settings
	 * @param Helper_Notices          $notices Our notice class used to queue admin messages and errors
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Data $data, Helper_Abstract_Options $options, Helper_Notices $notices ) {

		/* Assign our internal variables */
		$this->data    = $data;
		$this->options = $options;
		$this->notices = $notices;
	}

	/**
	 * Check if the current notice has already been dismissed
	 *
	 * @param string $type The current notice ID
	 *
	 * @return boolean       True if dismissed, false otherwise
	 *
	 * @since 4.0
	 */
	public function is_notice_already_dismissed( $type ) {

		$dismissed_notices = $this->options->get_option( 'action_dismissal', [] );

		if ( isset( $dismissed_notices[ $type ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Mark the current notice as being dismissed
	 *
	 * @param string $type The current notice ID
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function dismiss_notice( $type ) {

		$dismissed_notices          = $this->options->get_option( 'action_dismissal', [] );
		$dismissed_notices[ $type ] = $type;
		$this->options->update_option( 'action_dismissal', $dismissed_notices );
	}

	/**
	 * Check if one of the core fonts exists in the fonts directory
	 *
	 * @return bool
	 *
	 * @since 5.0
	 */
	public function core_font_condition() {

		$misc = GPDFAPI::get_misc_class();

		/* Check if one of the core fonts already exists */
		if ( ! is_file( $this->data->template_font_location . 'DejaVuSansCondensed.ttf' ) && ! $misc->is_gfpdf_settings_tab( 'tools' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Redirect user to our font installer tool
	 *
	 * @since 5.0
	 */
	public function core_font_redirect() {
		wp_safe_redirect( admin_url( 'admin.php?page=gf_settings&subview=PDF&tab=tools#/downloadCoreFonts' ) );
		exit;
	}

}
