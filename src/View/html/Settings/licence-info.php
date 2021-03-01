<?php

/**
 * License Info
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.2
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p>
	<?php esc_html_e( 'To take advantage of automatic updates for your Gravity PDF add-ons, enter your license key(s) below.', 'gravity-forms-pdf-extended' ); ?>
	<?php esc_html_e( 'Your license key is located in your Purchase Confirmation email you received after you bought the add-on.', 'gravity-forms-pdf-extended' ); ?>
</p>

<p>
	<?php esc_html_e( 'By installing a Gravity PDF extension you are automatically giving permission for us to periodically poll GravityPDF.com via HTTPS for your current license status and any new plugin updates. The only personal data sent is your website domain name and license key. To opt-out you will need to deactivate all Gravity PDF extensions.', 'gravity-forms-pdf-extended' ); ?>
</p>
