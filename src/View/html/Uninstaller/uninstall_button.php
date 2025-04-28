<?php

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $args */

?>

<form action="" method="post" class="gform-settings-panel gform-settings-panel__addon-uninstall">
	<?php wp_nonce_field( 'uninstall', 'gf_addon_uninstall' ); ?>

	<div class="gform-settings-panel__content">
		<div class="addon-logo dashicons"><?php echo wp_kses_post( $args['icon'] ); ?></div>

		<div class="addon-uninstall-text">
			<h4 class="gform-settings-panel__title"><?php echo esc_attr( $args['title'] ); ?></h4>
			<div><?php echo esc_html( sprintf( __( 'This operation deletes ALL %s settings.', 'gravityforms' ), $args['title'] ) ); ?></div>
		</div>

		<div class="addon-uninstall-button">
			<input id="addon" name="addon" type="hidden" value="<?php echo esc_attr( $args['title'] ); ?>">

			<button
					type="submit"
					aria-label="<?php echo esc_attr( sprintf( __( 'Uninstall %s', 'gravityforms' ), $args['title'] ) ); ?>"
					name="uninstall_addon"
					value="uninstall"
					class="button uninstall-addon red"
					onclick="return confirm('<?php echo esc_js( __( 'This operation deletes ALL Gravity PDF settings and deactivates the plugin. If you continue, all settings, configuration, custom templates and fonts will be removed.', 'gravity-pdf' ) ); ?>');"
					onkeypress="return confirm('<?php echo esc_js( __( 'This operation deletes ALL Gravity PDF settings and deactivates the plugin. If you continue, all settings, configuration, custom templates and fonts will be removed.', 'gravity-pdf' ) ); ?>');">
				<i class="dashicons dashicons-trash"></i>
				<?php esc_html_e( 'Uninstall', 'gravityforms' ); ?>
			</button>
		</div>
	</div>
</form>
