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

$settings = function( $id, $output_title = false ) {
	global $wp_settings_fields;

	if ( ! isset( $wp_settings_fields[ $id ][ $id ] ) ) {
		return;
	}

	foreach ( (array) $wp_settings_fields[ $id ][ $id ] as $field ) {
		$class = '';
		if ( ! empty( $field['args']['class'] ) ) {
			$class = ' class="' . esc_attr( $field['args']['class'] ) . '"';
		}
		?>

		<div<?= $class ?>>
			<?php if ( $output_title ): ?>
				<div class='gform-settings-panel__title'><?= $field['title'] ?></div>
			<?php endif; ?>

			<?php call_user_func( $field['callback'], $field['args'] ) ?>
		</div>
		<?php
	}
};

?>

<div id="pdfextended-settings">

	<form class="gform_settings_form" method="post" action="options.php">
		<?php settings_fields( 'gfpdf_settings' ); ?>

		<fieldset id="" class="gform-settings-panel gform-settings-panel--full">
			<header class="gform-settings-panel__header">
				<legend class="gform-settings-panel__title">Default PDF Options</legend>
			</header>

			<div class="gform-settings-panel__content gform_settings_form">
				<div class="gform-settings-description gform-settings-panel--full">Control the default settings to use when you create new PDFs on your forms.</div>
				<?php $settings( 'gfpdf_settings_general', true ); ?>
			</div>
		</fieldset>

		<fieldset id="" class="gform-settings-panel gform-settings-panel--full">
			<header class="gform-settings-panel__header">
				<legend class="gform-settings-panel__title">Entry View</legend>
			</header>

			<div class="gform-settings-panel__content">
				<?php $settings( 'gfpdf_settings_general_view' ); ?>
			</div>
		</fieldset>

		<fieldset id="" class="gform-settings-panel gform-settings-panel--half">
			<header class="gform-settings-panel__header">
				<legend class="gform-settings-panel__title">
					<?= esc_html__( 'Background Processing', 'gravity-forms-pdf-extended' ) ?>
				</legend>
			</header>

			<div class="gform-settings-panel__content">
				<?php $settings( 'gfpdf_settings_general_background_processing' ); ?>
			</div>
		</fieldset>

		<fieldset id="" class="gform-settings-panel gform-settings-panel--half">
			<header class="gform-settings-panel__header">
				<legend class="gform-settings-panel__title">Debug Mode</legend>
			</header>

			<div class="gform-settings-panel__content">
				<?php $settings( 'gfpdf_settings_general_debug_mode' ); ?>
			</div>
		</fieldset>


		<fieldset id="" class="gform-settings-panel gform-settings-panel--full gform-settings-panel--collapsed">
			<header class="gform-settings-panel__header">
				<legend class="gform-settings-panel__title">Security</legend>

				<span class="gform-settings-panel__collapsible-control">
					<input type="checkbox" name="gform_settings_section_collapsed_security" id="form_settings_section_collapsed_security" value="1" onclick="this.checked ? this.closest( '.gform-settings-panel' ).classList.add( 'gform-settings-panel--collapsed' ) : this.closest( '.gform-settings-panel' ).classList.remove( 'gform-settings-panel--collapsed' )" checked="">
					<label class="gform-settings-panel__collapsible-toggle" for="form_settings_section_collapsed_security"><span class="screen-reader-text">Toggle Security Section</span></label>
				</span>
			</header>

			<div class="gform-settings-panel__content gform_settings_form">
				<?php $settings( 'gfpdf_settings_general_security', true ); ?>
			</div>
		</fieldset>

		<?php
		if ( $args['edit_cap'] ) {
			submit_button();
		}
		?>

		<div class="extensions-upsell">
			<a href="https://gravitypdf.com/store/">
				<?php esc_html_e( 'Want more features? Take a look at our addons.', 'gravity-forms-pdf-extended' ); ?>
			</a>
		</div>
	</form>

	<?php
	/* See https://gravitypdf.com/documentation/v5/gfpdf_post_general_settings_page/ for more details about this action */
	do_action( 'gfpdf_post_general_settings_page' );
	?>
</div>
