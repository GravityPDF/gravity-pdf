<?php

namespace GFPDF\Helper;

use GFPDF\Model\Model_Form_Settings;
use GFPDF\Stat\Stat_Functions;

use GFCommon;

/**
 * Our Gravity PDF Options API
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
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

/* Exit if accessed directly */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Class to set up the settings api callbacks
 *
 * Pulled straight from the Easy Digital Download register-settings.php file (props to Pippin and team)
 * and modified to suit our requirements
 * @since 4.0
 */
class Helper_Options implements Helper_Int_Filters {

	/**
	 * Holds the current global user settings
	 * @var Array
	 * @since 4.0
	 */
	private $settings = array();

	/**
	 * Holds the Gravity Form PDF Settings
	 * @var Array
	 * @since 4.0
	 */
	private $form_settings = array();

	/**
	 * Initialise the options API
	 * @return void
	 * @since 4.0
	 */
	public function init() {
		$this->set_plugin_settings();
		$this->add_filters();
	}

	public function add_filters() {
        /* register our core santize functions */
        add_filter( 'gfpdf_settings_sanitize', array($this, 'sanitize_required_field'), 10, 4 );
        add_filter( 'gfpdf_settings_sanitize', array($this, 'sanitize_all_fields'), 10, 4 );

        add_filter( 'gfpdf_settings_sanitize_text', array($this, 'sanitize_trim_field') );
        add_filter( 'gfpdf_settings_sanitize_textarea', array($this, 'sanitize_trim_field') );
        add_filter( 'gfpdf_settings_sanitize_number', array($this, 'sanitize_number_field') );
	}
	
    /**
     * Get the plugin's settings from the database
     * @since 4.0
     * @return  void
     */
    public function set_plugin_settings() {
        if ( false == get_option( 'gfpdf_settings' ) ) {
            add_option( 'gfpdf_settings' );
        }

        /* assign our settings */
        $this->settings = $this->get_settings();
    }

	/**
	 * Get Settings
	 *
	 * Retrieves all plugin settings
	 *
	 * @since 4.0
	 * @return array GFPDF settings
	 */
	public function get_settings() {
		$tempSettings = get_transient('gfpdf_settings_user_data');
		delete_transient('gfpdf_settings_user_data');

		if($tempSettings !== false) {
			$settings = $tempSettings;
		} else {
			$settings = (is_array(get_option( 'gfpdf_settings' ))) ? get_option( 'gfpdf_settings' ) : array();
		}
		return apply_filters( 'gfpdf_get_settings', $settings );
	}

	/**
	 * Get form settings if appropriate items are set
	 * @return Array The stored form settings
	 * @since 4.0
	 */
	public function get_form_settings() {
		/* get GF settings */
		$form_id = (int) rgget('id');
		$pid     = (!empty(rgget('pid'))) ? rgget('pid') : rgpost('gform_pdf_id');

        /* return early if no ID set */
        if(!$form_id) {
            return array();
        }

		$model = new Model_Form_Settings();
		$settings = $model->get_settings($form_id);

		if(!is_wp_error($settings)) {
			/* get the selected form settings */
			return (isset($settings[$pid])) ? $settings[$pid] : array();
		}

		/* there was an error */
		return array();
	}

	/**
	 * Add all settings sections and fields
	 *
	 * @param  $fields Array Fields that should be registered
	 * @since 4.0
	 * @return void
	*/
	public function register_settings( $fields = array() ) {

		/* If no fields were passed into the function we'll use the defaults */
		if(sizeof($fields) == 0) {
			$fields = $this->get_registered_settings();
		}

		foreach( $fields as $tab => $settings ) {

			foreach ( $settings as $option ) {

				$name = isset( $option['name'] ) ? $option['name'] : '';

				add_settings_field(
					'gfpdf_settings[' . $option['id'] . ']',
					$name,
					method_exists(  $this, $option['type'] . '_callback' ) ? array($this, $option['type'] . '_callback') : array($this, 'missing_callback'),
					'gfpdf_settings_' . $tab,
					'gfpdf_settings_' . $tab,
					array(
						'section'            => $tab,
						'id'                 => isset( $option['id'] )         	    			? $option['id']      				: null,
						'desc'               => ! empty( $option['desc'] )      				? $option['desc']    				: '',
						'desc2'              => ! empty( $option['desc2'] )     				? $option['desc2']   				: '',
						'type'               => isset( $option['type'] )        				? $option['type']    				: null,
						'name'               => isset( $option['name'] )        				? $option['name']    				: null,
						'size'               => isset( $option['size'] )        				? $option['size']    				: null,
						'options'            => isset( $option['options'] )     				? $option['options'] 				: '',
						'std'                => isset( $option['std'] )         				? $option['std']     				: '',
						'min'                => isset( $option['min'] )         				? $option['min']     				: null,
						'max'                => isset( $option['max'] )         				? $option['max']     				: null,
						'step'               => isset( $option['step'] )        				? $option['step']    				: null,
						'chosen'             => isset( $option['chosen'] )      				? $option['chosen']  				: null,
						'class'              => isset( $option['class'] )       				? $option['class']  				: null,
						'inputClass'         => isset( $option['inputClass'] )  				? $option['inputClass']  			: null,
						'placeholder'        => isset( $option['placeholder'] ) 				? $option['placeholder'] 			: null,
						'tooltip'            => isset( $option['tooltip'] )     				? $option['tooltip'] 				: null,
						'multiple'           => isset( $option['multiple'] )    				? $option['multiple'] 				: null,
						'required'           => isset( $option['required'] )    				? $option['required'] 		 		: null,
						'uploaderTitle'      => isset( $option['uploaderTitle'] )    			? $option['uploaderTitle'] 			: null,
						'uploaderButtonText' => isset( $option['uploaderButtonText'] )    		? $option['uploaderButtonText'] 	: null,
					)
				);
			}

		}

		/* Creates our settings in the options table */
		register_setting( 'gfpdf_settings', 'gfpdf_settings', array($this, 'settings_sanitize') );
	}

