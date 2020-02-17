<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_View;

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
