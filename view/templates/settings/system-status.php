<?php

 /*
  * Template: System Status
  * Module: Settings Page
  *
  */
  
  /*
   * Don't run if the correct class isn't present
   */
  if(!class_exists('GFPDF_Settings_Model'))
  {
	 exit;  
  }
  
  ?>
  
<h2><?php _e('System Status', 'pdfextended'); ?></h2>

<div id="pdf-system-status">
           <p><label><?php _e('Wordpress Version', 'pdfextended'); ?>:</label> <strong><?php echo $gfpdfe_data->wp_version; ?></strong> <span class="<?php echo ($gfpdfe_data->wp_is_compatible === true) ? 'fa fa-check-circle' : 'fa fa-times-circle'; ?>"></span>
            <?php if($gfpdfe_data->wp_is_compatible === false): ?>
            <br />
            <span class="details"><?php _e('Wordpress Version '. GF_PDF_EXTENDED_WP_SUPPORTED_VERSION . ' is required to use this plugin.', 'pdfextended'); ?>
            <?php endif; ?>
            </p>
            
            
            <p><label><?php _e('Gravity Forms', 'pdfextended'); ?>:</label> <strong>
			<?php if($gfpdfe_data->gf_installed === false): ?>
            <?php _e('Not Installed', 'pdfextended'); ?></strong> <span class="fa fa-times-circle"></span>
            <br />
            <span class="details"><?php _e('Gravity Forms '. GF_PDF_EXTENDED_SUPPORTED_VERSION . ' is required to use this plugin. <a href="https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=235154" target="ejejcsingle">Upgrade today</a>.', 'pdfextended'); ?></span>
                        
            <?php else: 
            echo $gfpdfe_data->gf_version; ?> </strong>			           
            <span class="<?php echo ($gfpdfe_data->gf_is_compatible === true) ? 'fa fa-check-circle' : 'fa fa-times-circle'; ?>"></span>
				<?php if($gfpdfe_data->gf_is_compatible === false): ?>
                <br />
                <span class="details"><?php _e('Gravity Forms '. GF_PDF_EXTENDED_SUPPORTED_VERSION . ' is required to use this plugin. <a href="https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=235154" target="ejejcsingle">Upgrade today</a>.', 'pdfextended'); ?></span>
                <?php endif; ?>
            <?php endif; ?>
            </p>     
            
                   
            <p><label><?php _e('PHP Version', 'pdfextended'); ?>:</label> <strong><?php echo $gfpdfe_data->php_version; ?></strong> <span class="<?php echo ($gfpdfe_data->php_version_compatible === true) ? 'fa fa-check-circle' : 'fa fa-times-circle'; ?>"></span>
            <?php if($gfpdfe_data->php_version_compatible === false): ?>
            <br />
            <span class="details"><?php _e('PHP Version '. GF_PDF_EXTENDED_PHP_SUPPORTED_VERSION . ' is required to use this plugin.', 'pdfextended'); ?></span>
            <?php endif; ?>
            </p>
            
            <p><label><?php _e('MB String', 'pdfextended'); ?>:</label> <strong><?php ($gfpdfe_data->mb_string_installed === true) ? _e('Yes', 'pdfextended') : _e('No', 'pdfextended'); ?></strong>
            <span class="<?php echo ($gfpdfe_data->mb_string_installed === true) ? 'fa fa-check-circle' : 'fa fa-times-circle'; ?>"></span>
            <?php if($gfpdfe_data->mb_string_installed === false): ?>
            <br />
            <span class="details"><?php _e('The PHP extension MB String and MB String Regex functions are required to use this plugin. Contact your web host to have it enabled.', 'pdfextended'); ?></span>
            <?php endif; ?>
            </p>   
            
            <p><label><?php _e('GD Library', 'pdfextended'); ?>:</label> <strong><?php ($gfpdfe_data->gd_installed  === true) ? _e('Yes', 'pdfextended') : _e('No', 'pdfextended'); ?></strong>
            <span class="<?php echo ($gfpdfe_data->gd_installed === true) ? 'fa fa-check-circle' : 'fa fa-times-circle'; ?>"></span>
            <?php if($gfpdfe_data->gd_installed === false): ?>
            <br />
            <span class="details"><?php _e('The PHP extension GD Library is required to use this plugin. Contact your web host to have it enabled.', 'pdfextended'); ?></span>
            <?php endif; ?>
            </p>      
            
            <?php
            $ram_icon = 'fa fa-check-circle';
            if($gfpdfe_data->ram_compatible === false)
            {
                $ram_icon = 'fa fa-exclamation-triangle';
                if($gfpdfe_data->ram_available < 64)
                {
                    $ram_icon = 'fa fa-times-circle';
                }
            }


            ?>

            <p><label><?php _e('Available RAM', 'pdfextended'); ?>:</label> 
            <strong>
                <?php if($gfpdfe_data->ram_available === -1): ?>
                    <?php echo __('Unlimited', 'pdfextended'); ?>
                <?php else: ?>
                    <?php echo $gfpdfe_data->ram_available; ?>MB
                <?php endif; ?>
            </strong> 
            <span class="<?php echo $ram_icon; ?>"></span>
            <?php if($gfpdfe_data->ram_compatible === false): ?>
            <br />
            <span class="details">
                <?php echo sprintf(__('We recommend you have 128MB of available RAM to run this plugin. The minimum system requirement is 64MB. %sNot sure what this means? Contact your web host and ask them to fix the issue.', 'pdfextended'), '<br />'); ?>
                <?php if($gfpdfe_data->ram_available >= 64 && $gfpdfe_data->ram_available < 128): ?>
                    <?php echo sprintf(__('%sNote: If you run less than 128MB, you risk PHP suffering a fatal error which will stop your website from running on Gravity Form pages.%s', 'pdfextended'), '<br /><b>', '</b>'); ?>
                <?php endif; ?>
            </span>
            <?php endif; ?>
            </p>       
            
            
            <?php if($gfpdfe_data->is_initialised === false): ?>			                
                    
                    <p><label><?php _e('Uploads Directory Writable?', 'pdfextended'); ?></label> <strong><?php ($gfpdfe_data->can_write_upload_dir  === true) ? _e('Yes', 'pdfextended') : _e('No', 'pdfextended'); ?></strong> <span class="<?php echo ($gfpdfe_data->can_write_upload_dir === true) ? 'fa fa-check-circle' : 'fa fa-exclamation-triangle'; ?>"></span>
                    <?php if($gfpdfe_data->can_write_theme_dir === false): ?>
                    <br />
                    <span class="details"><?php echo sprintf(__('Your upload folder is not writable by your web server. Check that "%s" is writable by your web server otherwise we will attempt to use the FTP installer to initialise.', 'pdfextended'), str_replace(ABSPATH, '', $gfpdfe_data->upload_dir) ); ?></span>
                    <?php endif; ?>
                    </p>              
            
            <?php else: ?>                 
             
                     <p><label><?php _e('PDF Output Directory Writable?', 'pdfextended'); ?></label> <strong><?php ($gfpdfe_data->can_write_output_dir  === true) ? _e('Yes', 'pdfextended') : _e('No', 'pdfextended'); ?></strong> <span class="<?php echo ($gfpdfe_data->can_write_output_dir === true) ? 'fa fa-check-circle' : 'fa fa-times-circle'; ?>"></span>
                    <?php if($gfpdfe_data->can_write_output_dir === false): ?>
                    <br />
                    <span class="details"><?php echo sprintf(__('The plugin\'s output folder is not writable by your web server. PDFs will not be attached to notifications until this problem is fixed. Check that "%s" is writable by your web server.', 'pdfextended'), str_replace(ABSPATH, '', $gfpdfe_data->template_location) ); ?></span>
                    <?php endif; ?>
                    </p>  
                    
                    <p><label><?php _e('PDF Font Directory Writable?', 'pdfextended'); ?></label> <strong><?php ($gfpdfe_data->can_write_font_dir  === true) ? _e('Yes', 'pdfextended') : _e('No', 'pdfextended'); ?></strong> <span class="<?php echo ($gfpdfe_data->can_write_font_dir === true) ? 'fa fa-check-circle' : 'fa fa-exclamation-triangle'; ?>"></span>
                    <?php if($gfpdfe_data->can_write_font_dir === false): ?>
                    <br />
                    <span class="details"><?php echo sprintf(__('The plugin\'s font folder is not writable by your web server. Check that "%s" is writable by your web server otherwise we will attempt to use the FTP installer to initialise.', 'pdfextended'),  str_replace(ABSPATH, '', $gfpdfe_data->template_font_location)  ); ?></span>
                    <?php endif; ?>
                    </p>   
                    
                    <p><label><?php _e('mPDF Temporary Directory Writable?', 'pdfextended'); ?></label> <strong><?php ($gfpdfe_data->can_write_pdf_temp_dir  === true) ? _e('Yes', 'pdfextended') : _e('No', 'pdfextended'); ?></strong> <span class="<?php echo ($gfpdfe_data->can_write_pdf_temp_dir === true) ? 'fa fa-check-circle' : 'fa fa-exclamation-triangle'; ?>"></span>
                    <?php if($gfpdfe_data->can_write_pdf_temp_dir === false): ?>
                    <br />
                    <span class="details"><?php echo sprintf(__('mPDF temporary directory not writable (%s). Memory and image processing time will increase.', 'pdfextended'), str_replace(ABSPATH, '', PDF_PLUGIN_DIR) . 'mPDF/tmp/'); ?></span>
                    <?php endif; ?>
                    </p>                             
                    
          <?php endif; ?>        
</div>          