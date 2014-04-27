<?php

 /*
  * Template: Initialisation Tab
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
  
	<?php  if($gfpdfe_data->allow_initilisation === true): ?>
    <div class="leftcolumn">                        
    <?php
        /*
         * Include the initialisation template
         */
         include PDF_PLUGIN_DIR . 'view/templates/settings/initialisation.php';                         
     ?>    
                                                 
    </div>
    
    <div class="rightcolumn">       
        <?php	
            /* 
             * Include the system status template
             */
             include PDF_PLUGIN_DIR . 'view/templates/settings/system-status.php';                     
        ?>
    </div>
    
    <?php else: ?>
    <div class="leftcolumn">     
        <h2><?php _e('Gravity Forms PDF Extended', 'pdfextended'); ?></h2>       
        <p><?php _e("Your web server isn't compatible with Gravity Forms PDF Extended. Please see the problem areas below.", 'pdfextended'); ?></p>
        <?php	
            /* 
             * Include the system status template
             */
             include PDF_PLUGIN_DIR . 'view/templates/settings/system-status.php';
             
        ?>
    </div>  
    
    <?php endif; ?>  