	/**
	 * Retrieve the array of plugin settings
	 *
	 * @since 4.0
	 * @return array
	*/
	public function get_registered_settings() {

		/**
		 * 'Whitelisted' Gravity PDF settings, filters are provided for each settings
		 * section to allow extensions and other plugins to add their own settings
		 */
		$gfpdf_settings = array(
			/** General Settings */
			'general' => apply_filters( 'gfpdf_settings_general',
				array(
					'default_pdf_size' => array(
						'id'         => 'default_pdf_size',
						'name'       => __('Default Paper Size', 'gravitypdf'),
						'desc'       => __('Set the default paper size used when generating PDFs.', 'gravitypdf'),
						'type'       => 'select',
						'options'    => $this->get_paper_size(),
						'inputClass' => 'large',
						'chosen'     => true,
						'class'      => 'gfpdf_paper_size',
					),

					'default_custom_pdf_size' => array(
						'id'       => 'default_custom_pdf_size',
						'name'     => __('Custom Paper Size', 'gravitypdf'),
						'desc'     => __('Control the exact paper size. Can be set in millimeters or inches.', 'gravitypdf'),
						'type'     => 'paper_size',
						'size'     => 'small',
						'chosen'   => true,
						'required' => true,
						'class'    => 'gfpdf-hidden gfpdf_paper_size_other',
					),

					'default_template' => array(
						'id'         => 'default_template',
						'name'       => __('Default Template', 'gravitypdf'),
						'desc'       => __('Set the default PDF template to use for all forms.', 'gravitypdf'),
						'type'       => 'select',
						'options'    => $this->get_templates(),
						'inputClass' => 'large',
						'chosen'     => true,
					),

					'default_font_type' => array(
						'id'         => 'default_font_type',
						'name'       => __('Default Font Type', 'gravitypdf'),
						'desc'       => __('Set the default font type used in the PDFs.', 'gravitypdf'),
						'type'       => 'select',
						'options'    => $this->get_installed_fonts(),
						'inputClass' => 'large',
						'chosen'     => true,
					),

					'default_font_colour' => array(
						'id'      => 'default_font_colour',
						'name'    => __('Default Font Colour', 'gravitypdf'),
						'type'    => 'color',
						'std'     => '#000000',
						'desc'    => __('Set the default font colour used in the PDFs.', 'gravitypdf'),
					),

					'default_rtl' => array(
						'id'      => 'default_rtl',
						'name'    => __('Reverse Text (RTL)', 'gravitypdf'),
						'desc'    => __('Languages like Arabic and Hebrew are written right to left.', 'gravitypdf'),
						'type'    => 'radio',
						'options' => array(
							'Yes'     => __('Yes', 'gravitypdf'),
							'No'      => __('No', 'gravitypdf')
						),
						'std'     => __('No', 'gravitypdf'),
					),

					'default_action' => array(
						'id'      => 'default_action',
						'name'    => __('Entry View', 'gravitypdf'),
						'desc'    => sprintf(__('Select the default action used when accessing a PDF from the %sGravity Forms entries list%s page.', 'gravitypdf'), '<a href="'. admin_url('admin.php?page=gf_entries') . '">', '</a>'),
						'type'    => 'radio',
						'options' => array(
							'View'     => __('View', 'gravitypdf'),
							'Download' => __('Download', 'gravitypdf'),
						),
						'std'     => 'View',
					),
				)
			),

			'general_security' => apply_filters( 'gfpdf_settings_general_security',
				array(
					'admin_capabilities' => array(
						'id'          => 'admin_capabilities',
						'name'        => __('User Restriction', 'gravitypdf'),
						'desc'        => __('Restrict PDF access to logged in users with any of these capabilities. The Administrator Role always has full access.', 'gravitypdf'),
						'type'        => 'select',
						'options'     => $this->get_capabilities(),
						'std'         => 'gravityforms_view_entries',
						'inputClass'  => 'large',
						'chosen'      => true,
						'multiple'    => true,
						'required'    => true,
						'placeholder' => __('Select Capability', 'gravitypdf'),
						'tooltip'     => '<h6>' . __('User Restriction', 'gravitypdf') . '</h6>' . __("Only logged in users with these capabilities can view generated PDFs they don't have ownership of. Ownership refers to the user who completed the original Gravity Form entry.", 'gravitypdf'),
					),

					'limit_to_admin' => array(
						'id'      => 'limit_to_admin',
						'name'    => __('Restrict Owner', 'gravitypdf'),
						'desc'    => __("When enabled, the original entry owner will NOT be able to view their PDF.", 'gravitypdf'),
						'type'    => 'radio',
						'options' => array(
							'Yes'     => __('Yes', 'gravitypdf'),
							'No'      => __('No', 'gravitypdf')
						),
						'std'     => __('No', 'gravitypdf'),
						'tooltip' => '<h6>' . __('Restrict Owner', 'gravitypdf') . '</h6>' . __("Enable this option if you only want logged in users with the above capability to view PDFs.", 'gravitypdf'),
					),

					'logged_out_timeout' => array(
						'id'      => 'logged_out_timeout',
						'name'    => __('Logged Out Timeout', 'gravitypdf'),
						'desc'    => __('Limit how long a <em>logged out</em> users has direct access to the PDF after completing the form. Set to 0 to disable time limit (not recommended).', 'gravitypdf'),
						'desc2'   => __('minutes', 'gravitypdf'),
						'type'    => 'number',
						'size'    => 'small',
						'std'     => 20,
						'tooltip' => '<h6>' . __('Logged Out Timeout', 'gravitypdf') . '</h6>' . __("By default, logged out users can view PDFs when their IP matches the IP assigned to the Gravity Form entry. But because IP addresses can change frequently a time-based restriction also applies.", 'gravitypdf'),
					),
				)
			),

			
			/** Extension Settings */
			'extensions' 	=> apply_filters('gfpdf_settings_extensions',
				array()
			),
			'licenses' 		=> apply_filters('gfpdf_settings_licenses',
				array()
			),

			'tools' 		=> apply_filters('gfpdf_settings_tools',
				array(
					'setup_templates' => array(
						'id'      => 'setup_templates',
						'name'    => __('Setup Custom Templates', 'gravitypdf'),
						'desc'    => sprintf(__("Ready to get down and dirty with custom PDF templates? %sSee docs to get started%s.", 'gravitypdf'), '<a href="#">', '</a>'),
						'type'    => 'button',
						'std'     => __('Run Setup', 'gravitypdf'),
						'options' => 'copy',
						'tooltip' => '<h6>' . __('Setup Custom Templates', 'gravitypdf') . '</h6>' . __('TODO... Write Copy', 'gravitypdf'),
					),

					'manage_fonts' => array(
						'id'      => 'manage_fonts',
						'name'    => __('Fonts', 'gravitypdf'),
						'desc'    => __("Add, update or remove custom fonts.", 'gravitypdf'),
						'type'    => 'button',
						'std'     => __('Manage Fonts', 'gravitypdf'),
						'options' => 'install_fonts',
						'tooltip' => '<h6>' . __('Install Fonts', 'gravitypdf') . '</h6>' . sprintf(__("Custom fonts can be installed and used in your PDFs. Currently only %s.ttf%s and %s.otf%s font files are supported. Once installed, fonts can be used in your custom PDF templates with a CSS %sfont-family%s declaration.", 'gravitypdf'), '<code>', '</code>', '<code>', '</code>', '<code>', '</code>'),
					),
				)
			),

			/* Form (PDF) Settings */
			'form_settings' => apply_filters('gfpdf_form_settings',
				array(

					'name' => array(
						'id'       => 'name',
						'name'     => __('Name', 'gravitypdf'),
						'type'     => 'text',
						'required' => true,
					),

					'template' => array(
						'id'         => 'template',
						'name'       => __('Template', 'gravitypdf'),
						'desc'       =>  sprintf(__('Choose from the pre-installed templates or %sbuild your own%s.', 'gravitypdf'), '<a href="#">', '</a>'),
						'type'       => 'select',
						'options'    => $this->get_templates(),
						'inputClass' => 'large',
						'chosen'     => true,
						'tooltip'    => '<h6>' . __('Templates', 'gravitypdf') . '</h6>' . __('Set the template used to generate your PDF.', 'gravitypdf'),
					),

					'notification' => array(
						'id'                 => 'notification',
						'name'               => __('Notifications', 'gravitypdf'),
						'desc'               => __('Automatically attach PDF to the selected notifications.', 'gravitypdf'),
						'type'               => 'select',
						'options'            => array(),
						'inputClass'         => 'large',
						'chosen'             => true,
						'multiple'           => true,
						'placeholder'        => __('Choose a Notification', 'gravitypdf'),
					),

					'filename' => array(
						'id'         => 'filename',
						'name'       => __('Filename', 'gravitypdf'),
						'type'       => 'text',
						'desc'       => 'The name used when saving a PDF. Mergetags are allowed.',
						'tooltip'    => '<h6>' . __('Filename', 'gravitypdf') . '</h6>' . __('Set an appropriate filename for the generated PDF. You should exclude the .pdf extension from the name.', 'gravitypdf'),
						'inputClass' => 'merge-tag-support mt-hide_all_fields',
						'required'   => true,
					),

					'conditional' => array(
						'id'         => 'conditional',
						'name'       => __('Conditional Logic', 'gravitypdf'),
						'type'       => 'conditional_logic',
						'desc'       => __('Enable conditional logic', 'gravitypdf'),
						'class'      => 'conditional_logic',
						'inputClass' => 'conditional_logic_listener',
						'tooltip'    => '<h6>' . __('Conditional Logic', 'gravitypdf') . '</h6>' . __('Create rules to dynamically enable or disable PDFs. This includes attaching to notifications and viewing.', 'gravitypdf'),
					),

					'conditionalLogic' => array(
						'id'      => 'conditionalLogic',
						'type'    => 'hidden',
						'class'   => 'gfpdf-hidden',
					),
				
				)
			),

			/* Form (PDF) Settings Appearance */
			'form_settings_appearance' => apply_filters('gfpdf_form_settings_appearance',
				array(
					'pdf_size' => array(
						'id'      => 'pdf_size',
						'name'    => __('Paper Size', 'gravitypdf'),
						'desc'    => __('Set the paper size used when generating PDFs.', 'gravitypdf'),
						'type'    => 'select',
						'options' => $this->get_paper_size(),
						'std'     => $this->get_option('default_pdf_size', 'A4'),
						'inputClass'   => 'large',
						'class' => 'gfpdf_paper_size',
						'chosen'  => true,
					),

					'custom_pdf_size' => array(
						'id'      => 'custom_pdf_size',
						'name'    => __('Custom Paper Size', 'gravitypdf'),
						'desc'    => __('Control the exact paper size. Can be set in millimeters or inches.', 'gravitypdf'),
						'type'    => 'paper_size',
						'size'    => 'small',
						'chosen'  => true,
						'required' => true,
						'class'   => 'gfpdf-hidden gfpdf_paper_size_other',
						'std'     => $this->get_option('default_custom_pdf_size'),
					),

					'orientation' => array(
						'id'      => 'orientation',
						'name'    => __('Orientation', 'gravitypdf'),
						'type'    => 'select',
						'options' => array(
							'portrait' => __('Portrait', 'gravitypdf'),
							'landscape' => __('Landscape', 'gravitypdf'),
						),
						'inputClass'   => 'large',
						'chosen'  => true,
					),

					'font' => array(
						'id'      => 'font',
						'name'    => __('Font', 'gravitypdf'),
						'type'    => 'select',
						'options' => $this->get_installed_fonts(),
						'std'     => $this->get_option('default_font_type'),
						'desc'    => __('Set the font to use in the PDF.', 'gravitypdf'),
						'inputClass'   => 'large',
						'chosen'  => true,
					),

					'font_colour' => array(
						'id'      => 'font_colour',
						'name'    => __('Font Colour', 'gravitypdf'),
						'type'    => 'color',
						'std'     => $this->get_option('default_font_colour'),
						'desc'    => __('Set the font colour used in the PDF.', 'gravitypdf'),
					),

					'rtl' => array(
						'id'    => 'rtl',
						'name'    => __('Reverse Text (RTL)', 'gravitypdf'),
						'desc'  => __('Languages like Arabic and Hebrew are written right to left.', 'gravitypdf'),
						'type'  => 'radio',
						'options' => array(
							'Yes' => __('Yes', 'gravitypdf'),
							'No'  => __('No', 'gravitypdf')
						),
						'std'   => $this->get_option('default_rtl', 'No'),
					),
															
				)
			),

			/**
			 * Form (PDF) Settings Custom Appearance
			 * This filter allows templates to add custom options for use specific to that template
			 * Gravity PDF autoloads a JSON template file if it exists and loads it up with this filter
			 */
			'form_settings_custom_appearance' => apply_filters('gfpdf_form_settings_custom_appearance', array()),

			/* Form (PDF) Settings Advanced */
			'form_settings_advanced' => apply_filters('gfpdf_form_settings_advanced',
				array(
					'format' => array(
						'id'    => 'format',
						'name'  => __('Format', 'gravitypdf'),
						'desc'  => __('Generate a PDF in the selected format.', 'gravitypdf'),
						'type'  => 'radio',
						'options' => array(
							'Standard' => 'Standard',
							'PDFA1B'  => 'PDF/A-1b',
							'PDFX1A'  => 'PDF/X-1a',
						),
						'std'   => 'Standard',
						'tooltip' => '<h6>' . __('PDF Format', 'gravitypdf') . '</h6>' . __("Generate a document adhearing to the appropriate PDF standard. When not in 'Standard' mode, watermarks, alpha-transparent PNGs and security options can NOT be used.", 'gravitypdf'),
					),

					'security' => array(
						'id'      => 'security',
						'name'    => __('Enable PDF Security', 'gravitypdf'),
						'desc'    => __('Password protect generated PDFs, or restrict user capabilities.', 'gravitypdf'),
						'type'    => 'radio',
						'options' => array(
							'Yes' => __('Yes', 'gravitypdf'),
							'No'  => __('No', 'gravitypdf')
						),
						'std'     => __('No', 'gravitypdf'),
					),

					'password' => array(
						'id'    => 'password',
						'name'  => __('Password', 'gravitypdf'),
						'type'  => 'text',
						'desc'  => 'Set a password to view PDFs. Leave blank to disable password protection.',
						'inputClass' => 'merge-tag-support mt-hide_all_fields',
					),

					'privileges' => array(
						'id'      => 'privileges',
						'name'    => __('Privileges', 'gravitypdf'),
						'desc'    => 'Restrict end-user capabilities.',
						'type'    => 'select',
						'options' => $this->get_privilages(),
						'std'     => array(
							'copy',
							'print',
							'print-highres',
							'modify',
							'annot-forms',
							'fill-forms',
							'extract',
							'assemble',
						),
						'inputClass'       => 'large',
						'chosen'      => true,
						'tooltip'     => '<h6>' . __('Privileges', 'gravitypdf') . '</h6>' . __("You can prevent the end-user completing certain actions to the PDF, such as copying text, printing, adding annotations or extracting pages.", 'gravitypdf'),
						'multiple'    => true,
						'placeholder' => __('Select PDF Privileges', 'gravitypdf'),
					),

					'image_dpi' => array(
						'id'    => 'image_dpi',
						'name'  => __('Image DPI', 'gravitypdf'),
						'type'  => 'number',
						'size'  => 'small',
						'std'   => 96,
						'tooltip' => '<h6>' . __('Image DPI', 'gravitypdf') . '</h6>' . __("Control the image DPI (dots per inch). Set to 300 when professionally printing.", 'gravitypdf'),
					),

					'save' => array(
						'id'    => 'save',
						'name'  => __('Always Save PDF?', 'gravitypdf'),
						'desc'  => __('Force a PDF to be saved to disk when a new entry is submitted.', 'gravitypdf'),
						'type'  => 'radio',
						'options' => array(
							'Yes' => __('Yes', 'gravitypdf'),
							'No'  => __('No', 'gravitypdf')
						),
						'std'   => __('No', 'gravitypdf'),
						'tooltip' => '<h6>' . __('Save PDF', 'gravitypdf') . '</h6>' . __("When notifications are disabled a PDF is not automatically saved to disk. Enable this option to force the PDF to be generated and saved. Useful when using the 'gfpdf_post_pdf_save' hook to copy / manipulate the PDF further.", 'gravitypdf'),
					),

				)
			),
		);

		return apply_filters( 'gfpdf_registered_settings', $gfpdf_settings );
	}

