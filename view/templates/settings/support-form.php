<?php

 /*
  * Template: Changelog
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
  
  <div id="support-request">
  		<?php
		/* set timezone to Sydney Australia and then swap back again */
		$temp_timezone = date_default_timezone_get();
		date_default_timezone_set('Australia/Sydney');
		?>
  		<div class="pdf-notice"><p>
			<?php _e('Gravity PDF\'s support hours are from 9:00am-5:00pm Monday to Friday, Sydney Australia time.', 'pdfextended'); ?> <br />
            <strong>
				<?php printf(
							__('The current time in Sydney Australia is %s.', 'pdfextended'), 
							date('g:ia l, F d')
							); 
				?>
             </strong>
         </p></div>
        
        <?php
		date_default_timezone_set($temp_timezone);
		?>
  
      <form method="post">
      <?php wp_nonce_field('pdf_settings_nonce','pdf_settings_nonce_field'); ?>
        <p>
            <label class="inline-label" for="support-type">Support Type</label>
            <select id="support-type" name="support-type">
                <option>Problem</option>
                <option>Question</option>     
                <option>Suggestion</option>                 
                          
            </select>
        </p>    
      
        <p>
            <label class="inline-label" for="email-address">Email Address</label>
            <input type="email" name="email-address" id="email-address" value="<?php echo get_option('admin_email'); ?>" />
             <span class="details tabbed"><?php _e('Enter the email address you want us to contact you on.', 'pdfextended'); ?><br />
             <strong><?php _e('Note: To ensure the best support possible, please use the above email to respond to all support communications.', 'pdfextended'); ?></strong></span>
        </p>
        
        <p>
            <label class="inline-label" for="website-address">Website</label>
            <input type="text" name="website-address" id="website-address" value="<?php echo site_url(); ?>" disabled />
        </p>  
        
        <p>
            <label for="active-plugins">Active Plugins</label>
            <textarea name="active-plugins" id="active-plugins" disabled><?php print $gfpdfe_data->active_plugins; ?></textarea>
        </p>      
        
        <p>
            <label for="system-status">System Status</label>
            <textarea name="system-status" id="system-status" disabled>
			<?php
                /*
                 * Include the initialisation template
                 */
				echo $gfpdfe_data->system_status;
             ?>              
            </textarea>
        </p> 
        
        <p>
            <label for="current-configuration">Configuration</label>
            <textarea name="current-configuration" id="current-configuration" disabled>
            <?php 
				echo $gfpdfe_data->configuration_file;
			?>
            </textarea>
        </p>       
        
        <p>
            <label for="comments">Comments</label>
            <textarea name="comments" id="comments" placeholder="<?php _e('Enter as much detail about the problem as you can.', 'pdfextended'); ?>"></textarea>    
            <span class="details"><?php _e('Please enter as much detail about the problem as you can.', 'pdfextended'); ?><br />
            <strong><?php _e('Note: Our support representatives can only communicate in English.', 'pdfextended'); ?></strong></span>
        </p>  
        
        <p><input class="button-primary gfbutton" type="submit" id="support-request-button" name="submit" value="<?php _e('Request Support', 'pdfextended'); ?>" /> </p>
                  
      </form>
  </div>
  