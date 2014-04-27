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
  
  
 <h2><?php _e('Support'); ?></h2>
 
    <div class="leftcolumn">                        
    <?php
        /*
         * Include the support form template
         */
         include PDF_PLUGIN_DIR . 'view/templates/settings/support-form.php';                         
     ?>    
                                                 
    </div>
    
    <div class="rightcolumn">
        <?php	
            /* 
             * Include the FAQs and Support Forum
             */
             include PDF_PLUGIN_DIR . 'view/templates/settings/support-sidebar.php';                     
        ?>
    </div> 
 
 
          
          
          
