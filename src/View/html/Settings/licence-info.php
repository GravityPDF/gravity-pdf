<?php

/**
 * License Info
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.2
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p>
	<?php
	printf(
		esc_html__( 'To take advantage of automatic updates enter and save your license key(s) below. %1$sYou can find your purchased licenses in your GravityPDF.com account%2$s.', 'gravity-pdf' ),
		'<a href="https://gravitypdf.com/account/licenses/">',
		'</a>'
	);
	?>
</p>