<?php

 /*
  * Template: Changelog
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
  
  
	<h2><?php _e('Frequently Asked Questions', 'pdfextended'); ?></h2>
    <ul>
    	<li><a href="http://gravityformspdfextended.com/faq/can-exclude-field-showing-pdf/"><?php _e('Can I exclude a field from showing up in the PDF?', 'pdfextended'); ?></a></li>
    	<li><a href="http://gravityformspdfextended.com/faq/i-want-to-have-multiple-pdf-template-files-how-do-i-do-it/"><?php _e('I want to have multiple PDF template files generated on one form. How do I do it?', 'pdfextended'); ?></a></li>        
    	<li><a href="http://gravityformspdfextended.com/faq/i-want-users-to-be-able-to-download-the-pdf-from-the-server/"><?php _e('I want users to be able to download the PDF from the server.', 'pdfextended'); ?></a></li>                
    	<li><a href="http://gravityformspdfextended.com/faq/how-do-i-change-the-pdf-size-or-create-a-landscape-pdf/"><?php _e('How do I change the PDF size or create a landscape PDF?', 'pdfextended'); ?></a></li>                        
    	<li><a href="http://gravityformspdfextended.com/faq/im-creating-a-custom-template-how-do-i-know-the-names-of-my-fields-in-the-form_data-array/"><?php _e('I am created a custom template. How do I know the names of my fields in the $form_data array?', 'pdfextended'); ?></a></li>                                
    	<li><a href="http://gravityformspdfextended.com/faq/how-large-a-pdf-are-you-able-to-createprocess/"><?php _e('How large a PDF are you able to create/process?', 'pdfextended'); ?></a></li>                                        
    </ul>
    
    <p><?php printf( __('Got a question that isn\'t answered above? %1$sHead to our support forum%2$s and let us know.', 'pdfextended'), '<a href="http://gravityformspdfextended.com/support/gravity-forms-pdf-extended/">', '</a>' ); ?></p>