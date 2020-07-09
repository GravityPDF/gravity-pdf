<?php

/**
 * General Settings View
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

<div id="pdfextended-settings">

	<!-- Prevent Firefox auto-filling fields on refresh. @see https://stackoverflow.com/a/44504822/1614565 -->
	<form name="gfpdf-settings-form-<?=rand() ?>" class="gform_settings_form" method="post" action="options.php">
		<?php settings_fields( 'gfpdf_settings' ); ?>

		<?= $content ?>

		<div id="submit-and-promo-container">
			<?php
			if ( $args['edit_cap'] ) {
				submit_button( null, 'primary', 'submit', false );
			}
			?>

			<div class="extensions-upsell">
				<a href="https://gravitypdf.com/store/">
					<?php esc_html_e( 'Want more features? Take a look at our addons.', 'gravity-forms-pdf-extended' ); ?>
				</a>
			</div>
		</div>
	</form>

	<?php
	/* See https://gravitypdf.com/documentation/v5/gfpdf_post_general_settings_page/ for more details about this action */
	do_action( 'gfpdf_post_general_settings_page' );
	?>
</div>
