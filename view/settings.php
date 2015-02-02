<?php

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
            
            ?>
            
            <h2 class="nav-tab-wrapper">  
                <?php 
                $active = 'nav-tab-active';
                foreach($this->model->navigation as $id => $page): ?>
                    <a href="<?php echo $page['id']; ?>" class="nav-tab <?php echo $active; ?>"><?php _e($page['name'], 'pdf_extended'); ?></a>      
                <?php 
                    $active = '';
                endforeach; ?>
            </h2> 
                    
                    
            <div id="pdfextended-settings">    
            
            	<?php 
					foreach($this->model->navigation as $id => $page)
					{
						?>
                        	<div id="<?php echo substr($page['id'], 1); ?>" class="nav-tab-contents">
                            	<?php include $page['template']; ?>
                            </div>
                        <?php
					}                                        
				?>                           
                 
                 <?php
                    do_action('pdf-extended-settings');
                 ?> 
            </div>
        
        <?php
		
	}
}