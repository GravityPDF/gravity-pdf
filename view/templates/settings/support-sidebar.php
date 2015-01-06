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
    <p><?php echo sprintf(__('See our %scomprehensive FAQ section%s for more information about Gravity PDF.', 'pdfextended'), '<a href="https://gravitypdf.com/#faqs">', '</a>'); ?></p>
    
    <p><?php echo sprintf( __('Got a question that isn\'t answered above? %sHead to our support forum%s and let us know.', 'pdfextended'), '<a href="https://support.gravitypdf.com/">', '</a>' ); ?></p>