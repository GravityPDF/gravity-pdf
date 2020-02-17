<?php

/**
 * The Add/Edit Form Settings View
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

global $wp_settings_fields;

?>

<!-- Merge tag functionality requires a global form object -->
<script type="text/javascript">
	<?php GFCommon::gf_global(); ?>
	<?php GFCommon::gf_vars(); ?>
	var form = <?php echo json_encode( $args['form'] ); ?>;
	var gfpdf_current_pdf = <?php echo json_encode( $args['pdf'] ); ?>;
	var entry_meta = <?php echo json_encode( $args['entry_meta'] ); ?>;

	<?php GFFormSettings::output_field_scripts(); ?>
</script>

<?php GFFormSettings::page_header( $args['title'] ); ?>

<h3>
	<span>
	  <i class="fa fa-file-o"></i>
		<?php echo $args['title']; ?>
	</span>
</h3>


<form method="post" id="gfpdf_pdf_form">

	<div class="wp-filter gfpdf-tab-wrapper">
		<ul class="filter-links">
			<li id="gfpdf-general-nav">
				<a href="#gfpdf-general-options" class="current"><i class="fa fa-cog"></i> General</a>
			</li>

			<li id="gfpdf-appearance-nav">
				<a href="#gfpdf-appearance-options"><i class="fa fa-adjust"></i> Appearance</a>
			</li>

			<li id="gfpdf-custom-appearance-nav" 
			<?php
			if ( empty( $wp_settings_fields['gfpdf_settings_form_settings_custom_appearance']['gfpdf_settings_form_settings_custom_appearance'] ) ) :
				?>
				 style="display: none" <?php endif; ?>>
				<a href="#gfpdf-custom-appearance-options"><i class="fa fa-file-text-o"></i> Template</a>
			</li>

			<li id="gfpdf-advanced-nav">
				<a href="#gfpdf-advanced-pdf-options"><i class="fa fa-cogs"></i> Advanced</a>
			</li>
		</ul>


	</div>

	<?php wp_nonce_field( 'gfpdf_save_pdf', 'gfpdf_save_pdf' ); ?>

	<input type="hidden" id="gform_pdf_id" name="gform_pdf_id" value="<?php echo $args['pdf_id']; ?>"/>
	<input type="hidden" id="gform_id" name="gform_id" value="<?php echo $args['form']['id']; ?>"/>


	<div id="gfpdf-general-options" class="gfpdf-tab-container">

		<!-- display standard fields -->
		<table id="pdf-form-settings" class="form-table">
			<?php do_settings_fields( 'gfpdf_settings_form_settings', 'gfpdf_settings_form_settings' ); ?>
		</table>
	</div>

	<!-- display appearance fields -->
	<div id="gfpdf-appearance-options" class="gfpdf-tab-container">

		<table id="pdf-general-appearance" class="form-table">
			<?php do_settings_fields( 'gfpdf_settings_form_settings_appearance', 'gfpdf_settings_form_settings_appearance' ); ?>
		</table>
	</div>

	<!-- display template-specific options -->
	<div id="gfpdf-custom-appearance-options" class="gfpdf-tab-container">

		<table id="pdf-custom-appearance" class="form-table">
			<?php do_settings_fields( 'gfpdf_settings_form_settings_custom_appearance', 'gfpdf_settings_form_settings_custom_appearance' ); ?>
		</table>
	</div>

	<!-- display advanced fields -->
	<div id="gfpdf-advanced-pdf-options" class="gfpdf-tab-container">

		<table id="pdf-general-advanced" class="form-table">
			<?php do_settings_fields( 'gfpdf_settings_form_settings_advanced', 'gfpdf_settings_form_settings_advanced' ); ?>
		</table>
	</div>

	<div class="extensions-upsell">
		<a href="https://gravitypdf.com/extension-shop/">
			<?php esc_html_e( 'Want more features? See the Extension Shop.', 'gravity-forms-pdf-extended' ); ?>
		</a>
	</div>

	<p class="submit">
		<input class="button-primary" type="submit" value="<?php echo $args['button_label']; ?>" name="save"/>
	</p>
</form>


<?php GFFormSettings::page_footer(); ?>
