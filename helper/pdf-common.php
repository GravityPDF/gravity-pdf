<?php

/**
 * Plugin: Gravity Forms PDF Extended
 * File: pdf-common.php
 * 
 * This file holds a number of common functions used throughout the plugin
 */

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

		/*
		 * Unconvert { and } symbols from HTML entities 
		 */
		$string = str_replace('&#123;', '{', $string);		
		$string = str_replace('&#125;', '}', $string);

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
			request_filesystem_credentials($url, '', true, false, $post);
			return false;
		}		
		
		return true;
				
	}

	/*
	 * Check if we are on the PDF settings page 
	 */
	public static function is_settings()
	{
		if(isset($_GET['page']) && isset($_GET['subview']) && $_GET['page'] === 'gf_settings' && strtolower($_GET['subview']) === 'pdf')
		{
			return true;
		}
		return false;
	}

	public static function post($name)
	{
		if(isset($_POST[$name]))	
			return $_POST[$name];

		return '';
	}
	

	public static function get($name)
	{
		if(isset($_GET[$name]))	
			return $_GET[$name];

		return '';
	}
}

