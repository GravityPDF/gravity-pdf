
  <h3>
    <span>
      <i class="fa fa-cog"></i>
      <?php _e('General Settings', 'pdfextended'); ?>
    </span>
  </h3>

<form method="post" action="options.php">
	<?php settings_errors(); ?>
	<?php settings_fields( 'gfpdf_settings' ); ?>
	<table id="pdf-general" class="form-table">		
		<?php do_settings_fields('gfpdf_settings_general', 'gfpdf_settings_general'); ?>
	</table>

	<div class="hr-divider"></div>

  <h3>
    <span>
      <i class="fa fa-lock"></i>
      <?php _e('Security Settings', 'pdfextended'); ?>
    </span>
  </h3>

	<table id="pdf-general-security" class="form-table">		
		<?php do_settings_fields('gfpdf_settings_general_security', 'gfpdf_settings_general_security'); ?>
	</table>	

	<?php submit_button(); ?>
</form>