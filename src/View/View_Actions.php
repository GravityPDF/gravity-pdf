<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;

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
 * Controls the Gravity PDF Actions Display
 *
 * @since 4.0
 */
class View_Actions extends Helper_Abstract_View {
	/**
	 * Set the view's name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_type = 'Actions';

	/**
	 * Add our primary button and an opt-our dismissal button
	 *
	 * @param string $type        The action ID
	 * @param string $button_text The primary button text
	 * @param string $dismissal   Whether the dismissal button should be shown. Valid arguments are 'enabled' or 'disabled'
	 *
	 * @return string              The action_buttons HTML
	 *
	 * @since 4.0
	 */
	public function get_action_buttons( $type, $button_text, $dismissal = 'enabled' ) {

		return $this->load(
			'action_buttons',
			[
				'type'        => $type,
				'button_text' => $button_text,
				'dismissal'   => $dismissal,
			],
			false
		);

	}

	/**
	 * Load our Core Font Installer
	 *
	 * @param string $type        The action ID
	 * @param string $button_text The primary button text
	 *
	 * @return string              The notice HTML
	 *
	 * @since 5.0
	 */
	public function core_font( $type, $button_text ) {

		$html  = $this->load( 'core_font', [], false );
		$html .= $this->get_action_buttons( $type, $button_text, 'disabled' );

		return $html;
	}
}
