<?php

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

class settingsView
{	
	private $model;
	
	function __construct($model, $data = array())
	{		
			global $gfpdfe_data;
			
			$this->model = $model;
            
            /*
             * Show any messages the plugin might have called
             * Because we had to run inside the settings page to correctly display the FTP credential form admin_notices was already called.
             * To get around this we can recall it here.
             */
             do_action('gfpdfe_notices');
             
            /* 
             * Show the settings page deployment form 
             */
			 
			 /*
			  * Rekey $this->model->navigation to ensure the order is correct
			  */
			  ksort($this->model->navigation);
            
              $tab = PDF_Common::get('tab');
              if(strlen($tab) == 0)
              {
                $tab = 'initialisation';
              }

            ?>
            
            <h2 class="nav-tab-wrapper">  
                <?php                 
                foreach($this->model->navigation as $id => $page): ?>
                    <?php $active = ($page['id'] == $tab) ? 'nav-tab-active' : '' ?>
                    <a href="<?php echo PDF_SETTINGS_URL; ?>&amp;tab=<?php echo $page['id']; ?>" class="nav-tab <?php echo $active; ?>"><?php _e($page['name'], 'pdfextended'); ?></a>      
                <?php                     
                endforeach; ?>
            </h2> 
                    
                    
            <div id="pdfextended-settings">    

            	<?php 
 

                    foreach($this->model->navigation as $item)
                    {
                        if($item['id'] == $tab)
                        {
                            $page = $item;
                            break;
                        }
                    }

                    if(isset($page))
                    {
                        
                        ?>
                            <div id="<?php echo $page['id']; ?>" class="nav-tab-contents">
                                <?php include $page['template']; ?>
                            </div>
                        <?php
                        do_action('pdf-extended-settings-' . $page['id']);
                    }

				    do_action('pdf-extended-settings');	                                        
				?>                           
                 
            </div>
        
        <?php
		
	}
}