	/**
	 * Update a current registered settings
	 * @param  String $group_id     The top-level group we're updating
	 * @param  String $setting_id   The section group we're updating
	 * @param  String $option_id    The option we are updating
	 * @param  Mixed $option_value  The new option value
	 * @return Boolean               True on success, false on failure
	 */
	public function update_registered_field( $group_id, $setting_id, $option_id, $option_value) {
		global $wp_settings_fields;

		$group = 'gfpdf_settings_' . $group_id;
		$setting = "gfpdf_settings[$setting_id]";

		/* Check if our setting exists */
		if( isset($wp_settings_fields[$group][$group][$setting]['args'][$option_id]) ) {
			$wp_settings_fields[$group][$group][$setting]['args'][$option_id] = $option_value;
			return true;
		}

		return false;
	}

	/**
	 * Get an option
	 *
	 * Looks to see if the specified setting exists, returns default if not
	 *
	 * @since 4.0
	 * @return mixed
	 */
	public function get_option( $key = '', $default = false ) {
		
		$gfpdf_options = $this->settings;

		$value = ! empty( $gfpdf_options[ $key ] ) ? $gfpdf_options[ $key ] : $default;
		$value = apply_filters( 'gfpdf_get_option', $value, $key, $default );
		return apply_filters( 'gfpdf_get_option_' . $key, $value, $key, $default );
	}

	/**
	 * Update an option
	 *
	 * Updates an Gravity PDF setting value in both the db and the global variable.
	 * Warning: Passing in an empty, false or null string value will remove
	 *          the key from the gfpdf_options array.
	 *
	 * @since 4.0
	 * @param string $key The Key to update
	 * @param string|bool|int $value The value to set the key to
	 * @return boolean True if updated, false if not.
	 */
	public function update_option( $key = '', $value = false ) {

		// If no key, exit
		if ( empty( $key ) ){
			return false;
		}

		if ( empty( $value ) ) {
			$remove_option = $this->delete_option( $key );
			return $remove_option;
		}

		/* First let's grab the current settings */
		$options = get_option( 'gfpdf_settings' );

		/* Let's let devs alter that value coming in */
		$value = apply_filters( 'gfpdf_update_option', $value, $key );
		$value = apply_filters( 'gfpdf_update_option_' . $key, $value, $key );

		/* Next let's try to update the value */
		$options[ $key ] = $value;
		$did_update      = update_option( 'gfpdf_settings', $options );

		/* If it updated, let's update the global variable */
		if ( $did_update ){
			$this->settings[ $key ] = $value;
		}

		return $did_update;
	}

