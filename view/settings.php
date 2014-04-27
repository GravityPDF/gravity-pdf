<?php

class settingsView
{	
	private $model;
	
	function __construct($model)
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