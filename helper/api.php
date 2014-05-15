<?php

define('PDF_DEBUG', false);

class gfpdfe_API
{
	private $api_url = 'http://gravityformspdfextended.com/api/';
	private $api_version = '1.0';
	
	private $username;
	private $secret;
	
	public $response_message;
	
	public function __construct()
	{
		
		/*
		 * Set the username and secret if available, otherwise request them from the API
		 */	
		 $this->username 	= get_option('gfpdfe_api_username');
		 $this->secret 		= get_option('gfpdfe_api_secret');
		 		 		 
		 if( $this->username === false || strlen($this->username) == 0 || $this->secret === false || strlen($this->secret) == 0 )
		 {
			 $this->get_api_access_details();
		 }		 
		
	}
	
	private function get_api_access_details()
	{
		/*
		 * Register User is the correct API path to get new credentials
		 */
		$url = $this->api_url . 'registerUser/';
		
		$request_args = array(
			'body' => array(),
		);	
			
		/*
		 * Hash our request for the API
		 */
		$request_args = $this->add_headers($request_args); 	
		
		$response = wp_remote_get($url, $request_args);

		/*
		 * Check if there is an error
		 */
		if ( is_wp_error($response) )		 
		{
			print json_encode(array('error' => array('msg' => $response->get_error_message())));	
			exit;
		}

		/*
		 * Check the response codes for errors
		 */
		 if($this->check_response_code($response) === true)
		 {
			 /*
			  * Store the username and secret key in the DB
			  */
			  $r_data = json_decode($response['body']);
			 
			  update_option('gfpdfe_api_username', $r_data->username);
			  update_option('gfpdfe_api_secret', $r_data->secretkey);		  
			  
			  $this->username = $r_data->username;
			  $this->secret = $r_data->secretkey;
		 }
		 else
		 {
			/*
			 * Error
			 */ 
			 print json_encode(array('error' => $this->response_message));
			 exit;
		 }
	
	}
	
	private function add_headers($request)
	{

		/* change the timeout from 5 seconds to 20 */
		$request['timeout'] = 20;

		$request['headers'] = array(
			'API' 			=> (string) $this->api_version,
			'API_STAMP' 	=> (string) time(),
			'API_URL' 		=> (string) site_url(),
		);			
		
		if(strlen($this->username) > 0)
		{
			$request['headers']['API_USER'] = $this->username;	
			return $this->create_hash($request, $this->secret);
		}
		
		ksort($request);
		return $this->create_hash($request);										
	}
	
	private function check_response_code($response)
	{
	
		switch($response['response']['code'])
		{
			case '200':
				/*
				 * Everything okay
				 */
				 return true; 
			break;
			
			case '400':
				/*
				 * Bad Request
				 */
				 $this->response_message = __('Bad Request.', 'pdfextended');
				 return false;
			break;
			
			case '401':
				/*
				 * Unauthorized Access
				 */				
				 $this->response_message = __('Unauthorized Access.', 'pdfextended');
				 
				 /*
				  * Remove current API access keys
				  */				  				  
					delete_option('gfpdfe_api_username');
		 			delete_option('gfpdfe_api_secret');		
					
					$this->username = false;
					$this->secret = false;		  
				  
				 /*
				  * Automatically regenerate key
				  */
				 $this->get_api_access_details();
				 return false;				 
			break;
			
			case '405':
				/*
				 * Method not allowed
				 */
				 $this->response_message = __('Method not allowed.', 'pdfextended');
				 return false;				 
			break;	
			
			case '500':
				/*
				 * Internal Server Error
				 */
				 $this->response_message = __('Internal API Error.', 'pdfextended');
				 return false;				 
			break;			
			
			case '503':
				/*
				 * Service Unavailable 
				 */
				 $this->response_message = __('API Unavailable.', 'pdfextended');
				 return false;				 
			break;					
		}
	}
	
	/*
	 * Create our hash function which will ensure the request hasn't been tampered en-route
	 * We'll make calls over HTTPS as well, but this is an added security measure. 
	 */
	private function create_hash($request, $secret_key = '')
	{
		/*
		 * Sign our hash with our secret key if we have one
		 */
			if(PDF_DEBUG == true)
			{
				file_put_contents( ABSPATH . 'pdf-api.log',  date('d/m/Y h:m:s') . ' Hash'  ."\n", FILE_APPEND);				
				file_put_contents( ABSPATH . 'pdf-api.log',  serialize($request) . $secret_key. "\n", FILE_APPEND);		
			}
		
		/*
		 * Remove any items not needed in the hash
		 */			 
		$hash_request = array();
		$hash_request['body'] = $request['body'];
		$hash_request['headers'] = $request['headers'];

		 
		$hashed = hash ('sha256', serialize($hash_request) . $secret_key );		
		$request['headers']['hash'] = $hashed;
		return $request;			
	}
	
	public function support_request($body)
	{
		static $retry = false;
		$url = $this->api_url . 'supportRequest/';
		
		$request_args = array(
			'body' => $body			
		);	
		
		/*
		 * Hash our request for the API
		 */
		$request_args = $this->add_headers($request_args); 			
		
		$response = wp_remote_post($url, $request_args);

		/*
		 * Check if there is an error
		 */
		if ( is_wp_error($response) )		 
		{
			print json_encode(array('error' => array('msg' => $response->get_error_message())));	
			exit;
		}

		/*
		 * Check the response codes for errors
		 */
		 if($this->check_response_code($response) === true)
		 {
			$response_array = json_decode($response['body']);
		 	print json_encode(array('msg' => __($response_array->msg, 'pdfextended'))); 
		 }
		 else
		 {
			 if($this->response_message == __('Unauthorized Access.', 'pdfextended') && $retry == false)
			 {				
				/*
				 * Access keys failed. They were regenerated so let's try again
				 */ 
				 $retry = true;
				 $this->support_request($body);		
				 return;		 
			 }
			 else
			 {
				return false; 
			 }

		 }		
	}	
}