	/**
	 * Remove an option
	 *
	 * Removes an Gravity PDF setting value in both the db and the global variable.
	 *
	 * @since 4.0
	 * @param string $key The Key to delete
	 * @return boolean True if updated, false if not.
	 */
	public function delete_option( $key = '' ) {

		// If no key, exit
		if ( empty( $key ) ){
			return false;
		}

		// First let's grab the current settings
		$options = get_option( 'gfpdf_settings' );

		// Next let's try to update the value
		if( isset( $options[ $key ] ) ) {
			unset( $options[ $key ] );
		}

		$did_update = update_option( 'gfpdf_settings', $options );

		if ( $did_update ) {
			$this->settings = $options;
		}

		return $did_update;
	}

	/**
	 * Get a list of user capabilities
	 * @return array The array of roles available
	 * @since 4.0
	 */
	public function get_capabilities() {
       
		/* sort through all roles and fetch unique capabilities */
		$roles        = get_editable_roles();
		$capabilities = array();

        /* Add Gravity Forms Capabilities */
        $gf_caps = GFCommon::all_caps();

        foreach ($gf_caps as $gf_cap) {
            $capabilities['Gravity Forms Capabilities'][$gf_cap] = apply_filters('gfpdf_capability_name', $gf_cap);
        }

		foreach($roles as $role) {
			foreach($role['capabilities'] as $cap => $val) {
				if(!isset($capabilities[$cap]) && !in_array($cap, $gf_caps)) {
					$capabilities['Active WordPress Capabilities'][$cap] = apply_filters('gfpdf_capability_name', $cap);
				}
			}
		}

		/* sort alphabetically */
		foreach($capabilities as &$val) {
			ksort($val);
		}

		return apply_filters('gfpdf_capabilities', $capabilities);

	}

	/**
	 * Return our paper size
	 * @return array The array of paper sizes available
	 * @since 4.0
	 */
	public function get_paper_size() {
		return apply_filters( 'gfpdf_get_paper_size', array(
			__('Common Sizes', 'gravitypdf') => array(
				'A4'        => __('A4 (210 x 297mm)', 'gravitypdf'),
				'letter'    => __('Letter (8.5 x 11in)', 'gravitypdf'),
				'legal'     => __('Legal (8.5 x 14in)', 'gravitypdf'),
				'ledger'    => __('Ledger / Tabloid (11 x 17in)', 'gravitypdf'),
				'executive' => __('Executive (7 x 10in)', 'gravitypdf'),
				'custom'    => __('Custom Paper Size', 'gravitypdf'),
			),

			__('"A" Sizes', 'gravitypdf') => array(
				'A0' => __('A0 (841 x 1189mm)', 'gravitypdf'),
				'A1' => __('A1 (594 x 841mm)', 'gravitypdf'),
				'A2' => __('A2 (420 x 594mm)', 'gravitypdf'),
				'A3' => __('A3 (297 x 420mm)', 'gravitypdf'),
				'A5' => __('A5 (210 x 297mm)', 'gravitypdf'),
				'A6' => __('A6 (105 x 148mm)', 'gravitypdf'),
				'A7' => __('A7 (74 x 105mm)', 'gravitypdf'),
				'A8' => __('A8 (52 x 74mm)', 'gravitypdf'),
				'A9' => __('A9 (37 x 52mm)', 'gravitypdf'),
				'A10' => __('A10 (26 x 37mm)', 'gravitypdf'),
			),

			__('"B" Sizes', 'gravitypdf') => array(
				'B0' => __('B0 (1414 x 1000mm)', 'gravitypdf'),
				'B1' => __('B1 (1000 x 707mm)', 'gravitypdf'),
				'B2' => __('B2 (707 x 500mm)', 'gravitypdf'),
				'B3' => __('B3 (500 x 353mm)', 'gravitypdf'),
				'B4' => __('B4 (353 x 250mm)', 'gravitypdf'),
				'B5' => __('B5 (250 x 176mm)', 'gravitypdf'),
				'B6' => __('B6 (176 x 125mm)', 'gravitypdf'),
				'B7' => __('B7 (125 x 88mm)', 'gravitypdf'),
				'B8' => __('B8 (88 x 62mm)', 'gravitypdf'),
				'B9' => __('B9 (62 x 44mm)', 'gravitypdf'),
				'B10' => __('B10 (44 x 31mm)', 'gravitypdf'),
			),

			__('"C" Sizes', 'gravitypdf') => array(
				'C0' => __('C0 (1297 x 917mm)', 'gravitypdf'),
				'C1' => __('C1 (917 x 648mm)', 'gravitypdf'),
				'C2' => __('C2 (648 x 458mm)', 'gravitypdf'),
				'C3' => __('C3 (458 x 324mm)', 'gravitypdf'),
				'C4' => __('C4 (324 x 229mm)', 'gravitypdf'),
				'C5' => __('C5 (229 x 162mm)', 'gravitypdf'),
				'C6' => __('C6 (162 x 114mm)', 'gravitypdf'),
				'C7' => __('C7 (114 x 81mm)', 'gravitypdf'),
				'C8' => __('C8 (81 x 57mm)', 'gravitypdf'),
				'C9' => __('C9 (57 x 40mm)', 'gravitypdf'),
				'C10' => __('C10 (40 x 28mm)', 'gravitypdf'),
			),
			
			__('"RA" and "SRA" Sizes', 'gravitypdf') => array(
				'RA0' => __('RA0 (860 x 1220mm)', 'gravitypdf'),
				'RA1' => __('RA1 (610 x 860mm)', 'gravitypdf'),
				'RA2' => __('RA2 (430 x 610mm)', 'gravitypdf'),
				'RA3' => __('RA3 (305 x 430mm)', 'gravitypdf'),
				'RA4' => __('RA4 (215 x 305mm)', 'gravitypdf'),
				'SRA0' => __('SRA0 (900 x 1280mm)', 'gravitypdf'),
				'SRA1' => __('SRA1 (640 x 900mm)', 'gravitypdf'),
				'SRA2' => __('SRA2 (450 x 640mm)', 'gravitypdf'),
				'SRA3' => __('SRA3 (320 x 450mm)', 'gravitypdf'),
				'SRA4' => __('SRA4 (225 x 320mm)', 'gravitypdf'),
			),
		));
	}

	/**
	 * Parse our installed PDF template files
	 * @return array The array of templates
	 * @since 4.0
	 * @todo
	 */
	public function get_templates() {
		global $gfpdf;

		$templates = array();
		$legacy    = array();

		$prefix_text = __('User Templates: ', 'gravitypdf');
		$legacy_text = __('Legacy', 'gravitypdf');

		/**
		 * Load the user's templates
		 */
		foreach(glob( $gfpdf->data->template_location . '*.php') as $filename) {

			$info = $this->get_template_headers($filename);
			$file = basename($filename, '.php');

			if(!empty($info['template'])) {
				$templates[$prefix_text . $info['group']][$file] = $info['template'];
			} else if(substr($file, 0, 8) != 'example-') { /* exclude the example templates */
				$legacy[$file] = Stat_Functions::human_readable($file);
			}
		}

		/**
		 * Load templates included with Gravity PDF
		 * We'll exclude any files starting with example-, and any files overridden by the user
		 */
		foreach($this->get_plugin_pdf_templates() as $filename) {
			$info = $this->get_template_headers($filename);
			$file = basename($filename, '.php');
			
			/* only add core template if not being overridden by user template */
			if(!isset($templates[$prefix_text . $info['group']][$file])) {
				$templates[$info['group']][$file] = $info['template'];
			} else {
				/* user template overrides core template. Mark template. */
				$templates[$prefix_text . $info['group']][$file] = $templates[$prefix_text . $info['group']][$file] . '*';
			}
		}

		/*
		 * Add our legacy array to the end of our templates array
		 */
		if(sizeof($legacy) > 0) {
			$templates[$legacy_text] = $legacy;
		}

		return apply_filters('gfpdf_template_list', $templates);
	}

