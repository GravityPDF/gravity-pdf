<?php

/**
 * The Add/Edit Form Settings View
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

global $wp_settings_fields;

?>

<!-- Merge tag functionality requires a global form object -->
<script type="text/javascript">
	<?php GFCommon::gf_global(); ?>
	<?php GFCommon::gf_vars(); ?>
	var form = <?= json_encode( $args['form'] ); ?>;
	var gfpdf_current_pdf = <?= json_encode( $args['pdf'] ); ?>;
	var entry_meta = <?= json_encode( $args['entry_meta'] ); ?>;

	<?php GFFormSettings::output_field_scripts(); ?>
</script>

<?php GFFormSettings::page_header( $args['title'] ); ?>

<!-- Prevent Firefox auto-filling fields on refresh. @see https://stackoverflow.com/a/44504822/1614565 -->
<form name="gfpdf-settings-form-<?= rand() ?>" method="post" id="gfpdf_pdf_form" class="gform_settings_form">

	<?php wp_nonce_field( 'gfpdf_save_pdf', 'gfpdf_save_pdf' ); ?>

	<input type="hidden" id="gform_pdf_id" name="gform_pdf_id" value="<?= esc_attr( $args['pdf_id'] ) ?>" />
	<input type="hidden" id="gform_id" name="gform_id" value="<?= esc_attr( $args['form']['id'] ) ?>" />

	<?= $args['content'] ?>

	<div id="submit-and-promo-container">
		<input type="submit" name="submit" id="submit" value="<?= $args['button_label']; ?>" class="button primary large">

		<div class="extensions-upsell">
			<a href="https://gravitypdf.com/store/">
				<?php esc_html_e( 'Want more features? Take a look at our addons.', 'gravity-forms-pdf-extended' ); ?>
			</a>
		</div>
	</div>
</form>

<?php GFFormSettings::page_footer(); ?>
