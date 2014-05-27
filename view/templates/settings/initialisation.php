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
  
 <h2><?php ($gfpdfe_data->is_initialised) ? _e('Welcome to Gravity Forms PDF Extended', 'pdfextended') : _e('Initialise Plugin', 'pdfextended'); ?></h2>
          
			<?php if($gfpdfe_data->is_initialised): ?>

                <p><?php _e('The plugin has successfully installed and is ready to start automating your documents.', 'pdfextended'); ?></p>

                <h3><?php _e("What's next?", 'pdfextended'); ?></h3>
                <p><?php _e('The next step is to correctly configured the plugin so that you can generate PDF documents.', 'pdfextended'); ?> 
                <?php _e("To help you get started, we've put together a five-part video series with the most common plugin configurations.", 'pdfextended'); ?></p>
                <ol>
                  <li><a href="http://gravityformspdfextended.com/documentation-v3-x-x/standard-configuration/basics/"><?php _e('The Basics: Only Download PDF through the Admin Area', 'pdfextended'); ?></a></li>
                  <li><a href="http://gravityformspdfextended.com/documentation-v3-x-x/standard-configuration/email/"><?php _e('The Email: Send Completed PDF via Email', 'pdfextended'); ?></a></li>
                  <li><a href="http://gravityformspdfextended.com/documentation-v3-x-x/standard-configuration/the-download/"><?php _e('The Download: User Downloads PDF after Submitting Form (using a link or auto redirecting)', 'pdfextended'); ?></a></li>
                  <li><a href="http://gravityformspdfextended.com/documentation-v3-x-x/standard-configuration/the-email-advanced/"><?php _e('The Email Advanced: Manually Review User Submission before Emailing PDF', 'pdfextended'); ?></a></li>
                  <li><a href="http://gravityformspdfextended.com/documentation-v3-x-x/standard-configuration/the-payment/"><?php _e('The Payment: Send PDF after Capturing Payment using Paypal Standard', 'pdfextended'); ?></a></li>
                </ol>

                <p><?php echo sprintf(__('If you know little about PHP we recommend starting with %sPart 1:The Basics%s and then watching the tutorial you’re interested in. It will give you the foundational skills you need to configure the software.', 'pdfextended'), '<i>', '</i>'); ?></p>

                <h3><?php _e('Custom Templates', 'pdfextended'); ?></h3>
                <p><?php echo sprintf(__('Creating a custom template gives you ultimate control of the look and feel of your documents using only HTML and CSS. %sWe recommend you review our online documentation%s to create and customise your template files.', 'pdfextended'), '<a href="http://gravityformspdfextended.com/documentation-v3-x-x/templates/">', '</a>'); ?></p>

                <p><strong><?php _e('Note', 'pdfextended'); ?>: <?php _e("During some plugin updates we will update the default and example template files. If you plan to customise them you should make a copy.", 'pdfextended'); ?></strong></p>

                <h3><?php _e('Reinitialise or install a new font?', 'pdfextended'); ?></h3>
                <p><?php echo sprintf(__('Did you switch themes and something went wrong syncing the template folder? Or want to %suse a custom font%s in your template? Try reinitialise the software, or just initialise the fonts.', 'pdfextended'), '<a href="http://gravityformspdfextended.com/documentation-v3-x-x/language-support/">', '</a>'); ?> 
      



      <?php else: ?>

                   <p><?php _e('To complete the installation, Gravity Forms PDF Extended needs to be initialised.', 'pdfextended'); ?>

                   <p><?php _e('Initialisation does a number of important things, including:', 'pdfextended'); ?></p>
                   
                   <ol>
                        <li><strong><?php _e('Unzips the mPDF package', 'pdfextended'); ?>: </strong>The software used to convert HTML/CSS to PDFs is very large. To keep the plugin size small we ship it zipped up.</li>
                        <?php if(get_option('gf_pdf_extended_installed') != 'installed'): ?>
                        <li><strong><?php _e('Install the template files', 'pdfextended'); ?></strong>: <?php _e("We create a folder called PDF_EXTENDED_TEMPLATE in your active theme directory and move over all the templates and configuration files. This folder is where you'll look to configure the software and create your PDF templates.", 'pdfextended'); ?></li>                        
                        <?php endif; ?>                  
                        <li><strong><?php _e('Install fonts', 'pdfextended'); ?>: </strong><?php _e('You can use custom fonts in your PDFs. During initialisation we install any fonts found in the PDF_EXTENDED_TEMPLATES/fonts/ folder', 'pdfextended'); ?></li>                
                   </ol>		  	
                  
                   <p><strong><?php _e('Note', 'pdfextended'); ?>: <?php _e("During some plugin updates we will update the default and example template files. If you plan to customise them you should make a copy.", 'pdfextended'); ?></strong></p>

                   <p><strong><?php _e('Having trouble initialising?', 'pdfextended'); ?></strong> <a href="#"><?php _e('Follow these instructions to manually initialise the plugin', 'pdfextended'); ?></a>.</p>
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
     
