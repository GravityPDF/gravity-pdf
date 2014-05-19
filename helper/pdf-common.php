<?php

class PDF_Common
{
	public static function setup_ids()
	{
		global $form_id, $lead_id, $lead_ids;
		
		$form_id 		=  ($form_id) ? $form_id : absint( rgget("fid") );
		$lead_ids 		=  ($lead_id) ? array($lead_id) : explode(',', rgget("lid"));
		
		/**
		 * If form ID and lead ID hasn't been set stop the PDF from attempting to generate
		 */
		if(empty($form_id) || empty($lead_ids))
		{
			return;
		}				
	}		
	
	/*
	 * Sniff the $_SERVER array for the real user IP address
	 * Sometimes users are behind proxies, or different servers will set different keys
	 * so we will look at the most common keys
	 */
	public static function getRealIpAddr()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
		{
		  $ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
		{
		  $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else
		{
		  $ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}
	
	/*
	 * We will use the output buffer to get the HTML template
	 */
	public static function get_html_template($filename) 
	{
	  global $form_id, $lead_id, $lead_ids;

	  ob_start();
	  require($filename);	
	  
	  $page = ob_get_contents();
	  ob_end_clean();	    
	  
	  return $page;
	}	
	
	/**
	 * Get the name of the PDF based on the Form and the submission
	 */
	public static function get_pdf_filename($form_id, $lead_id)
	{
		return "form-$form_id-entry-$lead_id.pdf";
	}
	
	/*
	* Check if mPDF folder exists.
	* If so, unzip and delete
	* Helps reduce the package file size
	*/		
	public static function unpack_mPDF()
	{
		$file = PDF_PLUGIN_DIR .'mPDF.zip';
		$path = pathinfo(realpath($file), PATHINFO_DIRNAME);
		
		if(file_exists($file))
		{
			/* unzip folder and delete */
			$zip = new ZipArchive;
			$res = $zip->open($file);
			
			if ($res === TRUE) {
  				$zip->extractTo($path);
			    $zip->close();	
				unlink($file);
			}
		}
	}	
	
	/*
	 * We need to validate the PDF name
	 * Check the size limit, if the file name's syntax is correct 
	 * and strip any characters that aren't classed as valid file name characters.
	 */
	public static function validate_pdf_name($name, $form_id = false, $lead_id = false)
	{
		$pdf_name = $name;							
		
		if($form_id > 0)
		{
			$pdf_name = self::do_mergetags($pdf_name, $form_id, $lead_id);	
		}
		
		/*
		 * Limit the size of the filename to 100 characters
		 */
		 if(strlen($pdf_name) > 150)
		 {
			$pdf_name = substr($pdf_name, 0, 150); 
		 }
		 
		/*
		 * Remove extension from the end of the filename so we can replace all '.' 
		 * Will add back before we are finished
		 */		
		if(substr($pdf_name, -4) == '.pdf')
		{
			$pdf_name = substr($pdf_name, 0, -4);	
		}			 
		
		/*
		 * Remove any invalid (mostly Windows) characters from filename
		 */
		 $pdf_name = str_replace('/', '-', $pdf_name);
		 $pdf_name = str_replace('\\', '-', $pdf_name);		
		 $pdf_name = str_replace('"', '-', $pdf_name);				 
		 $pdf_name = str_replace('*', '-', $pdf_name);				 
		 $pdf_name = str_replace('?', '-', $pdf_name);				 		 
		 $pdf_name = str_replace('|', '-', $pdf_name);				 		 		 
		 $pdf_name = str_replace(':', '-', $pdf_name);				 		 		 		 
		 $pdf_name = str_replace('<', '-', $pdf_name);				 		 		 		 
		 $pdf_name = str_replace('>', '-', $pdf_name);				 		 		 		 		 		 
		 $pdf_name = str_replace('.', '_', $pdf_name);				 		 		 		 		 		 		 
		
		 $pdf_name = $pdf_name . '.pdf';
		
		return $pdf_name;
	}
	
	/*
	 * Replace all the merge tag fields in the string
	 * We wll remove the {all_fields} mergetag is it is not needed
	 */
	public static function do_mergetags($string, $form_id, $lead_id)
	{		
		$form = RGFormsModel::get_form_meta($form_id);
		$lead = RGFormsModel::get_lead($lead_id);
		
		/* strip {all_fields} merge tag from $string */
		$string = str_replace('{all_fields}', '', $string);		
		
		return trim(GFCommon::replace_variables($string, $form, $lead, false, false, false));		
	}
	
	/*
	 * Allow users to view the $form_data array, if it exists
	 */
	public static function view_data($form_data)
	{
		if(isset($_GET['data']) && $_GET['data'] === '1' && GFCommon::current_user_can_any("gravityforms_view_entries"))
		{
			print '<pre>'; 
			print_r($form_data);
			print '</pre>';
			exit;
		}
	}
	
	public static function display_compatibility_error()
	{
		 $message = sprintf(__("Gravity Forms " . GF_PDF_EXTENDED_SUPPORTED_VERSION . " is required to use this plugin. Activate it now or %spurchase it today!%s", 'pdfextended'), "<a href='https://www.e-junkie.com/ecom/gb.php?cl=54585&c=ib&aff=235154'>", "</a>"); 
		 PDF_Common::display_plugin_message($message, true);			
	}
	
	public static function display_wp_compatibility_error()
	{
		 $message = __("Wordpress " . GF_PDF_EXTENDED_WP_SUPPORTED_VERSION . " or higher is required to use this plugin.", 'pdfextended'); 
		 PDF_Common::display_plugin_message($message, true);			
	}	
	
	public static function display_documentation_details()
	{
		 $message = sprintf(__("Please review the %sGravity Forms PDF Extended documentation%s for comprehensive installation instructions. %sUpgraded from v2.x.x? Review our migration guide%s.%s", 'pdfextended'), "<a href='http://gravityformspdfextended.com/documentation-v3-x-x/installation-and-configuration/'>", "</a>", '<a style="color: red;" href="http://gravityformspdfextended.com/documentation-v3-x-x/v3-0-0-migration-guide/">', '</a>', '</span>'); 
		 PDF_Common::display_plugin_message($message);						
	}	
	
	public static function display_pdf_compatibility_error()
	{
		 $message = __("PHP " . GF_PDF_EXTENDED_PHP_SUPPORTED_VERSION . " or higher is required to use this plugin.", 'pdfextended'); 
		 PDF_Common::display_plugin_message($message, true);			
	}
	
	public static function display_plugin_message($message, $is_error = false){

        $style = $is_error ? 'style="background-color: #ffebe8;"' : "";

        echo '</tr><tr class="plugin-update-tr"><td colspan="5" class="plugin-update"><div class="update-message" ' . $style . '>' . $message . '</div></td>';
    }
	
	/* 
	 * New to 3.0.2 we will use WP_Filesystem API to manipulate files instead of using in-built PHP functions	
	 * $post Array the post data to include in the request_filesystem_credntials API	 
	 */
	public static function initialise_WP_filesystem_API($post, $nonce)
	{

		$url = wp_nonce_url(PDF_SETTINGS_URL, $nonce);	
		
		if (false === ($creds = request_filesystem_credentials($url, '', false, false, $post) ) ) {
			/* 
			 * If we get here, then we don't have correct permissions and we need to get the FTP details.
			 * request_filesystem_credentials will handle all that
			 */			 
			return false; // stop the normal page from displaying
		}		

		/*
		 * Check if the credentials are no good and display an error
		 */
		if ( ! WP_Filesystem($creds) ) {
			request_filesystem_credentials($url, '', true, false, $post_credentials);
			return false;
		}		
		
		return true;
				
	}
	
}

