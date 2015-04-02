<?php

 /*
  * Template: Initialisation
  * Module: Settings Page
  */
  
/*
    This file is part of Gravity PDF.

    Gravity PDF Copyright (C) 2015 Blue Liquid Designs

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

  /*
   * Don't run if the correct class isn't present
   */
  if(!class_exists('GFPDF_Settings_Model'))
  {
	 exit;  
  }   
  
  ?>
  
 <h2><?php ($gfpdfe_data->is_initialised) ? _e('Welcome to Gravity PDF', 'pdfextended') : _e('Initialise Plugin', 'pdfextended'); ?></h2>
          
			<?php if($gfpdfe_data->is_initialised): ?>

                <p><?php _e('The plugin has successfully installed and is ready to start automating your documents.', 'pdfextended'); ?></p>

                <h3><?php _e("What's next?", 'pdfextended'); ?></h3>
                <p><?php _e('The next step is to correctly configured the plugin so that you can generate PDF documents.', 'pdfextended'); ?> 
                <?php _e("To help you get started, we've put together a five-part video series with the most common plugin configurations.", 'pdfextended'); ?></p>
                <ol>
                  <li><a href="https://developer.gravitypdf.com/documentation/basic-configuration-video-part-one/"><?php _e('The Basics: Only Download PDF through the Admin Area', 'pdfextended'); ?></a></li>
                  <li><a href="https://developer.gravitypdf.com/documentation/how-to-automated-pdf-email-part-two/"><?php _e('The Email: Send Completed PDF via Email', 'pdfextended'); ?></a></li>
                  <li><a href="https://developer.gravitypdf.com/documentation/how-to-allow-user-pdf-downloads-video-part-three/"><?php _e('The Download: User Downloads PDF after Submitting Form (using a link or auto redirecting)', 'pdfextended'); ?></a></li>
                  <li><a href="https://developer.gravitypdf.com/documentation/how-to-manual-review-before-pdf-email-part-four/"><?php _e('The Email Advanced: Manually Review User Submission before Emailing PDF', 'pdfextended'); ?></a></li>
                  <li><a href="https://developer.gravitypdf.com/documentation/send-pdf-after-accepting-payment-video-part-five/"><?php _e('The Payment: Send PDF after Capturing Payment using Paypal Standard', 'pdfextended'); ?></a></li>
                </ol>

                <p><?php echo sprintf(__('If you know little about PHP we recommend starting with %sPart 1:The Basics%s and then watching the tutorial you’re interested in. It will give you the foundational skills you need to configure the software.', 'pdfextended'), '<i>', '</i>'); ?></p>

                <h3><?php _e('Custom Templates', 'pdfextended'); ?></h3>
                <p><?php echo sprintf(__('Creating a custom template gives you ultimate control of the look and feel of your documents using only HTML and CSS. %sWe recommend you review our online documentation%s to create and customise your template files.', 'pdfextended'), '<a href="http://developer.gravitypdf.com/documentation/custom-templates-introduction/">', '</a>'); ?></p>                    



      <?php else: ?>

                   <p><?php _e('To complete the installation, Gravity PDF needs to be initialised.', 'pdfextended'); ?>

                   <p><strong><?php _e('What does initialisation do?', 'pdfextended'); ?></strong></p>
                   
                   <ol>                       
                        <li><strong><?php _e('Install the template files', 'pdfextended'); ?></strong>: <?php _e("We create a folder called PDF_EXTENDED_TEMPLATE in your active theme directory and move over all the templates and configuration files. This folder is where you'll look to configure the software and create your PDF templates.", 'pdfextended'); ?></li>                                                              
                   </ol>		  	
                
                   
            <?php endif; ?>
			

      <form method="post">
                <?php wp_nonce_field('gfpdf_deploy_nonce_action','gfpdf_deploy_nonce'); ?>
                <input type="hidden" name="gfpdf_deploy" value="1">

                <?php if($gfpdfe_data->is_initialised): ?>
                <h3><?php _e('Have a problem with the software?', 'pdfextended'); ?></h3>
                <p><?php echo __('Did you switch themes and something went wrong syncing the template folder? Try reinitialise the software.', 'pdfextended'); ?></p>                 
                <?php endif; ?>
                
                <input type="submit" value="<?php ($gfpdfe_data->is_initialised) ? _e('Reinitialise Plugin', 'pdfextended') : _e('Initialise Plugin', 'pdfextended'); ?>" class="button" id="upgrade" name="upgrade">
                <?php if($gfpdfe_data->is_initialised): ?>
                  <label><input type="checkbox" value="1" name="overwrite"> <?php echo __('Reinstall Default and Example Templates', 'pdfextended'); ?> <?php gform_tooltip('pdf_overwrite'); ?></label>
                  <br><br>
                <?php endif; ?>   


                <?php if($gfpdfe_data->is_initialised): ?>
                   <h3><?php _e('Install a new font?', 'pdfextended'); ?></h3>
                   <p><?php echo sprintf(__('Do you want to use a custom font in your template? %sSee our documentation on how to correctly install and use a custom font%s before initialising below.', 'pdfextended'), '<a href="https://developer.gravitypdf.com/documentation/language-support/">', '</a>'); ?></p>
                  <input type="submit" value="<?php _e('Initialise Fonts', 'pdfextended') ; ?>" class="button" id="font-initialise" name="font-initialise">                                           
                <?php endif; ?>                


                
                                
          </form>   
     