	/**
	 * An array used to parse the template headers
	 * @return Array
	 * @since 4.0
	 */
	public function get_template_header_details() {
		/**
		 * We load in data from the PDF template headers
		 * @var array
		 */
		return apply_filters('gfpdf_template_header_details', array(
			'template'             => __('Template Name', 'gravitypdf'),
			'version'              => __('Version', 'gravitypdf'),
			'description'          => __('Description', 'gravitypdf'),
			'author'               => __('Author', 'gravitypdf'),
			'group'                => __('Group', 'gravitypdf'),
			'required_pdf_version' => __('Required PDF Version', 'gravitypdf'),
		));
	}

	/**
	 * Gets the template information based on the raw template name
	 * @param  String $name The template to get information for
	 * @return Array       The template information
	 * @since 4.0
	 */
	public function get_template_information( $name ) {
		global $gfpdf;

		if( is_file( $gfpdf->data->template_location . $name . '.php')) {
			$template          = $this->get_template_headers($gfpdf->data->template_location . $name . '.php');
			$template['group'] = __('User Templates: ', 'gravitypdf') . $template['group'];
			return $template;
		}

		if( is_file( PDF_PLUGIN_DIR . 'initialisation/templates/' . $name . '.php')) {
			return $this->get_template_headers(PDF_PLUGIN_DIR . 'initialisation/templates/' . $name . '.php');
		}

		return false;
	}

	/**
	 * Get the current template headers
	 * @param  String $path The path to the file
	 * @return Array        Details about the file
	 * @since 4.0
	 */
	public function get_template_headers($path) {
		$info = get_file_data($path, $this->get_template_header_details());

		/* this is a legacy template */
		if(empty($info['template'])) {
			return array('group' => 'Legacy');
		} else {
			return $info;
		}
	}

    /**
     * Returns an array of the current PDF templates shipped with Gravity PDF
     * @return Array
     * @since 4.0
     */
    public function get_plugin_pdf_templates() {
        return glob( PDF_PLUGIN_DIR . 'initialisation/templates/*.php');
    }


	/**
	 * Parse our installed font files
	 * @return array The array of fonts
	 * @since 4.0
	 */
	public function get_installed_fonts() {
		$fonts = array(
			__('Unicode', 'gravitypdf') => array(
				'dejavusanscondensed'  => 'Dejavus Sans Condensed',
				'dejavusans'           => 'Dejavus Sans',
				'dejavuserifcondensed' => 'Dejavus Serif Condensed',
				'dejavuserif'          => 'Dejavus Serif',
				'dejavusansmono'       => 'Dejavus Sans Mono',
				
				'freesans'             => 'Free Sans',
				'freemono'             => 'Free Mono',
				
				'mph2bdamase'          => 'MPH 2B Damase',
			),
			
			__('Indic', 'gravitypdf')   => array(
				'lohitkannada' => 'Lohit Kannada',
				'pothana2000'  => 'Pothana2000',
			),
			
			__('Arabic', 'gravitypdf')  => array(
				'xbriyaz' => 'XB Riyaz',
				'lateef' => 'Lateef',
				'kfgqpcuthmantahanaskh' => 'Bahif Uthman Taha',
			),
			
			__('Other', 'gravitypdf')   => array(
				'estrangeloedessa' => 'Estrangelo Edessa (Syriac)',
				'kaputaunicode'    => 'Kaputa (Sinhala)',
				'abyssinicasil'    => 'Abyssinica SIL (Ethiopic)',
				'aboriginalsans'   => 'Aboriginal Sans (Cherokee / Canadian)',
				'jomolhari'        => 'Jomolhari (Tibetan)',
				'sundaneseunicode' => 'Sundanese (Sundanese)',
				'taiheritagepro'   => 'Tai Heritage Pro (Tai Viet)',
				'aegean'           => 'Aegean (Greek)',
				'quivira'          => 'Quivira (Greek)',
				'eeyekunicode'     => 'Eeyek (Meetei Mayek)',
				'lannaalif'        => 'Lanna Alif (Tai Tham)',
				'daibannasilbook'  => 'Dai Banna SIL (New Tai Lue)',
				'garuda'           => 'Garuda (Thai)',
				'khmeros'          => 'Khmer OS (Khmer)',
				'dhyana'           => 'Dhyana (Lao)',
				'tharlon'          => 'TharLon (Myanmar / Burmese)',
				'padaukbook'       => 'Padauk Book (Myanmar / Burmese)',
				'zawgyi-one'       => 'Zawgyi One (Myanmar / Burmese)',
				'ayar'             => 'Ayar Myanmar (Myanmar / Burmese)',
			),
		);

		$fonts = $this->add_custom_fonts($fonts);

		return apply_filters('gfpdf_font_list', $fonts);
	}

	/**
	 * If any custom fonts add them to our font list
	 * @param Array $fonts Current font list
	 * @since 4.0
	 */
	public function add_custom_fonts($fonts = array()) {
		$custom_fonts = $this->get_custom_fonts();

		if(sizeof($custom_fonts) > 0) {
			
			$user_defined_fonts = array();
			
			/* Loop through our fonts and assign them to a new array in the appropriate format */
			foreach($custom_fonts as $font) {
				$user_defined_fonts[ $font['shortname'] ] = $font['font_name'];
			}

			/* Merge the new fonts at the beginning of the $fonts array */
			$fonts = Stat_Functions::array_unshift_assoc( $fonts, __('User-Defined Fonts', 'gravitypdf'), $user_defined_fonts );
		}

		return $fonts;
	}

	/**
	 * Get a list of the custom fonts installed
	 * @return Array
	 * @since 4.0
	 */
	public function get_custom_fonts() {
		$fonts = $this->get_option('custom_fonts');

		if(is_array($fonts) && sizeof($fonts) > 0) {
			foreach($fonts as &$font) {
				$font['shortname'] = $this->get_font_short_name($font['font_name']);
			}

			return $fonts;
		}

		return array();
	}

    /**
     * Get font shortname we can use an in array
     * @param  String $name The font name to convert
     * @return String       Shortname of font
     * @since  4.0
     */
    public function get_font_short_name($name) {
        return mb_strtolower(str_replace(' ', '', $name), 'UTF-8');
    }

	/**
	 * Get the font's display name from the font key
	 * @param  String $font_key The font key to search for
	 * @return Mixed (String / Object)           The font display name or WP_Error
	 * @since 4.0
	 */
	public function get_font_display_name( $font_key ) {

		foreach($this->get_installed_fonts() as $groups) {
			if(isset($groups[ $font_key ])) {
				return $groups[ $font_key ];
			}
		}

		return new WP_Error('font_not_found', __('Could not find Gravity PDF Font'));
	}

	/**
	 * Parse our PDF privilages
	 * @return array The array of privilages
	 * @since 4.0
	 * @todo
	 */
	public function get_privilages() {
		$privilages = array(
			'copy'          => __('Copy', 'gravitypdf'),
			'print'         => __('Print - Low Resolution', 'gravitypdf'),
			'print-highres' => __('Print - High Resolution', 'gravitypdf'),
			'modify'        => __('Modify', 'gravitypdf'),
			'annot-forms'   => __('Annotate', 'gravitypdf'),
			'fill-forms'    => __('Fill Forms', 'gravitypdf'),
			'extract'       => __('Extract', 'gravitypdf'),
			'assemble'      => __('Assemble', 'gravitypdf'),
		);

		return apply_filters('gfpdf_privilages_list', $privilages);
	}

