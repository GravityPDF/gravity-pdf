<?php

/**
 * The HTML mark-up to display our core PDF page
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

<div class="row-separator">
	<h3 class="gfpdf-page gfpdf-field <?php echo $classes; ?>">
		<?php echo $form['pagination']['pages'][ $page ]; ?>
	</h3>
</div>
