<?php

namespace GFPDF\Helper;

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
 * Class Helper_Abstract_Config_Settings
 *
 * @package GFPDF\Helper
 */
abstract class Helper_Abstract_Config_Settings implements Helper_Interface_Config_Settings {

	/**
	 * @var array Holds the current PDF settings
	 * @since 6.0
	 */
	protected $settings = [];

	/**
	 * Setter
	 *
	 * @since 6.0
	 */
	public function set_settings( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Getter
	 *
	 * @since 6.0
	 */
	public function get_settings(): array {
		return $this->settings;
	}
}
