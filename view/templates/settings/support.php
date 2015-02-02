<?php

 /*
  * Template: Changelog
  * Module: Settings Page
  */
 
/*
    This file is part of Gravity PDF.

    Gravity PDF is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Gravity PDF is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Gravity PDF. If not, see <http://www.gnu.org/licenses/>.
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
 
 
          
          
          
