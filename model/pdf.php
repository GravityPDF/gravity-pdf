<?php

/**
 * Plugin: Gravity PDF
 * File: model/pdf.php
 * 
 * The model that does all the processing and interacts with our controller and view (if necisarry) 
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

class GFPDF_Core_Model
{
	/*
	 * This function is used to check if the Gravity Forms shortcode is loaded on the front end of the website
	 * and that the form has currently been submitted.
	 * We use this to determine if Gravity Forms is currently loaded on the website to prevent resource overload
	 */
   public static function valid_gravity_forms()
   {
	    $form_id = isset($_POST["gform_submit"]) ? $_POST["gform_submit"] : 0;

        if($form_id)
		{
            $form_info = RGFormsModel::get_form($form_id);       
            $is_valid_form = $form_info && $form_info->is_active;

            if($is_valid_form)
			{	   
				return true;
			}
		}
		return false;
   }
   
   /*
    * Function to check if the major compatibility functionality is met
	* This includes Wordpress and Gravity Forms
	*/
   public static function check_major_compatibility()
   {
	   	global $gfpdfe_data;		

		if($gfpdfe_data->wp_is_compatible === false)
		{
		 	add_action('after_plugin_row_' . GF_PDF_EXTENDED_PLUGIN_BASENAME, array('GFPDF_Notices', 'display_wp_compatibility_error')); 
		 	return false;  
		}	

		if($gfpdfe_data->php_version_compatible === false)   		
		{
		 	add_action('after_plugin_row_' . GF_PDF_EXTENDED_PLUGIN_BASENAME, array('GFPDF_Notices', 'display_pdf_compatibility_error')); 
		 	return false;  			
		}		
   
		if($gfpdfe_data->gf_is_compatible === false)
		{
		 	add_action('after_plugin_row_' . GF_PDF_EXTENDED_PLUGIN_BASENAME, array('GFPDF_Notices', 'display_compatibility_error')); 
		 	return false;  
		}					
		
		return true;
   }	

	
	 /*
	  * Check if the system is fully installed and return the correct values
	  */
	 public static function is_fully_installed()
	 {
		 global $gfpdfe_data;			 

		if(self::check_major_compatibility() === false)
		{
			return false;
		}

		if( ($gfpdfe_data->fresh_install === true) || (!is_dir($gfpdfe_data->template_site_location)) )
		{						
			return false;
		}
		 
		 if($gfpdfe_data->allow_initilisation === false)
		 {		 			
			return false; 
		 }

		 return true;
	 }	
	 
	/*
	 * Display a view and download button on the entry detailed page
	 */ 
	public static function detail_pdf_link($form_id, $lead) {  
		global $gfpdf;
		
		/*
		 * Check if a user can view the PDF, otherwise exit early.
		 */
		if(!GFCommon::current_user_can_any("gravityforms_view_entries"))
		{
			return;	
		}
	
		$lead_id = $lead['id'];

		/*
		 * Get the template name
		 * Class: PDFGenerator
		 * File: pdf-configuration-indexer.php
		 */
		$template = $gfpdf->get_template($form_id);				
		
		/*
		 * Before setting up PDF options we will check if a configuration is found
		 * If not, we will set up defaults defined in configuration.php
		 */		
		$index = self::check_configuration($form_id, $template);				
		
		/*
		 * Now all the correct configuration and indexes are in place lets get our configuration nodes
		 */
		$templates = $gfpdf->get_form_configuration($form_id);

		/* exit early if templates not found */
		if($templates === false || sizeof($templates) === 0)
		{
			return;
		}		


		?>
			<strong>PDFs</strong><br />

        	<?php foreach($templates as $id => $template):
			$name = $gfpdf->get_pdf_name($id, $form_id, $lead['id']);
			$aid  = $gfpdf->get_aid($id, $form_id);
			 ?>	
            <div class="detailed_pdf">						
				<span><?php 
					echo $name; 									
					$url = home_url() .'/?gf_pdf=1&aid='. $aid .'&fid=' . $form_id . '&lid=' . $lead_id . '&template=' . $template['template']; 								
				?></span> 
                <a href="<?php echo $url; ?>" target="_blank" class="button"><?php _e('View', 'pdfextended'); ?></a> 
 				<a href="<?php echo $url.'&download=1'; ?>" target="_blank" class="button"><?php _e('Download', 'pdfextended'); ?></a>
 			</div>
                  
            <?php endforeach; ?>

            
        <?php	
	}
	
	/*
	 * Display the view PDF(s) link on the entry list page 
	 */
	public static function pdf_link($form_id, $field_id, $value, $lead) {
		global $gfpdf;

		/*
		 * Check if a user can view the PDF, otherwise exit early.
		 */		
		if(!GFCommon::current_user_can_any("gravityforms_view_entries"))
		{
			return;	
		}			 
		
		$lead_id = $lead['id'];	

		/*
		 * Get the template name
		 * Class: PDFGenerator
		 * File: pdf-configuration-indexer.php
		 */
		$template = $gfpdf->get_template($form_id);				
		
		/*
		 * Before setting up PDF options we will check if a configuration is found
		 * If not, we will set up defaults defined in configuration.php
		 */		
		$index = self::check_configuration($form_id, $template);				
		
		/*
		 * Now all the correct configuration and indexes are in place lets get our configuration nodes
		 */
		$templates = $gfpdf->get_form_configuration($form_id);

		/* exit early if templates not found */
		if($templates === false || sizeof($templates) === 0)
		{
			return;
		}

		/*
		 * Show if multiple PDFs assigned to single form
		 */
		if(sizeof($templates) > 1)
		{
			?>
                <span class="gf_form_toolbar_settings gf_form_action_has_submenu">
                   | <a href="#" title="View PDF configured for this form" onclick="return false" class=""><?php _e('View PDFs', 'pdfextended'); ?></a>
                    
                    <div class="gf_submenu">
                        <ul>
                        	<?php foreach($templates as $id => $t): 
							/*
							 * Replace MergeTags in filename
							 */
								$name = $gfpdf->get_pdf_name($id, $form_id, $lead['id']);
								$aid  = $gfpdf->get_aid($id, $form_id);
							?>							
                            <li class="">
                            	<?php
									$url = home_url() . '/?gf_pdf=1&aid='. $aid .'&fid=' . $form_id .'&lid=' . $lead_id . '&template=' . $t['template']; 
								?>
                            	<a href="<?php echo $url; ?>" target="_blank"><?php echo $name; ?></a> 
                            </li>        
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </span>
                
                <?php 

		}
		else
		{			
			
			/*
			 * Get the first and only item in the array
			 */
			$template = array_shift($templates);
			$url = home_url() . '/?gf_pdf=1&fid=' . $form_id .'&lid=' . $lead_id . '&template=' . $template['template']; 
			
			?>
			| <a href="<?php echo $url; ?>" target="_blank"><?php _e('View PDF', 'pdfextended'); ?></a> 
			<?php
		}
	}
	
	/*
	 * Handle incoming routes
	 * Look for $_GET['gf_pdf'] variable, authenticate user and generate/display PDF
	 * TODO - slated for v3.9.0: move to a proper permalink structure 
	 * /pdf/id/
	 * /pdf/id/template/
	 * /pdf/id/template/action/
	 * where action is 'html', 'data', or 'print'
	 */ 
	public static function process_exterior_pages() {	 	 
	  global $wpdb, $gfpdf, $form_id, $lead_ids;
	  	
	  /*
	   * If $_GET variable isn't set then stop function
	   */ 	 	  
	  if(rgempty( 'gf_pdf', $_GET))
	  {
		return;
	  }
		

		PDF_Common::get_ids();
		$ip = GFFormsModel::get_ip(); 
		
		/*
		 * Get the template name
		 * Class: PDFGenerator
		 * File: pdf-configuration-indexer.php
		 */
		$template = $gfpdf->get_template($form_id);				
		
		/*
		 * Before setting up PDF options we will check if a configuration is found
		 * If not, we will set up defaults defined in configuration.php
		 */		
		$index = self::check_configuration($form_id, $template);	

		/* 
		 * Authenticate all lead Ids
		 */ 
		$lead_ids = self::validate_entry_ids($lead_ids, $form_id, $ip, $index);


		if(sizeof($lead_ids) == 0)	
		{
			if(!is_user_logged_in())
			{
				/* give the user a chance to authenticate */
				auth_redirect();
			}
			else
			{
				die(__('Access Denied', 'pdfextended'));
			}
		}

		/*
		 * Give user with correct privilages the option to change the PDF template via the URL
		 */
		if(is_user_logged_in() && GFCommon::current_user_can_any('gravityforms_view_entries'))
		{
		  /*
		   * Because this user is logged in with the correct access 
		   * we will allow a template to be shown by setting the template variable
		   */	 
		   if( ($template != $_GET['template']) && (substr($_GET['template'], -4) == '.php') )
		   {			
				$template = $_GET['template'];
		   }		
		}	
		 

		$pdf_arguments = self::generate_pdf_parameters($index, $form_id, $lead_ids[0], $template);		
		
		/*
		 * Add output to arguments 
		 */
		$output = 'view';
		if(isset($_GET['download']))
		{
			$output = 'download';	
		}	
		
		$pdf_arguments['output'] = $output;					

		/*
		 * While the security above will prevent the PDF being read by non-authorised users, 
		 * a user can disable that security with the 'access' => 'all' method (THIS IS NOT RECOMMENDED)
		 * To prevent those PDFs showing up in search engines we will tell them not to index the documents 
		 */
		if (!headers_sent()) 
		{
			header("X-Robots-Tag: noindex, nofollow", true);
		}

		$gfpdf->render->PDF_Generator($form_id, $lead_ids[0], $pdf_arguments);
		
	  exit();
	}

	public static function validate_entry_ids($lead_ids, $form_id, $ip, $index)
	{
		global $gfpdf;

		if(empty($gfpdf->configuration[$index]['access']) || $gfpdf->configuration[$index]['access'] !== 'all') /* unpublicised feature to give FULL access to ALL PDFs in this configuration - RECOMMENDATION: DO NOT USE */
		{
			foreach($lead_ids as $key => $lead_id)
			{
				if(self::authenticate_user($form_id, $lead_id, $ip) === false)
				{
					unset($lead_ids[$key]);
				}
				/* resequence so there are no loop issues later */
			}	$lead_ids = array_values($lead_ids);	
		}	

		return $lead_ids;		
	}

	private static function authenticate_user($form_id, $lead_id, $ip)
	{
		global $wpdb;

		/*
		 * Run if user is not logged in
		 */ 
		 if(!is_user_logged_in())
		 {
			return self::check_logged_out_user($form_id, $lead_id, $ip);
		 }
		 else
		 {
			  /*
			   * Ensure logged in users have the correct privilages 
			   */
			   		   
			  if(!GFCommon::current_user_can_any("gravityforms_view_entries"))
			  {
				  /*
				   * User doesn't have the correct access privilages 
				   * Let's check if they are assigned to the form
				   */
					$user_logged_entries = $wpdb->get_var( $wpdb->prepare("SELECT count(*) FROM `".$wpdb->prefix."rg_lead` WHERE form_id = %d AND status = 'active' AND id = %d AND created_by = %d", array($form_id, $lead_id, get_current_user_id()) ) );					   
					
					/*
					 * Failed again.
					 * One last check against the IP 
					 * If it matches the record then we will show the PDF
					 */
					if($user_logged_entries == 0)
					{				   
						return self::check_logged_out_user($form_id, $lead_id, $ip);
					}				   
			  }		   
		 }			
	}

	private static function check_logged_out_user($form_id, $lead_id, $ip)
	{
		global $wpdb;	

		/* 
		 * Check the lead is in the database and the IP address matches (little security booster) 
		 */
		$form_entries = $wpdb->get_var( $wpdb->prepare("SELECT count(*) FROM `".$wpdb->prefix."rg_lead` WHERE form_id = %d AND status = 'active' AND id = %d AND ip = %s", array($form_id, $lead_id, $ip) ) );	

		if($form_entries == 0)
		{
			return false;		
		}		
		return true;
	}

	/**
	 * Hooked to the gform_after_submission action hook, this function will save the PDF
	 * if the 'notification' option isn't present
	 * @param  array $entry The user entry array
	 * @param  array $form  The form data array	 
	 */
	public static function gfpdfe_save_pdf($entry, $form)
	{
		global $gfpdf, $form_id, $lead_id;

		$form_id = $entry['form_id'];
		$lead_id = $entry['id'];

		/*
		 * Before setting up PDF options we will check if a configuration is found
		 * If not, we will set up defaults defined in configuration.php
		 */
		self::check_configuration($form_id);		

		/*
		 * Check if form is in configuration
		 */			

		 if(!$config = $gfpdf->get_config($form_id))
		 {
			 return false;
		 }	

		 /* set up the correct lead IDs */
		 PDF_Common::get_ids();

		/* 
		 * To have our configuration indexes so loop through the PDF template configuration
		 * and generate and attach PDF files.
		 */		
		 foreach($config as $index)
		 {

		 		/*
		 		 * Check if the save option is selected		 		 
		 		 */
			 	if(isset($gfpdf->configuration[$index]['save']) && $gfpdf->configuration[$index]['save'] === true )
			 	{
			 		$template = (isset($gfpdf->configuration[$index]['template'])) ? $gfpdf->configuration[$index]['template'] : '';

					/* only generate the PDF is attaching to notification */
					$pdf_arguments = self::generate_pdf_parameters($index, $form_id, $lead_id, $template);

					/* generate and save default PDF */
					$gfpdf->render->PDF_Generator($form_id, $lead_id, $pdf_arguments);				 		
				}
		 }			 

	}
	
	/*
	 * Filter to return the PDFs that should be attached to the notification (if configured)
	 */
	public static function gfpdfe_create_and_attach_pdf($notification, $form, $entry)
	{								
		$notification = self::do_notification($notification, $form, $entry);		
    	return $notification;
	}
	
	/*
	 * Handles the Gravity Forms notification logic 
	 */
	public static function do_notification($notification, $form, $entry)
	{
		/*
		 * Allow the template/function access to these variables
		 */
		global $gfpdf, $form_id, $lead_id;		
				
		$notification_name = (isset($notification['name'])) ? $notification['name'] : '';			
		
		/*
		 * Set data used to determine if PDF needs to be created and attached to notification
		 * Don't change anything here.
		 */		
		$form_title        = $form['title'];
		$form_id           = $entry['form_id']; 
		$lead_id           = apply_filters('gfpdfe_lead_id', $entry['id'], $form, $entry, $gfpdf); /* allow premium plugins to override the lead ID */


		/*
		 * Before setting up PDF options we will check if a configuration is found
		 * If not, we will set up defaults defined in configuration.php
		 */
		 self::check_configuration($form_id);		

		/*
		 * Check if form is in configuration
		 */			

		 if(!$config = $gfpdf->get_config($form_id))
		 {
			 return $notification;
		 }	

		 /* set up the correct lead IDs */
		 PDF_Common::get_ids();			 

		/* 
		 * To have our configuration indexes so loop through the PDF template configuration
		 * and generate and attach PDF files.
		 */		
		 foreach($config as $index)
		 {
				$template = (isset($gfpdf->configuration[$index]['template'])) ? $gfpdf->configuration[$index]['template'] : '';					
	

				/* Get notifications user wants PDF attached to and check if the correct notifications hook is running */				
				$notifications = self::get_form_notifications($form, $index);				
														
				/* 
				 * Premium plugin filter
				 * Allows manual override of the notification 
				 * Allows the multi-report plugin to automate PDFs based on weekly/fortnightly/monthly basis
				 * Only allow boolean to be returned
				 */
				 $notification_override = (bool) apply_filters('gfpdfe_notification_override', false, $notification_name, $notifications, $form, $entry, $gfpdf);
			
				if (self::check_notification($notification_name, $notifications) || $notification_override === true) 
				{							
					/* only generate the PDF is attaching to notification */
					$pdf_arguments = self::generate_pdf_parameters($index, $form_id, $lead_id, $template);
	
					/* generate and save default PDF */
					$filename = $gfpdf->render->PDF_Generator($form_id, $lead_id, $pdf_arguments);									
												
					$notification['attachments'][] = $filename;						
				}

		 }	
		 return $notification;	
	}
	
	/*
	 * Check if name in notification_name String/Array matches value in $notifcations array	 
	 */
	public static function check_notification($notification_name, $notifications)
	{		
		if(is_array($notification_name))
		{
			foreach($notification_name as $name)
			{
				if(in_array($name, $notifications))
				{
					return true;	
				}					
			}
		}
		else
		{
			if(in_array($notification_name, $notifications))
			{
				return true;	
			}
		}
		
		return false;
	}
	
	/*
	 * Get all the notifications assigned to a form so we can determine if a PDF should be attached
	 */
    public static function get_notifications_name($action, $form){
        if(rgempty("notifications", $form))
            return array();

        $notifications = array();
        foreach($form["notifications"] as $notification){
            if(rgar($notification, "event") == $action)
                $notifications[] = $notification['name'];
        }

        return $notifications;
    }	
	
	/*
	 * Compare the assigned configuration notifications to the avaliable form notifications
	 */
	public static function get_form_notifications($form, $index)
	{
		global $gfpdf;

		/*
		 * Check if notification field even exists
		 */
		 if(!isset($gfpdf->configuration[$index]['notifications']))
		 {
			return array(); 
		 }
		
		/*
		 * Get all form_submission notifications and use to check if any are configured to attach a PDF
		 */  			 
		$notifications = self::get_notifications_name('form_submission', $form);			

		$new_notifications = array();				

		/*
		 * If notifications is true the user wants to attach the PDF to all notifications
		 */ 

		if($gfpdf->configuration[$index]['notifications'] === true)
		{					
			$new_notifications = $notifications;
		}
		/*
		 * Only a single notification is selected
		 */ 		
		else if(!is_array($gfpdf->configuration[$index]['notifications']))
		{
			/*
			 * Ensure that notification is valid
			 */
			 if(in_array($gfpdf->configuration[$index]['notifications'], $notifications))
			 {
					$new_notifications = array($gfpdf->configuration[$index]['notifications']); 
			 }
		}
		else
		{
			foreach($gfpdf->configuration[$index]['notifications'] as $name)
			{
				if(in_array($name, $notifications))
				{
					$new_notifications[] = $name;	
				}
			}
		}
		
		return $new_notifications;
	}
	
	/*
	 * Generate PDF parameters to pass to the PDF renderer
	 * $index Integer The configuration index number
	 */
	public static function generate_pdf_parameters($index, $form_id, $lead_id, $template = '')
	{
		global $gfpdf;

		$config = $gfpdf->configuration[$index];		
		
		$pdf_name    = (isset($config['filename']) && strlen($config['filename']) > 0) ? $gfpdf->get_pdf_name($index, $form_id, $lead_id) : PDF_Common::get_pdf_filename($form_id, $lead_id);	
		$template    = (isset($template) && strlen($template) > 0) ? $template : $gfpdf->get_template($index);	 
		
		$pdf_size    = (isset($config['pdf_size']) && (is_array($config['pdf_size']) || strlen($config['pdf_size']) > 0)) ? $config['pdf_size'] : PDFGenerator::$default['pdf_size'];
		$orientation = (isset($config['orientation']) && strlen($config['orientation']) > 0) ? $config['orientation'] : PDFGenerator::$default['orientation'];
		$security    = (isset($config['security']) && $config['security']) ? $config['security'] : PDFGenerator::$default['security'];			
		$premium     = (isset($config['premium']) && $config['premium'] === true) ? true: false;

		/* added in v3.4.0 */
		$dpi    	= (isset($config['dpi']) && (int) $config['dpi'] > 0) ? (int) $config['dpi'] : false;
		
		/* added in v3.4.0 */
		$pdfa1b 	= (isset($config['pdfa1b']) && $config['pdfa1b'] === true) ? true : false;		
		
		/* added in v3.4.0 */
		$pdfx1a 	= (isset($config['pdfx1a']) && $config['pdfx1a'] === true) ? true : false;		

		/*
		 * Validate privileges 
		 * If blank and security is true then set privileges to all
		 */ 
		$privileges      = (isset($config['pdf_privileges'])) ? $gfpdf->validate_privileges($config['pdf_privileges']) : $gfpdf->validate_privileges('');	
		
		$pdf_password    = (isset($config['pdf_password'])) ? PDF_Common::do_mergetags($config['pdf_password'], $form_id, $lead_id) : '';
		$master_password = (isset($config['pdf_master_password'])) ? PDF_Common::do_mergetags($config['pdf_master_password'], $form_id, $lead_id) : '';
		$rtl             = (isset($config['rtl'])) ? $config['rtl'] : false;		


		$form = RGFormsModel::get_form_meta($form_id);
		$lead = RGFormsModel::get_lead($lead_id);
		
		/*
		 * Run the options through filters
		 */
		$pdf_name        = apply_filters('gfpdfe_pdf_name', 		$pdf_name, 			$form, $lead);
		$template        = apply_filters('gfpdfe_template', 		$template, 			$form, $lead);
		$orientation     = apply_filters('gfpdf_orientation', 		$orientation, 		$form, $lead);
		$security        = apply_filters('gfpdf_security', 			$security, 			$form, $lead);
		$privileges      = apply_filters('gfpdf_privilages', 		$privileges, 		$form, $lead);
		$pdf_password    = apply_filters('gfpdf_password', 			$pdf_password, 		$form, $lead);
		$master_password = apply_filters('gfpdf_master_password', 	$master_password, 	$form, $lead);
		$rtl             = apply_filters('gfpdf_rtl', 				$rtl, 				$form, $lead);

		$pdf_arguments = array(
			'pdfname'             => $pdf_name,
			'template'            => $template,				
			'pdf_size'            => $pdf_size, /* set to one of the following, or array - in millimeters */
			'orientation'         => $orientation, /* landscape or portrait */
			
			'security'            => $security, /* true or false. if true the security settings below will be applied. Default false. */
			'pdf_password'        => $pdf_password, /* set a password to view the PDF */
			'pdf_privileges'      => $privileges, /* assign user privliages to the PDF */
			'pdf_master_password' => $master_password, /* set a master password to the PDF can't be modified without it */	
			'rtl'                 => $rtl,
			'premium'             => $premium,
			'dpi'                 => $dpi,	

			'pdfa1b'			  => $pdfa1b,			
			'pdfx1a'			  => $pdfx1a, 			
		);	
	
		return $pdf_arguments;	
	}	

	/*
	 * Checks if a configuration index is found
	 * If not, we will set up defaults defined in configuration.php if they exist
	 */
	public static function check_configuration($form_id, $template = '')
	{

		global $gf_pdf_default_configuration, $gfpdf;

		/*
		 * Check if configuration index already defined		 
		 */
		if(empty($gfpdf->index[$form_id]))
		{

			/*
			 * Check if a default configuration is defined
			 */			
			if(is_array($gf_pdf_default_configuration) && sizeof($gf_pdf_default_configuration) > 0 && GFPDF_SET_DEFAULT_TEMPLATE === true)
			{

				/*
				 * Add form_id to the defualt configuration				 
				 */
				 $default_configuration = array_merge($gf_pdf_default_configuration, array('form_id' => $form_id));
				 
				/*
				 * There is no configuration index and there is a default index so add the defaults to this form's configuration
				 */
				 $gfpdf->configuration[] = $default_configuration;
				 
				 /* get the id of the newly added configuration */
				 end($gfpdf->configuration);
				 $index = key($gfpdf->configuration);
				 
				 /* now add to the index */
				 $gfpdf->assign_index($form_id, $index);	

				 return $index;			  
				 
			}
		}
		else
		{
			/* if there are multiple indexes for a form we will look for the one with the matching template */
			if(sizeof($gfpdf->index[$form_id]) > 1 && strlen($template) > 0 )
			{

				/*
				 * Check if $_GET['aid'] present which will give us the index when multi templates assigned
				 */
				 if(isset($_GET['aid']) && (int) $_GET['aid'] > 0)
				 {
					$aid = (int) $_GET['aid'] - 1;
					if(isset($gfpdf->index[$form_id][$aid]))
					{
						return $gfpdf->index[$form_id][$aid];
					}					
				 }				

				/*
				 * If aid not present we'll match against the template
				 * This is usually the case when using a user-generated link
				 */
				$index = false;
				foreach($gfpdf->index[$form_id] as $i)
				{
					if(isset($gfpdf->configuration[$i]['template']) && $gfpdf->configuration[$i]['template'] == $template)
					{
						/* matched by template */
						return $i;	
					}
				}				
			}
			
			/* there aren't multiples so just return first node */
			return $gfpdf->index[$form_id][0];	
		}
		return false;	
	}		 	   
}