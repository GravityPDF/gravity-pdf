<?php

/**
 * License Settings View
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

/** @var $args array */

GFCommon::display_admin_message();

?>

<div id="pdfextended-settings">
	<form method="post" class="gform_settings_form" action="options.php">
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

		<?php if ( $args['edit_cap'] ): ?>
			<div id="submit-and-promo-container">
				<input type="submit" name="submit" id="submit" value="<?php echo esc_html__( 'Save Settings  →', 'gravityforms' ); ?>" class="button primary large">
			</div>
		<?php endif; ?>
	</form>

	<?php
	do_action( 'gfpdf_post_license_settings_page' );
	?>
</div>
