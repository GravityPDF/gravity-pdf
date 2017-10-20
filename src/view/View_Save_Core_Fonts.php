<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.3
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

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
 * Class View_Save_Core_Fonts
 *
 * @package GFPDF\View
 *
 * @since 5.0
 */
class View_Save_Core_Fonts extends Helper_Abstract_View {

	/**
	 * Set the view's name
	 *
	 * @var string
	 *
	 * @since 5.0
	 */
	protected $view_type = 'Core_Fonts';

	/**
	 * Setup the ReactJS DOM element for this feature
	 *
	 * @param $args
	 *
	 * @since 5.0
	 */
	public function core_fonts_setting( $args ) {
		if ( isset( $args['tooltip'] ) ) {
			echo '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post( $args['tooltip'] ) . '</span>';
		}
		?>
		<div id="gfpdf-install-core-fonts">
			<button class="button gfpdf-button" type="button">
				<?php esc_attr_e( 'Download Core Fonts', 'gravity-forms-pdf-extended' ); ?>
			</button>
		</div>
		<?php
	}
}
