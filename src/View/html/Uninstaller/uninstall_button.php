<?php

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
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
		<div class="addon-logo dashicons"><?= $args['icon'] ?></div>

		<div class="addon-uninstall-text">
			<h4 class="gform-settings-panel__title"><?= $args['title'] ?></h4>
			<div><?php printf( esc_html__( 'This operation deletes ALL %s settings.', 'gravityforms' ), $args['title'] ); ?></div>
		</div>

		<div class="addon-uninstall-button">
			<input id="addon" name="addon" type="hidden" value="<?= $args['title']; ?>">

			<button
					type="submit"
					aria-label="<?php printf( esc_html__( 'Uninstall %s', 'gravityforms' ), $args['title'] ); ?>"
					name="uninstall_addon"
					value="uninstall"
					class="button uninstall-addon red"
					onclick="return confirm('<?= esc_js( __( 'This operation deletes ALL Gravity PDF settings and deactivates the plugin. If you continue, all settings, configuration, custom templates and fonts will be removed.', 'gravity-forms-pdf-extended' ) ) ?>');"
					onkeypress="return confirm('<?= esc_js( __( 'This operation deletes ALL Gravity PDF settings and deactivates the plugin. If you continue, all settings, configuration, custom templates and fonts will be removed.', 'gravity-forms-pdf-extended' ) ) ?>');">
				<i class="dashicons dashicons-trash"></i>
				<?php esc_attr_e( 'Uninstall', 'gravityforms' ); ?>
			</button>
		</div>
	</div>
</form>
