<?php

/**
 * General Settings View
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var $args array */

GFCommon::display_admin_message();

?>

<div id="pdfextended-settings">

	<!-- Prevent Firefox auto-filling fields on refresh. @see https://stackoverflow.com/a/44504822/1614565 -->
	<form name="gfpdf-settings-form-<?= rand() ?>" class="gform_settings_form" method="post" action="options.php">
		<?php settings_fields( 'gfpdf_settings' ); ?>

		<?= $args['menu'] ?>
		<?= $args['content'] ?>

		<div id="submit-and-promo-container">
			<?php if ( $args['edit_cap'] ): ?>
				<input type="submit" name="submit" id="submit" value="<?= __( 'Save Settings  →', 'gravityforms' ) ?>" class="button primary large">
			<?php endif; ?>

			<div class="extensions-upsell">
				<a href="https://gravitypdf.com/store/">
					<?php esc_html_e( 'Want more features? Take a look at our addons.', 'gravity-forms-pdf-extended' ); ?>
				</a>
			</div>
		</div>
	</form>

	<?php
	/* See https://docs.gravitypdf.com/v6/developers/actions/gfpdf_post_general_settings_page for more details about this action */
	do_action( 'gfpdf_post_general_settings_page' );
	?>
</div>
