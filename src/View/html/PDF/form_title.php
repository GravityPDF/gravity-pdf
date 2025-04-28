<?php

/**
 * The HTML mark-up needed to display our core PDF title
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var $form array */

?>

<div class="row-separator">
	<h3 id="form_title"><?php echo esc_html( $form['title'] ); ?></h3>
</div>
