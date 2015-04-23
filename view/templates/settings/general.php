
<form method="post" action="options.php">
	<?php settings_errors(); ?>
	<table class="form-table">
		<?php settings_fields( 'gfpdf_settings' ); ?>
		<?php do_settings_fields('gfpdf_settings_general', 'gfpdf_settings_general'); ?>
	</table>

	<?php submit_button(); ?>
</form>