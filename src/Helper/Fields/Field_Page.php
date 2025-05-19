<?php

namespace GFPDF\Helper\Fields;

use GFPDF\Helper\Helper_Abstract_Fields;
use GFPDF\Statics\Kses;

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
 * @since 6.10.1
 */
class Field_Page extends Helper_Abstract_Fields {

	/**
	 * Display the HTML version of this field
	 *
	 * @param string $value
	 * @param bool   $label
	 *
	 * @return string
	 *
	 * @since 6.10.1
	 */
	public function html( $value = '', $label = true ) {
		$page_number = $this->field->pageNumber;

		ob_start();
		?>
		<h3 id="<?php echo esc_attr( 'page-no-' . $page_number ); ?>" class="gfpdf-page gfpdf-field <?php echo esc_attr( $this->get_field_classes() ); ?>">
			<?php echo wp_kses_post( $this->value() ); ?>
		</h3>
		<?php

		/* Run it through a filter and output */
		$html = apply_filters( 'gfpdf_field_page_name_html', ob_get_clean(), $page_number, $this->form );

		if ( $this->get_output() ) {
			Kses::output( $html );
		}

		return $html;
	}

	/**
	 * Get the page label
	 *
	 * @return string
	 *
	 * @since 6.10.1
	 */
	public function value() {
		return $this->field->content;
	}

	/**
	 * Check if the Page is hidden, or if there are no filled in fields on that page/section
	 *
	 * @return bool
	 *
	 * @since 6.10.1
	 */
	public function is_empty() {
		if ( \GFFormsModel::is_page_hidden( $this->form, $this->field->pageNumber, [], $this->entry ) ) {
			return true;
		}

		/* todo: this doesn't account for all fields if there are Section Breaks on the page */
		if ( \GFCommon::is_section_empty( $this->field, $this->form, $this->entry ) ) {
			return true;
		}

		return false;
	}
}
