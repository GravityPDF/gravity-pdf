<?php

/**
 * Shows a warning message when all the PDF conditional logic doesn't pass for the current entry
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="gfpdf_detailed_pdf_container">
	<?= esc_html__( 'No PDFs available for this entry.', 'gravity-forms-pdf-extended' ); ?>
</div>
