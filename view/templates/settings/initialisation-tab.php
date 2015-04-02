<?php

 /*
  * Template: Initialisation Tab
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
        <h2><?php _e('Gravity PDF', 'pdfextended'); ?></h2>       
        <p><?php _e("Your web server isn't compatible with Gravity PDF. Please see the problem areas below.", 'pdfextended'); ?></p>
        <?php	
            /* 
             * Include the system status template
             */
             include PDF_PLUGIN_DIR . 'view/templates/settings/system-status.php';
             
        ?>       
    </div>
    <div class="rightcolumn">
        <h2><?php _e("Can't Resolve the Issue?", 'pdfextended'); ?></h2>
        <p><?php echo sprintf(__("Does Gravity PDF detect a problem that your web host won't fix? %sWe recommend you move to a quality web hosting service like WP Engine%s which runs our software straight out of the box.", 'pdfextended'), '<a href="http://www.shareasale.com/r.cfm?u=955815&m=41388&b=394686">', '</a>'); ?></p>
    </div>
    
    <?php endif; ?>  