<?php

/**
 * General Settings View
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

/** @var $args array */

GFCommon::display_admin_message();

?>

<div id="pdfextended-settings">

	<!-- Prevent Firefox auto-filling fields on refresh. @see https://stackoverflow.com/a/44504822/1614565 -->
	<form name="gfpdf-settings-form-<?php echo esc_attr( wp_rand() ); ?>" class="gform_settings_form" method="post" action="options.php">
		<?php settings_fields( 'gfpdf_settings' ); ?>

		<?php do_action( 'gfpdf_settings_sub_menu' ); ?>

		<?php
		/** @since 6.4.0 */
		if ( isset( $args['callback'] ) ) {
			call_user_func_array( $args['callback'], $args['callback_args'] ?? [] );
		}

		/** @deprecated 6.4.0 */
		if ( isset( $args['content'] ) ) {
			echo wp_kses_post( $args['content'] );
		}
		?>

		<div id="submit-and-promo-container">
			<?php if ( $args['edit_cap'] ): ?>
				<input type="submit" name="submit" id="submit" value="<?php echo esc_attr__( 'Save Settings  →', 'gravityforms' ); ?>" class="button primary large">
			<?php endif; ?>

			<div class="extensions-upsell">
				<a href="https://gravitypdf.com/store/">
					<?php esc_html_e( 'Want more features? Take a look at our addons.', 'gravity-pdf' ); ?>
				</a>
			</div>
		</div>
	</form>

	<?php
	/* See https://docs.gravitypdf.com/v6/developers/actions/gfpdf_post_general_settings_page for more details about this action */
	do_action( 'gfpdf_post_general_settings_page' );
	?>
</div>
