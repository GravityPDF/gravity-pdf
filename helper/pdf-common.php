<?php

/**
 * Plugin: Gravity PDF
 * File: pdf-common.php
 * 
 * This file holds a number of common functions used throughout the plugin
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

class PDF_Common
{

	/**
	 * Depreciated function 
	 */
	public static function setup_ids() { }	

	/**
	 * Takes over for setup_ids() but is now called much earlier in the process 
	 * @return Boolean whether settings the ids was successful or not
	 */
	public static function get_ids()
	{
		global $form_id, $lead_id, $lead_ids;
		
		$form_id 		=  ($form_id) ? $form_id : absint( rgget("fid") );
		$lead_ids 		=  ($lead_id) ? array($lead_id) : explode(',', rgget("lid"));
		
		/**
		 * If form ID and lead ID hasn't been set stop the PDF from attempting to generate
		 */
		if(empty($form_id) || empty($lead_ids))
		{
			return false;
		}				
		return true;
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
		 * Limit the size of the filename to 120 characters
		 */
		 if(strlen($pdf_name) > 120)
		 {
			$pdf_name = substr($pdf_name, 0, 120); 
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
		 $pdf_name = self::remove_invalid_characters($pdf_name);			 		 		 		 		 		 		 		
		 $pdf_name = $pdf_name . '.pdf';
		
		return $pdf_name;
	}

	public static function remove_invalid_characters($name)
	{
		/*
		 * Remove any invalid (mostly Windows) characters from filename
		 */
		 $name = str_replace('/', '-', $name);
		 $name = str_replace('\\', '-', $name);		
		 $name = str_replace('"', '-', $name);				 
		 $name = str_replace('*', '-', $name);				 
		 $name = str_replace('?', '-', $name);				 		 
		 $name = str_replace('|', '-', $name);				 		 		 
		 $name = str_replace(':', '-', $name);				 		 		 		 
		 $name = str_replace('<', '-', $name);				 		 		 		 
		 $name = str_replace('>', '-', $name);				 		 		 		 		 		 
		 $name = str_replace('.', '_', $name);	

		 return $name;		
	}
	
	/*
	 * Replace all the merge tag fields in the string
	 * We wll remove the {all_fields} mergetag is it is not needed
	 */
	public static function do_mergetags($string, $form_id, $lead_id)
	{		
		/*
		 * Unconvert { and } symbols from HTML entities 
		 */
		$string = str_replace('&#123;', '{', $string);		
		$string = str_replace('&#125;', '}', $string);

		/* strip {all_fields} merge tag from $string */
		$string = str_replace('{all_fields}', '', $string);		

		/*
		 * Get form and lead data
		 */
		$form = RGFormsModel::get_form_meta($form_id);
		$lead = RGFormsModel::get_lead($lead_id);	

		$results = trim(GFCommon::replace_variables($string, $form, $lead, false, false, false));		

		/*
		 * Return results 
		 */	
		return $results;

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

	/**
	 * Gets the site name for use as a directory name
	 * @return String Returns the current 'safe' directory site name
	 */
	public static function get_site_name()
	{
		$name = (is_ssl()) ? str_replace('https://', '', site_url()) : str_replace('http://', '', site_url());
		return self::remove_invalid_characters($name);
	}

	/**
	 * Modified version of get_upload_dir() which just focuses on the base directory
	 * no matter if single or multisite installation 
	 * We also only needed the basedir and baseurl so stripped out all the extras
	 * @return Array Base dir and url for the upload directory
	 */
	public static function get_upload_dir()
	{
	        $siteurl = get_option( 'siteurl' );
	        $upload_path = trim( get_option( 'upload_path' ) );
	
	        if ( empty( $upload_path ) || 'wp-content/uploads' == $upload_path ) {
	                $dir = WP_CONTENT_DIR . '/uploads';
	        } elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
	                // $dir is absolute, $upload_path is (maybe) relative to ABSPATH
	                $dir = path_join( ABSPATH, $upload_path );
	        } else {
	                $dir = $upload_path;
	        }
	
	        if ( !$url = get_option( 'upload_url_path' ) ) {
	                if ( empty($upload_path) || ( 'wp-content/uploads' == $upload_path ) || ( $upload_path == $dir ) )
	                        $url = WP_CONTENT_URL . '/uploads';
	                else
	                        $url = trailingslashit( $siteurl ) . $upload_path;
	        }
	
	        /*
	         * Honor the value of UPLOADS. This happens as long as ms-files rewriting is disabled.
	         * We also sometimes obey UPLOADS when rewriting is enabled -- see the next block.
	         */
	        if ( defined( 'UPLOADS' ) && ! ( is_multisite() && get_site_option( 'ms_files_rewriting' ) ) ) {
	                $dir = ABSPATH . UPLOADS;
	                $url = trailingslashit( $siteurl ) . UPLOADS;
	        }		

	        $basedir = $dir;
	        $baseurl = $url;	

        return array(
	        'basedir' => $basedir,
	        'baseurl' => $baseurl,
 		);	                
	}

	/**
	 * Add backwards compatibility to users running Gravity Forms 1.8.3 or below 
	 * which don't have this function built in.
	 * Once support is dropped for Gravity Forms 1.8.x this function can be removed.
	 * Added in v3.7.1
	 * @param  Array  $currency A currency type
	 * @return String           Whether currency should be displayed as 'decimal_dot' or 'decimal_comma'
	 */
	public static function is_currency_decimal_dot($currency = null) {    
        if($currency == null){
            $code = GFCommon::get_currency();
            if(empty($code))
                $code = "USD";

            $currency = RGCurrency::get_currency($code);
        }

        return rgar($currency, "decimal_separator") == ".";    	
	}
}

