<?php

/**
 * Tools Settings View
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

<?php $this->tabs(); ?>


<div id="pdfextended-settings">
	<h3>
		<span>
			<i class="fa fa-cog"></i>
			<?php esc_html_e( 'Tools', 'gravity-forms-pdf-extended' ); ?>
		</span>
	</h3>

	<form method="post">

		<?php settings_fields( 'gfpdf_settings' ); ?>

		<table id="pdf-tools" class="widefat gfpdf_table">
			<thead>
				<tr>
					<th colspan="2"><?php esc_html_e( 'Tools', 'gravity-forms-pdf-extended' ); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php do_settings_fields( 'gfpdf_settings_tools', 'gfpdf_settings_tools' ); ?>
			</tbody>
		</table>

	</form>


	<?php if ( $args['custom_template_setup_warning'] ): ?>
		<!-- only show custom template warning if user has already installed them once -->
		<div id="setup-templates-confirm" title="<?php esc_html_e( 'Setup Custom Templates', 'gravity-forms-pdf-extended' ); ?>" style="display: none;">
			<?php printf( esc_html__( 'During the setup process any of the following templates stored in %1$s will be overridden. If you have modified any of the following template or template configuration files %2$smake a backup before continuing%3$s.', 'gravity-forms-pdf-extended' ), '<br><code>' . $args['template_directory'] . '</code>', '<strong>', '</strong>' ); ?>

			<?php if ( sizeof( $args['template_files'] ) > 0 ): ?>
				<ul>
					<?php foreach ( $args['template_files'] as $file ): ?>
						<li><?php echo basename( $file ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div id="manage-font-files" title="<?php esc_html_e( 'Manage Fonts', 'gravity-forms-pdf-extended' ); ?>" style="display: none;">
		<?php esc_html_e( 'Manage all your custom Gravity PDF fonts in one place.', 'gravity-forms-pdf-extended' ); ?> <?php esc_html_e( 'Only .ttf font files are supported and they MUST be uploaded through your media library (no external links).', 'gravity-forms-pdf-extended' ); ?>

		<div id="font-list">
			<!-- backbone to manage font list -->
		</div>

		<div id="font-add-list">
			<!-- backbone managed -->
		</div>
	</div>

	<?php
	/* See https://gravitypdf.com/documentation/v5/gfpdf_post_tools_settings_page/ for more details about this action */
	do_action( 'gfpdf_post_tools_settings_page' );
	?>
</div>

<script type="text/template" id="GravityPDFFontsEmpty">
	<div id="font-empty">
		<?php printf( esc_html__( 'Looks bare in here!%s Click "Add Font" below to get started.', 'gravity-forms-pdf-extended' ), '<br>' ); ?>
	</div>
</script>

<script type="text/template" id="GravityPDFFonts">
	<a href="#" class="font-name"><i class="fa fa-angle-right"></i><span name="font_name">{{- model.get('font_name') }}</span></a>
	<div class="font-settings" style="display: none">

		<form method="post">
			<input type="hidden" name="wpnonce" value="<?php echo wp_create_nonce( 'gfpdf_font_nonce' ); ?>"/>

			<div class="font-selector">
				<label><?php esc_html_e( 'Font Name', 'gravity-forms-pdf-extended' ); ?> <span class="gfield_required">*</span></label>
				<input type="text" required="required" value="{{- model.get('font_name') }}" name="font_name" class="regular-text font-name-field">
				<span class="gf_settings_description"><label><?php esc_html_e( 'Only alphanumeric characters and spaces are accepted.', 'gravity-forms-pdf-extended' ); ?></label></span>
			</div>

			<div class="font-selector">
				<label><?php esc_html_e( 'Regular', 'gravity-forms-pdf-extended' ); ?> <span class="gfield_required">*</span></label>
				<input type="text" value="{{- model.get('regular') }}" required="required" name="regular" class="regular-text">
				<span>
					<input type="button"
							 data-uploader-button-text="<?php esc_attr_e( 'Select Font', 'gravity-forms-pdf-extended' ); ?>"
							 data-uploader-title="<?php esc_attr_e( 'Select Font', 'gravity-forms-pdf-extended' ); ?>"
							 value="<?php esc_attr_e( 'Select Font', 'gravity-forms-pdf-extended' ); ?>"
							 class="gfpdf_settings_upload_button button-secondary">
				</span>
			</div>

			<div class="font-selector">
				<label><?php esc_html_e( 'Italics', 'gravity-forms-pdf-extended' ); ?></label>
				<input type="text" value="{{- model.get('italics') }}" name="italics" class="regular-text">
				<span>
					<input type="button"
						   data-uploader-button-text="<?php esc_attr_e( 'Select Font', 'gravity-forms-pdf-extended' ); ?>"
						   data-uploader-title="<?php esc_attr_e( 'Select Font', 'gravity-forms-pdf-extended' ); ?>"
						   value="<?php esc_attr_e( 'Select Font', 'gravity-forms-pdf-extended' ); ?>"
						   class="gfpdf_settings_upload_button button-secondary">
				</span>
			</div>

			<div class="font-selector">
				<label><?php esc_html_e( 'Bold', 'gravity-forms-pdf-extended' ); ?></label>
				<input type="text" value="{{- model.get('bold') }}" name="bold" class="regular-text">
				<span>
					<input type="button"
							 data-uploader-button-text="<?php esc_attr_e( 'Select Font', 'gravity-forms-pdf-extended' ); ?>"
							 data-uploader-title="<?php esc_attr_e( 'Select Font', 'gravity-forms-pdf-extended' ); ?>"
							 value="<?php esc_attr_e( 'Select Font', 'gravity-forms-pdf-extended' ); ?>"
							 class="gfpdf_settings_upload_button button-secondary">
				</span>
			</div>

			<div class="font-selector">
				<label><?php esc_html_e( 'Bold Italics', 'gravity-forms-pdf-extended' ); ?></label>
				<input type="text" value="{{- model.get('bolditalics') }}" name="bolditalics" class="regular-text">
				<span>
					<input type="button"
							 data-uploader-button-text="<?php esc_attr_e( 'Select Font', 'gravity-forms-pdf-extended' ); ?>"
							 data-uploader-title="<?php esc_attr_e( 'Select Font', 'gravity-forms-pdf-extended' ); ?>"
							 value="<?php esc_attr_e( 'Select Font', 'gravity-forms-pdf-extended' ); ?>"
							 class="gfpdf_settings_upload_button button-secondary">
				</span>
			</div>

			<div class="font-submit">
				<button class="button button-primary"><?php esc_html_e( 'Save Font', 'gravity-forms-pdf-extended' ); ?></button>
			</div>

		</form>

		<div class="css-usage">
			<b>Custom Template Usage</b><br>
			<input type="text" onclick="jQuery(this).select();" onfocus="jQuery(this).select();" readonly="readonly" name="usage" value="">
		</div>
	</div>

	<a href="#" class="delete-font"><i class="fa fa-trash-o"></i></a>
</script>

<div id="delete-confirm" title="<?php esc_attr_e( 'Delete Font?', 'gravity-forms-pdf-extended' ); ?>" style="display: none;">
	<?php printf( esc_html__( "Warning! You are about to delete this Font. Select 'Delete' to delete, 'Cancel' to stop.", 'gravity-forms-pdf-extended' ), '<strong>', '</strong>' ); ?>
</div>