	/**
	 * Settings Sanitization
	 *
	 * Adds a settings error (for the updated message)
	 * Run on admin options.php page
	 *
	 * @since 4.0
	 *
	 * @param array $input The value inputted in the field
	 *
	 * @return string $input Sanitizied value
	 */
	public function settings_sanitize( $input = array() ) {

		$gfpdf_options = $this->settings;

		if ( empty( $_POST['_wp_http_referer'] ) ) {
			return $input;
		}

		parse_str( $_POST['_wp_http_referer'], $referrer );

		$all_settings = $this->get_registered_settings();
		$tab          = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';
		$settings     = (!empty($all_settings[$tab])) ? $all_settings[$tab] : array();

		/*
		 * Get all setting types
		 */
		$tab_len = strlen($tab);
		foreach($all_settings as $id => $s) {
			/*
			 * Check if extra item(s) belongs on page but isn't the existing page
			 * Note that this requires the section ID share a similar ID to what is referenced in $tab
			 */
			if($tab != $id && $tab == substr($id, 0, $tab_len)) {
				$settings = array_merge($settings, $s);
			}
		}

		$input = $input ? $input : array();
		$input = apply_filters( 'gfpdf_settings_' . $tab . '_sanitize', $input );

		/**
		 * Loop through the settings whitelist and add any missing required fields to the $input
		 * Prevalant with Select boxes
		 */
		foreach($settings as $key => $value) {
			if(isset($value['required']) && $value['required']) {
				switch($value['type']) {
					case 'select':
						if(!isset($input[$key])) {
							$input[$key] = array();
						}
					break;

					default:
						if(!isset($input[$key])) {
							$input[$key] = '';
						}
					break;
				}
			}
		}

		/* Loop through each setting being saved and pass it through a sanitization filter */
		foreach ( $input as $key => $value ) {

			/* Get the setting type (checkbox, select, etc) */
			$type = isset( $settings[$key]['type'] ) ? $settings[$key]['type'] : false;

			if ( $type ) {
				/* Field type specific filter */
				$input[$key] = apply_filters( 'gfpdf_settings_sanitize_' . $type, $value, $key, $input, $settings[$key] );
			}

			/* General filter */
			$input[$key] = apply_filters( 'gfpdf_settings_sanitize', $input[$key], $key, $input, $settings[$key] );
		}

		/* Loop through the whitelist and unset any that are empty for the tab being saved */
		foreach ( $settings as $key => $value ) {
			if ( empty( $input[$key] ) ) {
				unset( $gfpdf_options[$key] );
			}
		}

		/* check for errors */
		if(count(get_settings_errors()) === 0) {
			/* Merge our new settings with the existing */
			$output = array_merge( $gfpdf_options, $input );
			add_settings_error( 'gfpdf-notices', '', __( 'Settings updated.', 'gravitypdf' ), 'updated' );
		} else {
			/* error is thrown. store the user data in a transient so fields are remembered */
			set_transient('gfpdf_settings_user_data', array_merge( $gfpdf_options, $input ), 30);

			/* return nothing */
			return array();
		}

		return $output;
	}


	/**
	 * Sanitize text / textarea fields
	 *
	 * @since 4.0
	 * @param array $input The field value
	 * @return string $input Sanitizied value
	 */
	public function sanitize_trim_field( $input ) {
		return trim( $input );
	}

	/**
	 * Sanitize number fields
	 *
	 * @since 4.0
	 * @param array $input The field value
	 * @return string $input Sanitizied value
	 */
	public function sanitize_number_field( $input ) {
		return (integer) $input;
	}

	/**
	 * Sanitize all fields depending on type
	 *
	 * @since 4.0
	 * @param mixed   $value The field's user input value
	 * @param string  $key The settings key
	 * @param array   $input All user fields
	 * @param array   $settings The field settings
	 * @return string $input Sanitizied value
	 */
	public function sanitize_all_fields( $value, $key, $input, $settings ) {
		if(!isset($settings['type'])) {
			$settings['type'] = '';
		}

		switch($settings['type']) {
			case 'rich_editor':
			case 'textarea':
				return wp_kses_post( $value );
			break;

			/* treat as plain text */
			default:
				if(is_array($value)) {
					array_walk_recursive($value, 'wp_strip_all_tags');
					return $value;
				} else {
					return wp_strip_all_tags($value);
				}
				
			break;
		}
	}

	/**
	 * Sanitize all required fields
	 *
	 * @since 4.0
	 * @param mixed   $value The field's user input value
	 * @param string  $key The settings key
	 * @param array   $input All user fields
	 * @param array   $settings The field settings
	 * @return string $input Sanitizied value
	 */
	public function sanitize_required_field( $value, $key, $input, $settings ) {

		if(isset($settings['required']) && $settings['required'] === true) {

			switch($settings['type']) {
				case 'select':
				case 'multicheck':
					$size = count($value);
		            if(empty($value) || sizeof(array_filter($value)) !== $size) {
		               /* throw error */
		               add_settings_error( 'gfpdf-notices', $key, __( 'PDF Settings could not be saved. Please enter all required information below.', 'gravitypdf' ) );
		            }
				break;

				case 'paper_size':
					if(isset($input['default_pdf_size']) && $input['default_pdf_size'] === 'custom') {
			            $size = sizeof($value);
			            if(sizeof(array_filter($value)) !== $size) {
			               /* throw error */
			               add_settings_error( 'gfpdf-notices', $key, __( 'PDF Settings could not be saved. Please enter all required information below.', 'gravitypdf' ) );
			            }
					}
				break;

				default:
					if(strlen(trim($value)) === 0) {
		               /* throw error */
		               add_settings_error( 'gfpdf-notices', $key, __( 'PDF Settings could not be saved. Please enter all required information below.', 'gravitypdf' ) );
					}
				break;
			}
		}

		return $value;
	}

	/**
	 * Gets the correct option value based on the field type
	 * @param  array  $args The field articles
	 * @return String       The current value for that particular field
	 * @since  4.0
	 */
	public function get_form_value($args = array()) {

		/* if callbacks called directly */
		if(isset($args['value'])) {
			return $args['value'];
		}

		/* Get our global Gravity PDF Settings */
		$options = $this->settings;

		/* Get our PDF GF settings (if any) */
		$pdf_form_settings = $this->get_form_settings();

		if(!isset($args['type'])) {
			$args['type'] = '';
		}

		switch($args['type']) {
			case 'checkbox':

				if(isset( $options[ $args['id'] ] ) ) {
					return checked( 1, $options[ $args['id'] ], false );

				} elseif( isset( $pdf_form_settings[ $args['id'] ] ) ) {
					return checked( 1, $pdf_form_settings[ $args['id'] ], false );

				} elseif( $args['std'] === true ) {
					return checked(1, 1, false);
				}

			break;

			case 'multicheck':

				if(isset( $options[$args[ 'id' ] ][ $args['multi-key'] ] ) ) {
					return $args['multi-option'];

				} elseif( isset($pdf_form_settings[ $args['id']][ $args['multi-key' ] ] ) ) {
					return $args['multi-option'];
				}

			break;

			case 'radio':

				if( isset( $options[ $args['id'] ] ) && isset( $args[ 'options' ][ $options[ $args['id'] ] ] ) ) {
					return $options[ $args['id'] ];

				} elseif( isset( $pdf_form_settings[ $args['id'] ] ) && isset( $args[ 'options' ][ $pdf_form_settings[ $args['id'] ] ]) ) {
					return $pdf_form_settings[ $args['id']];

				} elseif( isset( $args['std'] ) && isset( $args['std'] ) ) {
					return $args['std'];
				}

			break;

			case 'password':

				if ( isset( $options[ $args['id'] ] ) ) {
					return trim($options[ $args['id'] ]);

				} elseif( isset( $pdf_form_settings[ $args['id'] ] ) ) {
					return trim($pdf_form_settings[ $args['id'] ] );
				}

			break;

			case 'select':
			case 'paper_size':
				if ( isset( $options[ $args['id'] ] ) ) {
					return $options[ $args['id'] ];

				} elseif( isset( $pdf_form_settings[ $args['id'] ] ) ) {
					return $pdf_form_settings[ $args['id'] ] ;

				} elseif( isset( $args['std'] ) ) {
					return $args['std'];
				}
			break;

			/* treat as a text callback */
			default:
				if ( isset( $options[ $args['id'] ] ) ) {
					return trim($options[ $args['id'] ]);

				} elseif( isset( $pdf_form_settings[ $args['id'] ] ) ) {
					return trim($pdf_form_settings[ $args['id'] ] );

				} elseif( isset( $args['std'] ) ) {
					return $args['std'];
				}
			break;
		}

		/* if we made it here return empty string */
		return '';
	}

	/**
	 * Checkbox Callback
	 *
	 * Renders checkboxes.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public function checkbox_callback( $args ) {
		/* get our selected value */
		$checked  = $this->get_form_value($args);
		$class    = (isset($args['inputClass'])) ? esc_attr($args['inputClass']) : '';
		$required = (isset($args['required']) && $args['required'] === true) ? 'required' : '';
		$id       = (isset($args['idOverride'])) ? esc_attr($args['idOverride']) : 'gfpdf_settings[' . esc_attr($args['id']) . ']';

