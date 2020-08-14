<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Options;

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
 * Class Controller_Upgrade_Routines
 *
 * @package GFPDF\Controller
 */
class Controller_Upgrade_Routines {

	/**
	 * @var Helper_Abstract_Options
	 */
	protected $options;

	public function __construct( Helper_Abstract_Options $options ) {
		$this->options = $options;
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
			$this->enable_vendor_aliasing();
		}
	}

	/**
	 * Add global flag to enable vendor aliasing
	 *
	 * @since 6.0
	 */
	protected function enable_vendor_aliasing(): void {
		$this->options->update_option( 'vendor_aliasing', true );
	}

}
