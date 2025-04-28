<?php

/**
 * The Review Plugin Notice
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

?>

<div style="font-size:15px; line-height: 25px" role="alert" aria-live="polite">

	<strong><?php esc_html_e( 'Gravity PDF needs to download the Core PDF fonts.', 'gravity-pdf' ); ?></strong>

	<br>

	<?php esc_html_e( 'Before you can generate a PDF using Gravity Forms, the core fonts need to be saved to your server. This only needs to be done once.', 'gravity-pdf' ); ?>

	<br>

	<a href="https://docs.gravitypdf.com/v6/users/core-pdf-fonts"><?php esc_html_e( 'Get more info.', 'gravity-pdf' ); ?></a>
</div>
