<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Fields;

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
 * Controls the display and output of a Gravity Form field
 *
 * @since 4.0
 */
class Field_V3_List extends Field_List {

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
				<li><?php echo esc_html( $item ); ?></li>
			<?php endforeach; ?>
		</ul>
		<?php

		/* get buffer and return HTML */

		return Helper_Abstract_Fields::html( ob_get_clean() );
	}
}
