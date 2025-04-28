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
		esc_html__( 'To take advantage of automatic updates for your Gravity PDF extension(s), enter and save your license key(s) below. %1$sYou can find your purchased licenses in your GravityPDF.com account%2$s.', 'gravity-pdf' ),
		'<a href="https://gravitypdf.com/account/licenses/">',
		'</a>'
	);
	?>
</p>

<p>
	<?php esc_html_e( 'When a Gravity PDF extension is enabled, the plugin periodically polls GravityPDF.com over HTTPS for your license status and plugin updates. The only data sent is your website domain name and license key (if provided). To opt-out you need to deactivate all Gravity PDF extensions.', 'gravity-pdf' ); ?>
</p>
