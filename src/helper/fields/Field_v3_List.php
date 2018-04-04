<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Fields;

/**
 * Gravity Forms Field
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
 * Controls the display and output of a Gravity Form field
 *
 * @since 4.0
 */
class Field_v3_List extends Field_List {

	/**
	 * Display the HTML version of this field
	 *
	 * @param string $value
	 * @param bool   $label
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function html( $value = '', $label = true ) {

		/* exit early if list field is empty */
		if ( $this->is_empty() ) {
			return parent::html( '' );
		}

		/* get out field value */
		$value   = $this->value();
		$columns = is_array( $value[0] );

		/* Check if we have columns or not */
		if ( $columns ) {
			/* use the parent HTML */
			return parent::html();
		}

		/* Start buffer and generate a the single list bullet list */
		ob_start();
		?>

		<ul class="bulleted single-column-list">
			<?php foreach ( $value as $item ) : ?>
				<li><?php echo $item; ?></li>
			<?php endforeach; ?>
		</ul>
		<?php

		/* get buffer and return HTML */

		return Helper_Abstract_Fields::html( ob_get_clean() );
	}
}
