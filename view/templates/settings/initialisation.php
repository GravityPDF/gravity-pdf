<?php

 /*
  * Template: Initialisation
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
  
 <h2><?php ($gfpdfe_data->is_initialised) ? _e('Re-Initialise Plugin or Fonts', 'pdfextended') : _e('Initialise Plugin', 'pdfextended'); ?></h2>
          
			<?php if($gfpdfe_data->is_initialised): ?>
            		<p><?php _e('Is the plugin not working as it should? Try reinitialise and see if that helps. ', 'pdfextended'); ?></p>
            <?php else: ?>
                   <p><?php _e('Before you can use Gravity Forms PDF Extended it needs to be initialised. Initialisation does a number of important things, including:', 'pdfextended'); ?></p>
                   
                   <ol>
                        <li><strong><?php _e('Fresh Installation', 'pdfextended'); ?></strong>: <?php _e('Copies all the required template and configuration files to a folder called PDF_EXTENDED_TEMPLATE in your active theme\'s directory.', 'pdfextended'); ?><br />
                            <strong><?php _e('Upgrading', 'pdfextended'); ?></strong>: <?php _e('Copies the latest default and example templates, as well as the template.css file to the PDF_EXTENDED_TEMPLATE folder.', 'pdfextended'); ?> <strong><?php _e('If you modified these files please back them up before re-initialising as they will be removed', 'pdfextneded'); ?></strong>.
                        </li>
                        <li><?php _e('Unzips the mPDF package', 'pdfextended'); ?></li>
                        <li><?php _e('Installs any fonts found in the PDF_EXTENDED_TEMPLATE/fonts/ folder', 'pdfexnteded'); ?></li>                
                   </ol>		  	
            <?php endif; ?>
			<form method="post">
                <?php wp_nonce_field('gfpdf_deploy_nonce_action','gfpdf_deploy_nonce'); ?>
                <input type="hidden" name="gfpdf_deploy" value="1">
                <?php 
				
				/*
				 * Remove the cancel feature for the moment
				 *
				
				if(get_option('gf_pdf_extended_deploy') == 'no') { ?>				
                <input type="submit" value="Cancel Deployment" class="button" id="cancelupgrade" name="cancel">                
				<?php } */ ?>                                                
                <input type="submit" value="<?php ($gfpdfe_data->is_initialised) ? _e('Re-Initialise Plugin', 'pdfextended') : _e('Initialise Plugin', 'pdfextended'); ?>" class="button" id="upgrade" name="upgrade">
                
                <input type="submit" value="Initialise Fonts Only" class="button" id="font-initialise" name="font-initialise">                
          </form>   
     
