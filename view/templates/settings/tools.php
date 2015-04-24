
  <h3>
    <span>
      <i class="fa fa-cog"></i>
      <?php _e('General Settings', 'pdfextended'); ?>
    </span>
  </h3>

<form method="post" action="options.php">
	<?php settings_errors(); ?>
	<?php settings_fields( 'gfpdf_settings' ); ?>

	<table id="pdf-tools" class="widefat gfpdfe_table">		
    <thead>
      <tr>
        <th colspan="2"><?php _e( 'Tools', 'pdfextended' ); ?></th>
      </tr>
    </thead> 
    
    <tbody>   
		  <?php do_settings_fields('gfpdf_settings_tools', 'gfpdf_settings_tools'); ?>
    </tbody>
	</table>

	<?php submit_button(); ?>
</form>