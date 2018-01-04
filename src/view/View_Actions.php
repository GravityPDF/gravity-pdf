<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;

/**
 * Actions View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
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
	 * @param  string $type        The action ID
	 * @param  string $button_text The primary button text
	 * @param  string $dismissal   Whether the dismissal button should be shown. Valid arguments are 'enabled' or 'disabled'
	 *
	 * @return string              The action_buttons HTML
	 *
	 * @since 4.0
	 */
	public function get_action_buttons( $type, $button_text, $dismissal = 'enabled' ) {

		return $this->load( 'action_buttons', [
			'type'        => $type,
			'button_text' => $button_text,
			'dismissal'   => $dismissal,
		], false );

	}

	/**
	 * Load our Review Plugin Notice
	 *
	 * @param  string $type        The action ID
	 * @param  string $button_text The primary button text
	 *
	 * @return string              The notice HTML
	 *
	 * @since 4.0
	 */
	public function review_plugin( $type, $button_text ) {

		$html = $this->load( 'review_plugin', [], false );
		$html .= $this->get_action_buttons( $type, $button_text );

		return $html;
	}

	/**
	 * Load our v3 to v4 Migration Notice
	 *
	 * @param  string $type        The action ID
	 * @param  string $button_text The primary button text
	 *
	 * @return string              The notice HTML
	 *
	 * @since 4.0
	 */
	public function migration( $type, $button_text ) {

		$html = $this->load( 'migration', [], false );
		$html .= $this->get_action_buttons( $type, $button_text, 'disabled' );

		return $html;
	}
}