		$html = '<input type="checkbox" id="'. $id .'" class="gfpdf_settings_' . $args['id'] . ' '. $class .'" name="gfpdf_settings[' . $args['id'] . ']" value="1" ' . $checked . ' ' . $required . ' />';
		$html .= '<label for="'. $id .'"> '  . wp_kses_post($args['desc']) . '</label>';
		
		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post($args['tooltip']) . '</span>';
		}

		echo $html;
	}

	/**
	 * Multicheck Callback
	 *
	 * Renders multiple checkboxes.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public function multicheck_callback( $args ) {

		$class      = (isset($args['inputClass'])) ? esc_attr($args['inputClass']) : '';
		$required   = (isset($args['required']) && $args['required'] === true) ? 'required' : '';
		$args['id'] = esc_attr($args['id']);

		if ( ! empty( $args['options'] ) ) {
			foreach( $args['options'] as $key => $option ):
				/* set up multi-select option to pass to our form value getter */
				$args['multi-key']    = esc_attr($key);
				$args['multi-option'] = $option;

				$enabled = $this->get_form_value($args);

				echo '<input name="gfpdf_settings[' . $args['id'] . '][' . esc_attr($key) . ']" id="gfpdf_settings[' . $args['id'] . '][' . esc_attr($key) . ']" class="gfpdf_settings_' . $args['id'] . ' '. $class .'" type="checkbox" value="' . $option . '" ' . checked($option, $enabled, false) . ' ' . $required . ' />&nbsp;';
				echo '<label for="gfpdf_settings[' . $args['id'] . '][' . esc_attr($key) . ']">' . $option . '</label><br />';
			endforeach;
			echo '<span class="gf_settings_description">' . wp_kses_post($args['desc']) . '</span>';
			
			if(isset($args['tooltip'])) {
				echo '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post($args['tooltip']) . '</span>';
			}
		}
	}

	/**
	 * Radio Callback
	 *
	 * Renders radio boxes.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public function radio_callback( $args ) {
		
		/* get selected value (if any) */
		$selected   = $this->get_form_value($args);
		$required   = (isset($args['required']) && $args['required'] === true) ? 'required' : '';
		$args['id'] = esc_attr($args['id']);

		foreach ( $args['options'] as $key => $option ) :
			$checked = false;
			if($selected == $key) {
				$checked = true;
			}

			echo '<label for="gfpdf_settings[' . $args['id'] . '][' . esc_attr($key) . ']"><input name="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . '][' . esc_attr($key) . ']" type="radio" value="' . esc_attr($key) . '" ' . checked(true, $checked, false) . ' '. $required .' />';
			echo $option . '</label> &nbsp;&nbsp;';
		endforeach;

		echo '<span class="gf_settings_description">' . wp_kses_post($args['desc']) . '</span>';
		
		if(isset($args['tooltip'])) {
			echo '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post($args['tooltip']) . '</span>';
		}
	}

	/**
	 * Text Callback
	 *
	 * Renders text fields.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public function text_callback( $args ) {
		
		/* get selected value (if any) */
		$value      = $this->get_form_value($args);
		$class      = (isset($args['inputClass'])) ? esc_attr($args['inputClass']) : '';
		$required   = (isset($args['required']) && $args['required'] === true) ? 'required' : '';
		$args['id'] = esc_attr($args['id']);

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? esc_attr($args['size']) : 'regular';
		$html = '<input type="text" class="' . $size . '-text '. $class .'" id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '" '. $required .' />';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . wp_kses_post($args['desc']) . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post($args['tooltip']) . '</span>';
		}

		echo $html;
	}

	/**
	 * Number Callback
	 *
	 * Renders number fields.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public function number_callback( $args ) {
		
		/* get selected value (if any) */
		$value = $this->get_form_value($args);

		/* ensure value is not an array */
		if(is_array($value)) {
			$value = implode(' ', $value);
		}

		/* check if required */
		$class      = (isset($args['inputClass'])) ? esc_attr($args['inputClass']) : '';
		$required   = (isset($args['required']) && $args['required'] === true) ? 'required' : '';
		$args['id'] = esc_attr($args['id']);

		$max  = isset( $args['max'] ) ? (int) $args['max'] : 999999;
		$min  = isset( $args['min'] ) ? (int) $args['min'] : 0;
		$step = isset( $args['step'] ) ? (int) $args['step'] : 1;

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? esc_attr($args['size']) : 'regular';
		$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text gfpdf_settings_' . $args['id'] . ' '. $class .'" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '" '. $required .' /> ' . $args['desc2'];
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . wp_kses_post($args['desc']) . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post($args['tooltip']) . '</span>';
		}

		echo $html;
	}

	/**
	 * Textarea Callback
	 *
	 * Renders textarea fields.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public function textarea_callback( $args ) {
		
		/* get selected value (if any) */
		$value      = $this->get_form_value($args);
		$class      = (isset($args['inputClass'])) ? esc_attr($args['inputClass']) : '';
		$required   = (isset($args['required']) && $args['required'] === true) ? 'required' : '';
		$args['id'] = esc_attr($args['id']);

		$html = '<textarea cols="50" rows="5" id="gfpdf_settings[' . $args['id'] . ']" class="large-text gfpdf_settings_' . $args['id'] . ' '. $class .'" name="gfpdf_settings[' . $args['id'] . ']" '. $required .'>' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . wp_kses_post($args['desc']) . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post($args['tooltip']) . '</span>';
		}

		echo $html;
	}

	/**
	 * Password Callback
	 *
	 * Renders password fields.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public function password_callback( $args ) {
		
		/* get selected value (if any) */
		$value      = $this->get_form_value($args);
		$class      = (isset($args['inputClass'])) ? esc_attr($args['inputClass']) : '';
		$required   = (isset($args['required']) && $args['required'] === true) ? 'required' : '';
		$args['id'] = esc_attr($args['id']);

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? esc_attr($args['size']) : 'regular';
		$html = '<input type="password" class="' . $size . '-text '. $class .'" id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" '. $required .' />';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . wp_kses_post($args['desc']) . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post($args['tooltip']) . '</span>';
		}

		echo $html;
	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public function select_callback($args) {
		
		/* get selected value (if any) */
		$value       = $this->get_form_value($args);
		$placeholder = ( isset( $args['placeholder'] ) ) ? esc_attr($args['placeholder']) : '';
		$chosen      = ( isset( $args['chosen'] ) ) ? 'gfpdf-chosen' : '';
		$class       = (isset($args['inputClass'])) ? esc_attr($args['inputClass']) : '';
		$required    = (isset($args['required']) && $args['required'] === true) ? 'required' : '';
		$args['id']  = esc_attr($args['id']);

		$multiple = $multipleExt = '';
		if(isset($args['multiple'])) {
			$multiple    = 'multiple';
			$multipleExt = '[]';
		}

	    $html = '<select id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . ' '. $class .' ' . $chosen . '" name="gfpdf_settings[' . $args['id'] . ']' . $multipleExt .'" data-placeholder="' . $placeholder . '" '. $multiple .' '. $required .'>';
	    
		foreach ( $args['options'] as $option => $name ) {
			if(!is_array($name)) {
				if(is_array($value)) {
					foreach($value as $v) {
						$selected = selected( $option, $v, false );
						if($selected != '') {
							break;
						}
					}
				} else {
					$selected = selected( $option, $value, false );
				}
				
				$html .= '<option value="' . esc_attr($option) . '" ' . $selected . '>' . esc_html($name) . '</option>';
			} else {
				$html .= '<optgroup label="' . esc_attr($option) . '">';
				foreach($name as $op_value => $op_label) {
					$selected = '';
					if(is_array($value)) {
						foreach($value as $v) {
							$selected = selected( $op_value, $v, false );
							if($selected != '') {
								break;
							}
						}
					} else {
						$selected = selected( $op_value, $value, false );
					}
					
					$html .= '<option value="' . esc_attr($op_value) . '" ' . $selected . '>' . esc_html($op_label) . '</option>';
				}
				$html .= '</optgroup>';
			}
		}

		$html .= '</select>';
		$html .= '<span class="gf_settings_description">'  . wp_kses_post($args['desc']) . '</span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post($args['tooltip']) . '</span>';
		}

		echo $html;
	}

	/**
	 * Rich Editor Callback
	 *
	 * Renders rich editor fields.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @global $wp_version WordPress Version
	 */
	public function rich_editor_callback( $args ) {
		/* get selected value (if any) */
		$value = $this->get_form_value($args);

		$rows       = isset( $args['size'] ) ? esc_attr($args['size']) : 20;
		$args['id'] = esc_attr($args['id']);
		$class      = (isset($args['inputClass'])) ? esc_attr($args['inputClass']) : '';

		if ( function_exists( 'wp_editor' ) ) {
			ob_start();
			echo '<span class="mt-gfpdf_settings_' . $args['id'] . '" style="float:right; position:relative; right: 10px; top: 90px;"></span>';
			wp_editor( stripslashes( $value ), 'gfpdf_settings_' . $args['id'], apply_filters('gfpdf_rich_editor_settings', array( 'textarea_name' => 'gfpdf_settings[' . $args['id'] . ']', 'textarea_rows' => $rows, 'editor_class' => 'gfpdf_settings_' . $args['id'] . ' ' . $class, 'autop' => false )) );
			$html = ob_get_clean();
		} else {
			$html = '<textarea class="large-text" rows="10" class="gfpdf_settings_' . $args['id'] . ' ' . $class . '" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		}

		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . wp_kses_post($args['desc']) . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post($args['tooltip']) . '</span>';
		}

		echo $html;
	}

	/**
	 * Upload Callback
	 *
	 * Renders upload fields.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public function upload_callback( $args ) {
		
		/* get selected value (if any) */
		$value                = $this->get_form_value($args);
		$uploader_title       = (isset($args['uploaderTitle']))        ? esc_attr($args['uploaderTitle']) : __('Select Media', 'gravitypdf');
		$uploader_button_text = (isset($args['uploaderButtonText']))   ? esc_attr($args['uploaderButtonText']) : __('Select Media', 'gravitypdf');
		$button_text          = (isset($args['buttonText']))           ? esc_attr($args['buttonText']) : __('Upload File', 'gravitypdf');
		$class                = (isset($args['inputClass'])) ? esc_attr($args['inputClass']) : '';
		$required             = (isset($args['required']) && $args['required'] === true) ? 'required' : '';
		$args['id']           = esc_attr($args['id']);
		$size                 = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? esc_attr($args['size']) : 'regular';

		$html = '<input type="text" class="' . $size . '-text gfpdf_settings_' . $args['id'] . ' '. $class .'" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '" '. $required .' />';
		$html .= '<span>&nbsp;<input type="button" class="gfpdf_settings_upload_button button-secondary" value="' . $button_text . '" data-uploader-title="'. $uploader_title . '" data-uploader-button-text="'. $uploader_button_text . '" /></span>';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . wp_kses_post($args['desc']) . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post($args['tooltip']) . '</span>';
		}

		echo $html;
	}


	/**
	 * Color picker Callback
	 *
	 * Renders color picker fields.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public function color_callback( $args ) {
		
		/* get selected value (if any) */
		$value      = $this->get_form_value($args);
		$default    = isset( $args['std'] ) ? esc_attr($args['std']) : '';
		$class      = (isset($args['inputClass'])) ? esc_attr($args['inputClass']) : '';
		$required   = (isset($args['required']) && $args['required'] === true) ? 'required' : '';
		$args['id'] = esc_attr($args['id']);

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? esc_attr($args['size']) : 'regular';
		$html = '<input type="text" class="gfpdf-color-picker gfpdf_settings_' . $args['id'] . ' '. $class .'" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" '. $required .' />';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . wp_kses_post($args['desc']) . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post($args['tooltip']) . '</span>';
		}

		echo $html;
	}

	/**
	 * Add a button callback.
	 *
	 * Renders a button onto the settings field.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	public function button_callback( $args ) {
		global $gfpdf;

		$tab = (isset($_GET['tab'])) ? esc_attr($_GET['tab']) : 'general';
		$nonce = wp_create_nonce('gfpdf_settings[' . $args['id'] . ']');


		$html = '<button id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . '][name]" value="'. $args['id'] . '" class="button gfpdf-button" type="submit">' . esc_html($args['std']) .'</button>';
		$html .= '<span class="gf_settings_description">'  . wp_kses_post($args['desc']) . '</span>';
		$html .= '<input type="hidden" name="gfpdf_settings[' . $args['id'] . '][nonce]" value="' . $nonce . '" />';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . wp_kses_post($args['tooltip']) . '</span>';
		}
		
		echo $html;
	}


	/**
	 * Gravity Forms Conditional Logic Callback
	 *
	 * Renders the GF Conditional logic container
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	public function conditional_logic_callback( $args ) {
		$args['idOverride'] = 'gfpdf_conditional_logic';

		$this->checkbox_callback($args);
		
		$html = '<div id="gfpdf_conditional_logic_container" class="gfpdf_conditional_logic">
			<!-- content dynamically created from form_admin.js -->
		</div>';
		
		echo $html;
	}

	/**
	 * Render a hidden field
	 *
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	public function hidden_callback( $args ) {
		
		/* get selected value (if any) */
		$value      = $this->get_form_value($args);
		$class      = (isset($args['inputClass'])) ? esc_attr($args['inputClass']) : '';
		$args['id'] = esc_attr($args['id']);

		$html = '<input type="hidden" class="'. $class .'" id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '" />';

		echo $html;
	}

	/**
	 * Render the custom paper size functionality
	 *
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	public function paper_size_callback( $args ) {
		
		/* get selected value (if any) */
		$value = $this->get_form_value($args);

		if(empty($value)) {
			$value = array('', '', 'mm');
		}

		$placeholder = ( isset( $args['placeholder'] ) ) ? esc_attr($args['placeholder']) : '';
		$chosen      = ( isset( $args['chosen'] ) ) ? 'gfpdf-chosen' : '';
		$class       = ( isset($args['inputClass']) ) ? esc_attr($args['inputClass']) : '';
		$size        = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? esc_attr($args['size']) : 'regular';

		$html = '<input type="number" class="'. $size .'-text gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . ']_width" min="1" name="gfpdf_settings[' . $args['id'] . '][]" value="' . esc_attr( stripslashes( $value[0] ) ) . '" required /> ' . __('Width', 'gravitypdf');
		$html .= ' <input type="number" class="'. $size .'-text gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . ']_height" min="1" name="gfpdf_settings[' . $args['id'] . '][]" value="' . esc_attr( stripslashes( $value[1] ) ) . '" required /> ' . __('Height', 'gravitypdf');

		$measurement = apply_filters( 'gfpdf_paper_size_dimensions', array(
			'millimeters' => __('mm', 'gravitypdf'),
			'inches' => __('inches', 'gravitypdf'),
		));

		$html .= '&nbsp; — &nbsp; <select id="gfpdf_settings[' . $args['id'] . ']_measurement" style="width: 75px" class="gfpdf_settings_' . $args['id'] . ' '. $class .' ' . $chosen . '" name="gfpdf_settings[' . $args['id'] . '][]" data-placeholder="' . $placeholder . '">';

		$measure_value = esc_attr( stripslashes( $value[2] ) );
		foreach($measurement as $key => $val) {
			$selected = ($measure_value === $key) ? 'selected="selected"' : '';
			$html .= '<option value="'. $key .'" '. $selected .'>' . $val . '</option>';
		}

		$html .= '</select> ';

		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . esc_attr($args['id']) . ']"> '  . wp_kses_post($args['desc']) . '</label></span>';

		echo $html;
	}

	/**
	 * Descriptive text callback.
	 *
	 * Renders descriptive text onto the settings field.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	public function descriptive_text_callback( $args ) {
		echo wp_kses_post( $args['desc'] );
	}

	/**
	 * Hook Callback
	 *
	 * Adds a do_action() hook in place of the field
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	public function hook_callback( $args ) {
		do_action( 'gfpdf_' . $args['id'], $args );
	}

	/**
	 * Missing Callback
	 *
	 * If a public function is missing for settings callbacks alert the user.
	 *
	 * @since 4.0
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	public function missing_callback($args) {
		printf( __( 'The callback used for the <strong>%s</strong> setting is missing.', 'gravitypdf' ), $args['id'] );
	}

}