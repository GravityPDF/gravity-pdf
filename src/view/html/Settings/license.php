<?php

/**
 * License Settings View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2018, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.2
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
    This file is part of Gravity PDF.

    Gravity PDF – Copyright (C) 2018, Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

?>

<?php $this->tabs(); ?>

<div id="pdfextended-settings">
	<h3>
		<span>
		    <i class="fa fa-key"></i>
			<?php esc_html_e( 'Licensing', 'gravity-forms-pdf-extended' ); ?>
		</span>
	</h3>

	<form method="post" action="options.php">
		<?php settings_fields( 'gfpdf_settings' ); ?>

        <p>
			<?php esc_html_e( 'To take advantage of automatic updates for your Graivty PDF add-ons, enter your license key(s) below.', 'gravity-forms-pdf-extended' ); ?>
			<?php esc_html_e( 'Your license key is located in your Purchase Confirmation email you received after you bought the add-on.', 'gravity-forms-pdf-extended' ); ?>
        </p>

		<table id="pdf-license" class="form-table">
			<?php do_settings_fields( 'gfpdf_settings_licenses', 'gfpdf_settings_licenses' ); ?>
		</table>

		<?php
			if ( $args['edit_cap'] ) {
				submit_button();
			}
		?>
	</form>

	<em>
        – <?php esc_html_e( 'By installing a Gravity PDF extension you are automatically giving permission for us to periodically poll GravityPDF.com via HTTPS for your current license status and any new plugin updates. The only personal data sent is your website domain name and license key. To opt-out you will need to deactivate all Gravity PDF extensions.', 'gravity-forms-pdf-extended' ); ?>
    </em>

	<?php
	/* @TODO */
	do_action( 'gfpdf_post_license_settings_page' );
	?>
</div>