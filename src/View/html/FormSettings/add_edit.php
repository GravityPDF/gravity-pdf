<?php

/**
 * The Add/Edit Form Settings View
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

global $wp_settings_fields;
?>

<!-- Merge tag functionality requires a global form object -->
<script type="text/javascript">
	<?php GFCommon::gf_global(); ?>
	<?php GFCommon::gf_vars(); ?>
	var form = <?php echo wp_json_encode( $args['form'] ); ?>;
	var gfpdf_current_pdf = <?php echo wp_json_encode( $args['pdf'] ); ?>;
	var entry_meta = <?php echo wp_json_encode( $args['entry_meta'] ); ?>;
	var gfpdf_extra_conditional_logic_options = <?php echo wp_json_encode( $args['extra_conditional_logic_options'] ); ?>;

	<?php GFFormSettings::output_field_scripts(); ?>
</script>

<?php GFFormSettings::page_header( $args['title'] ); ?>

<!-- Prevent Firefox auto-filling fields on refresh. @see https://stackoverflow.com/a/44504822/1614565 -->
<form name="gfpdf-settings-form-<?php echo esc_attr( wp_rand() ); ?>" method="post" id="gfpdf_pdf_form"
	  class="gform_settings_form <?php echo esc_attr( $args['form_classes'] ); ?>">

	<?php wp_nonce_field( 'gfpdf_save_pdf', 'gfpdf_save_pdf' ); ?>

	<input type="hidden" id="gform_pdf_id" name="gform_pdf_id" value="<?php echo esc_attr( $args['pdf_id'] ); ?>" />
	<input type="hidden" id="gform_id" name="gform_id" value="<?php echo esc_attr( $args['form']['id'] ); ?>" />

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
		<input type="submit" name="submit" id="submit" value="<?php echo esc_attr( $args['button_label'] ); ?>" class="button primary large">

		<div class="extensions-upsell">
			<a href="https://gravitypdf.com/store/">
				<?php esc_html_e( 'Want more features? Take a look at our addons.', 'gravity-pdf' ); ?>
			</a>
		</div>
	</div>
</form>

<?php GFFormSettings::page_footer(); ?>
