<?php

/**
 * Plugin: Gravity PDF
 * File: mode/settings.php
 *
 * The model that does all the processing and interacts with our controller and view
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

   

class GFPDF_Settings_Model extends GFPDF_Settings
{
    public $navigation = array();

    /*
     * Construct
     */
     public function __construct()
     {
         /*
         * Let's check if the web server is going to be compatible
         */
         $this->check_compatibility();
     }

     /**
      * Set up our settings navigation
      * Note: ID 40/50 are taken by "Extensions" and "License" tabs
      */
     public function support_navigation()
     {

            /**
             * Store the setting navigation
             * The array key is the settings order
             * @var array
             */
            $this->navigation = array(
                5 => array(
                    'name'     => __('General', 'pdfextended'),
                    'id'       => 'general',
                    'template' => PDF_PLUGIN_DIR.'view/templates/settings/general.php',
                ),

                100 => array(
                    'name'     => __('Tools', 'pdfextended'),
                    'id'       => 'tools',
                    'template' => PDF_PLUGIN_DIR.'view/templates/settings/tools.php' ,
                ),

                120 => array(
                    'name' => __('Support', 'pdfextended'),
                    'id' => 'support',
                    'template' => PDF_PLUGIN_DIR.'view/templates/settings/support.php' ,
                ),

                150 => array(
                    'name' => __('DEP_Initialisation', 'pdfextended'),
                    'id' => 'initialisation',
                    'template' => PDF_PLUGIN_DIR.'view/templates/settings/initialisation-tab.php',
                ),

            );

            /**
             * Allow additional navigation to be added to the settings page
             * @since 3.8
             */
            $this->navigation = apply_filters('pdf_extended_settings_navigation', $this->navigation);                        
     }



	/**
	 * Text Callback
	 *
	 * Renders text fields.
	 *
	 * @since 1.0
	 * @param array $args Arguments passed by the setting
	 * @global $edd_options Array of all the EDD Options
	 * @return void
	 */
	public static function edd_text_callback( $args ) {
		global $edd_options;

		if ( isset( $edd_options[ $args['id'] ] ) )
			$value = $edd_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}   

    public function check_compatibility()
    {
        $status = new GFPDF_System_Status();

        $status->fresh_install();
        $status->is_initialised();

        $status->check_wp_compatibility();
        $status->check_gf_compatibility();
        $status->check_php_compatibility();

        $status->mb_string_installed();
        $status->gd_installed();
        $status->check_available_ram();

        $status->check_write_permissions();
    }

    /*
     * Shows the GF PDF Extended settings page
     */
    public function gfpdf_settings_page()
    {
        global $gfpdfe_data;
        /*
         * Run the page's configuration/routing options
         */
        if ($this->run_setting_routing() === true) {
            return;
        }

        $this->support_navigation();

        include PDF_PLUGIN_DIR.'view/settings.php';

		/*
		* Pass any additional variables to the view templates
		*/
        $status = new GFPDF_System_Status();

        $gfpdfe_data->active_plugins           = $status->get_active_plugins();
        $gfpdfe_data->system_status            = $status->get_system_status_html(false);
        $gfpdfe_data->configuration_file       = $status->get_configuration_file();

        new settingsView($this);
    }



    /*
     * Handle the AJAX Support Request
     */
    public static function gfpdf_support_request()
    {
        /*
         * Check the Nonce to make sure it is a valid request
         */
         $nonce = $_POST['nonce'];

        if (! wp_verify_nonce($nonce, 'pdf_settings_nonce')) {
            print json_encode(array('error' => array('msg' => __('There was a problem with your submission. Please reload the page and try again', 'pdfextended')) ));
            exit;
        }

         /*
          * AJAX Automatically adding slashes so remove them
          */
         $email = stripslashes($_POST['email']);
        $countType = stripslashes($_POST['supportType']);
        $comments = stripslashes($_POST['comments']);

        $error = array();
         /*
          * Check that email, support type and comments are valid
          */
          if (! is_email($email)) {
              $error['email'] = __('Please enter a valid email address', 'pdfextended');
          }

        $valid_support_types = array(__('Problem', 'pdfextended'), __('Question', 'pdfextended'), __('Suggestion', 'pdfextended'));

        if (in_array($countType, $valid_support_types) === false) {
            $error['supportType'] = __('Please select a valid support type.', 'pdfextended');
        }

        if (strlen($comments) == 0) {
            $error['comments'] = __('Please enter information about your support query so we can aid you more easily.', 'pdfextended');
        }

        if (sizeof($error) > 0) {
            $error['msg'] = __('There is a problem with your support request. Please correct the marked issues above.', 'pdfextended');
            print json_encode(array('error' => $error));
            exit;
        }

          /*
           * Do our POST request to the Gravity PDF API
           */
           self::send_support_request($email, $countType, $comments);

        print json_encode(array('msg' => __('Thank you for your support request. We\'ll respond to your request in the next 24-48 hours.', 'pdfextended')));
        exit;
    }

    public static function send_support_request($email, $countType, $comments)
    {
        global $gfpdfe_data;

         /*
          * Build our support request array
          */
        $status = new GFPDF_System_Status();

		$active_plugins = $status->get_active_plugins();
		$system_status  = $status->get_system_status_html(true);
		$configuration  = $status->get_configuration_file();
		$website        = site_url('/');
		$comments       = stripslashes($comments);
		
		$configuration  = htmlspecialchars_decode($configuration, ENT_QUOTES);
		
		$subject        = $countType.': Automated Ticket for "'.get_bloginfo('name').'"';
		$to             = 'support@gravitypdf.com';
		$from           = $email;
		$message        = "Support Type: $countType\r\n\r\nWebsite: $website\r\n\r\n----------------\r\n\r\n$comments\r\n\r\n----------------\r\n\r\n$system_status\r\n\r\n\r\nActive Plugins\r\n\r\n$active_plugins\r\n\r\n\r\n**Configuration**\r\n\r\n$configuration";

        $headers[] = 'From: '.$email;

        if (wp_mail($to, $subject, $message, $headers) === false) {
            /*
                 * Error
                 */
                 print json_encode(array('error' => array('msg' => $api->response_message )));
            exit;
        } else {
            print json_encode(array('msg' => __('Support request received. We will responed in 24 to 48 hours.', 'pdfextended')));
            exit;
        }

         /*
          * Create our
          */
         exit;
    }
}
