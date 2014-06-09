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