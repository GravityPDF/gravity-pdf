<?php

/**
 * Shows a warning message once there's no valid pdfs that passed its conditional logic
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="gfpdf_detailed_pdf_container">
	<?= esc_html__( 'No valid PDFs found.', 'gravity-forms-pdf-extended' ); ?>
</div>
