<?php

namespace GFPDF\Stat;
use GFPDF\Model\Model_Form_Settings;

/**
 * Options API 
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
 * Pulled straight from the Gravity PDF register-settings.php file (props to Pippin and team)
 * @since 3.8
 */
class Stat_Options_API {
	
	/**
	 * Get an option
	 *
	 * Looks to see if the specified setting exists, returns default if not
	 *
	 * @since 3.8
	 * @return mixed
	 */
	public static function get_option( $key = '', $default = false ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

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
	 * @since 3.8
	 * @param string $key The Key to update
	 * @param string|bool|int $value The value to set the key to
	 * @return boolean True if updated, false if not.
	 */
	public static function update_option( $key = '', $value = false ) {

		// If no key, exit
		if ( empty( $key ) ){
			return false;
		}

		if ( empty( $value ) ) {
			$remove_option = self::delete_option( $key );
			return $remove_option;
		}

		// First let's grab the current settings
		$options = get_option( 'gfpdf_settings' );

		// Let's let devs alter that value coming in
		$value = apply_filters( 'gfpdf_update_option', $value, $key );

		// Next let's try to update the value
		$options[ $key ] = $value;
		$did_update      = update_option( 'gfpdf_settings', $options );

		// If it updated, let's update the global variable
		if ( $did_update ){
			global $gfpdf;
			$gfpdf_options = $gfpdf->data->settings;			
			$gfpdf_options[ $key ] = $value;

		}

		return $did_update;
	}

	/**
	 * Remove an option
	 *
	 * Removes an Gravity PDF setting value in both the db and the global variable.
	 *
	 * @since 3.8
	 * @param string $key The Key to delete
	 * @return boolean True if updated, false if not.
	 */
	public static function delete_option( $key = '' ) {

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
			global $gfpdf;
			$gfpdf_options = $options;
		}

		return $did_update;
	}

	/**
	 * Get Settings
	 *
	 * Retrieves all plugin settings
	 *
	 * @since 3.8
	 * @return array GFPDF settings
	 */
	public static function get_settings() {
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
	public static function get_form_settings() {
		/* get GF settings */
		$form_id = (int) rgget('id');
		$pid     = (!empty(rgget('pid'))) ? rgget('pid') : rgpost('gform_pdf_id');

        /* return early if no ID set */
        if(!$form_id) {
            return array();
        }

		$model = new Model_Form_Settings();
		return $model->get_settings($form_id);		
	}

	/**
	 * Add all settings sections and fields
	 *
	 * @since 3.8
	 * @return void
	*/
	public static function register_settings() {

		foreach( self::get_registered_settings() as $tab => $settings ) {

			foreach ( $settings as $option ) {

				$name = isset( $option['name'] ) ? $option['name'] : '';

				add_settings_field(
					'gfpdf_settings[' . $option['id'] . ']',
					$name,
					method_exists(  __CLASS__, $option['type'] . '_callback' ) ? array(__CLASS__, $option['type'] . '_callback') : array(__CLASS__, 'missing_callback'),
					'gfpdf_settings_' . $tab,
					'gfpdf_settings_' . $tab,
					array(
						'section'     => $tab,
						'id'          => isset( $option['id'] )          ? $option['id']      : null,
						'desc'        => ! empty( $option['desc'] )      ? $option['desc']    : '',
						'desc2'       => ! empty( $option['desc2'] )     ? $option['desc2']    : '',
						'name'        => isset( $option['name'] )        ? $option['name']    : null,
						'size'        => isset( $option['size'] )        ? $option['size']    : null,
						'options'     => isset( $option['options'] )     ? $option['options'] : '',
						'std'         => isset( $option['std'] )         ? $option['std']     : '',
						'min'         => isset( $option['min'] )         ? $option['min']     : null,
						'max'         => isset( $option['max'] )         ? $option['max']     : null,
						'step'        => isset( $option['step'] )        ? $option['step']    : null,
						'chosen'      => isset( $option['chosen'] )      ? $option['chosen']  : null,
						'class'       => isset( $option['class'] )       ? $option['class']  : null,
						'inputClass'  => isset( $option['inputClass'] )       ? $option['inputClass']  : null,
						'placeholder' => isset( $option['placeholder'] ) ? $option['placeholder'] : null,
						'allow_blank' => isset( $option['allow_blank'] ) ? $option['allow_blank'] : true,	                    
						'tooltip'     => isset( $option['tooltip'] )     ? $option['tooltip'] : null,	 						
						'multiple'    => isset( $option['multiple'] )    ? $option['multiple'] : null,	 
						'required'    => isset( $option['required'] )    ? $option['required'] : null,	
					)
				);
			}

		}

		/* Creates our settings in the options table */
		register_setting( 'gfpdf_settings', 'gfpdf_settings', array(__CLASS__, 'settings_sanitize') );

		/* register our santize functions */
		add_filter( 'gfpdf_settings_sanitize_text', array(__CLASS__, 'sanitize_text_field') );		
		add_filter( 'gfpdf_settings_sanitize_paper_size', array(__CLASS__, 'sanitize_paper_size_field'), 10, 3 );		
	}

	/**
	 * Retrieve the array of plugin settings
	 *
	 * @since 3.8
	 * @return array
	*/
	public static function get_registered_settings() {

		global $gfpdf;

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
						'name'       => __('Default Paper Size', 'pdfextended'),
						'desc'       => __('Set the default paper size used when generating PDFs. This setting is overridden if you set the PDF size when configuring individual PDFs.', 'pdfextended'),
						'type'       => 'select',
						'options'    => self::get_paper_size(),	
						'inputClass' => 'large',	
						'chosen'     => true,	
						'class'      => 'gfpdf_paper_size',			
					),

					'default_custom_pdf_size' => array(
						'id'      => 'default_custom_pdf_size',
						'name'    => __('Custom Paper Size', 'pdfextended'),
						'desc'    => __('Control the exact paper size. Can be set in millimeters or inches.', 'pdfextended'),						
						'type'    => 'paper_size',	
						'size'    => 'small',
						'chosen'  => true,
						'required' => true,
						'class'   => 'gfpdf-hidden gfpdf_paper_size_other',						
					),				

					'default_template' => array(
						'id'      => 'default_template',
						'name'    => __('Default Template', 'pdfextended'),
						'desc'    => __('Set the default paper size used when generating PDFs. This setting is overridden if you set the PDF size when configuring individual PDFs.', 'pdfextended'),
						'type'    => 'select',
						'options' => self::get_templates(),	
						'inputClass'   => 'large',	
						'chosen'  => true,				
					),

					'default_font_type' => array(
						'id'      => 'default_font_type',
						'name'    => __('Default Font Type', 'pdfextended'),
						'desc'    => __('Set the default paper size used when generating PDFs. This setting is overridden if you set the PDF size when configuring individual PDFs.', 'pdfextended'),
						'type'    => 'select',
						'options' => self::get_installed_fonts(),	
						'inputClass'   => 'large',	
						'chosen'  => true,				
					),	

					'default_rtl' => array(
						'id'    => 'default_rtl',
						'name'    => __('Reverse Text (RTL)', 'pdfextended'),
						'desc'  => __('Written languages like Arabic and Hebrew are written right to left.', 'pdfextended'),						
						'type'  => 'radio',						
						'options' => array(
							'Yes' => __('Yes', 'pdfextended'),
							'No'  => __('No', 'pdfextended')
						),
						'std'   => __('No', 'pdfextended'),						
					),										

					'default_action' => array(
						'id'      => 'default_action',
						'name'    => __('Entry View', 'pdfextended'),
						'desc'    => sprintf(__('Select the default action used when accessing a PDF from the %sGravity Forms entries list%s page.'), '<a href="'. admin_url('admin.php?page=gf_entries') . '">', '</a>'),
						'type'    => 'radio',
						'options' => array(
							'View'     => __('View', 'pdfextended'), 
							'Download' => __('Download', 'pdfextended'),
						),
						'std'     => 'View',
					),					

					
				)
			),

			'general_security' => apply_filters( 'gfpdf_settings_general_security',
				array(
					'limit_to_admin' => array(
						'id'    => 'limit_to_admin',
						'name'  => __('Restrict PDFs to Admins', 'pdfextended'),
						'desc'  => __('Restrict PDF access to users with the <em>"Gravity Forms View Entries"</em> privilege. By default this is administrators only.', 'pdfextended'),						
						'type'  => 'radio',						
						'options' => array(
							'Yes' => __('Yes', 'pdfextended'),
							'No'  => __('No', 'pdfextended')
						),
						'std'   => __('No', 'pdfextended'),
						'tooltip' => '<h6>' . __('Restrict Access to Administrators Only', 'pdfextended') . '</h6>' . __("Enable this option if you don't want users accessing the generated PDFs. This is userful if the documents are for internal use, or security is a major concern.", 'pdfextended'),
					),				

					'logged_out_timeout' => array(
						'id'    => 'logged_out_timeout',
						'name'  => __('Logged Out Timeout', 'pdfextended'),
						'desc'  => __('How long a <em>logged out</em> users has direct access to the PDF after completing the form. Set to 0 to disable (not recommended).', 'pdfextended'),
						'desc2' => __('minutes', 'pdfextended'),
						'type'  => 'number',
						'size'  => 'small',
						'std'   => 20,
						'tooltip' => '<h6>' . __('Logged Out Timeout', 'pdfextended') . '</h6>' . __("By default, logged out users can view PDFs when their IP matches the IP assigned to the Gravity Form entry. But because IP addresses can change frequently a time-based restriction also applies.", 'pdfextended'),
					),					
				)
			),

			
			/** Extension Settings */
			'extensions' => apply_filters('gfpdf_settings_extensions',
				array()
			),
			'licenses' => apply_filters('gfpdf_settings_licenses',
				array()
			),

			'tools' => apply_filters('gfpdf_settings_tools',
				array(
					'reinstall' => array(
						'id'    => 'copy',
						'name'  => __('Setup Custom Templates', 'pdfextended'),
						'desc'  => sprintf(__("Ready to get down and dirty with custom PDF templates? %sSee docs to get started%s.", 'pdfextended'), '<a href="#">', '</a>'),						
						'type'  => 'button',						
						'std'   => __('Run Setup', 'pdfextended'),
						'options' => 'copy',
						'tooltip' => '<h6>' . __('Setup Custom Templates', 'pdfextended') . '</h6>' . __('TODO... Write Copy', 'pdfextended'),
					),

					'manage_fonts' => array(
						'id'    => 'manage_fonts',
						'name'  => __('Fonts', 'pdfextended'),
						'desc'  => __("Add, update or remove custom fonts.", 'pdfextended'),						
						'type'  => 'button',						
						'std'   => __('Manage Fonts', 'pdfextended'),
						'options' => 'install_fonts',
						'tooltip' => '<h6>' . __('Install Fonts', 'pdfextended') . '</h6>' . sprintf(__("Custom fonts can be installed and used in your PDFs. Currently only %s.ttf%s and %s.otf%s font files are supported. Once installed, fonts can be used in your custom PDF templates with a CSS %sfont-family%s declaration.", 'pdfextended'), '<code>', '</code>', '<code>', '</code>', '<code>', '</code>'),
					),													
				)
			),

			/* Form (PDF) Settings */
			'form_settings' => apply_filters('gfpdf_form_settings', 
				array(
					'name' => array(
						'id'   => 'name',
						'name' => __('Name', 'pdfextended'),												
						'type' => 'text',
						'required' => true,
					),					

					'template' => array(
						'id'      => 'template',
						'name'    => __('Template', 'pdfextended'),												
						'desc'    =>  sprintf(__('Choose from the pre-installed templates or %sbuild your own%s.', 'pdfextended'), '<a href="#">', '</a>'),
						'type'    => 'select',
						'options' => self::get_templates(),	
						'inputClass'   => 'large',	
						'chosen'  => true,							
						'tooltip' => '<h6>' . __('Templates', 'pdfextended') . '</h6>' . __('Set the template used to generate your PDF.', 'pdfextended'),
					),

					'notification' => array(
						'id'       => 'notification',
						'name'     => __('Notifications', 'pdfextended'),												
						'desc'     => __('Automatically attach PDF to the selected notifications.', 'pdfextended'),
						'type'     => 'select',
						'options'  => array(
							'Admin Notification' => 'Admin Notification',
							'User Notification' => 'User Notification',
						),	
						'inputClass'    => 'large',	
						'chosen'   => true,													
						'multiple' => true,
						'placeholder' => __('Choose a Notification', 'pdfextended'),
					),							

					'filename' => array(
						'id'   => 'filename',
						'name' => __('Filename', 'pdfextended'),												
						'type' => 'text',						
						'desc' => 'The name used when saving a PDF. Mergetags are allowed.',
						'tooltip' => '<h6>' . __('Filename', 'pdfextended') . '</h6>' . __('Set an appropriate filename for the generated PDF. You should exclude the .pdf extension from the name.', 'pdfextended'),
						'inputClass' => 'merge-tag-support mt-hide_all_fields',
						'required' => true,
					),									

					'conditional' => array(
						'id'      => 'pdf',						
						'name'    => __('Conditional Logic', 'pdfextended'),												
						'type'    => 'conditional_logic',					
						'desc'	  => __('Enable conditional logic', 'pdfextended'),	
						'class'   => 'conditional_logic',
						'inputClass' => 'conditional_logic_listener',
						'tooltip' => '<h6>' . __('Conditional Logic', 'pdfextended') . '</h6>' . __('Create rules to dynamically enable or disable PDFs. This includes attaching to notifications and viewing.', 'pdfextended'),												
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
						'name'    => __('Paper Size', 'pdfextended'),
						'desc'    => __('Set the paper size used when generating PDFs.', 'pdfextended'),
						'type'    => 'select',
						'options' => self::get_paper_size(),	
						'std'     => self::get_option('default_pdf_size'),
						'inputClass'   => 'large',	
						'class' => 'gfpdf_paper_size',
						'chosen'  => true,				
					),	

					'custom_pdf_size' => array(
						'id'      => 'custom_pdf_size',
						'name'    => __('Custom Paper Size', 'pdfextended'),	
						'desc'    => __('Control the exact paper size. Can be set in millimeters or inches.', 'pdfextended'),					
						'type'    => 'paper_size',	
						'size'    => 'small',
						'chosen'  => true,
						'required' => true,
						'class'   => 'gfpdf-hidden gfpdf_paper_size_other',
						'std'     => self::get_option('default_custom_pdf_size'),
					),						

					'orientation' => array(
						'id'      => 'orientation',
						'name'    => __('Orientation', 'pdfextended'),						
						'type'    => 'select',
						'options' => array(
							'portrait' => __('Portrait', 'pdfextended'),
							'landscape' => __('Landscape', 'pdfextended'),
						),	
						'inputClass'   => 'large',	
						'chosen'  => true,				
					),	

					'font' => array(
						'id'      => 'font',
						'name'    => __('Font', 'pdfextended'),						
						'type'    => 'select',
						'options' => self::get_installed_fonts(),
						'std'     => self::get_option('default_font_type'),	
						'desc'    => __('Set the default font used in the PDF.', 'pdfextended'),
						'inputClass'   => 'large',	
						'chosen'  => true,				
					),	

					'rtl' => array(
						'id'    => 'rtl',
						'name'    => __('Reverse Text (RTL)', 'pdfextended'),
						'desc'  => __('Written languages like Arabic and Hebrew are written right to left.', 'pdfextended'),						
						'type'  => 'radio',						
						'options' => array(
							'Yes' => __('Yes', 'pdfextended'),
							'No'  => __('No', 'pdfextended')
						),
						'std'   => self::get_option('default_rtl'),						
					),					
															
				)
			),

			/* Form (PDF) Settings Advanced */
			'form_settings_advanced' => apply_filters('gfpdf_form_settings_advanced', 
				array(
					'format' => array(
						'id'    => 'format',
						'name'  => __('Format', 'pdfextended'),
						'desc'  => __('Generate a PDF in the selected format.', 'pdfextended'),						
						'type'  => 'radio',						
						'options' => array(
							'Standard' => 'Standard',
							'PDFA1B'  => 'PDF/A-1b',
							'PDFX1A'  => 'PDF/X-1a',
						),
						'std'   => 'Standard',
						'tooltip' => '<h6>' . __('PDF Format', 'pdfextended') . '</h6>' . __("Generate a document adhearing to the appropriate PDF standard. When not in 'Standard' mode, watermarks, alpha-transparent PNGs and security options can NOT be used.", 'pdfextended'),
					),	

					'security' => array(
						'id'      => 'security',
						'name'    => __('Enable PDF Security', 'pdfextended'),
						'desc'    => __('Password protect generated PDFs, or restrict user capabilities.', 'pdfextended'),						
						'type'    => 'radio',						
						'options' => array(
							'Yes' => __('Yes', 'pdfextended'),
							'No'  => __('No', 'pdfextended')
						),
						'std'     => __('No', 'pdfextended'),						
					),

					'password' => array(
						'id'    => 'password',
						'name'  => __('Password', 'pdfextended'),												
						'type'  => 'text',						
						'desc'  => 'Set a password to view PDFs. Leave blank to disable password protection.',
						'inputClass' => 'merge-tag-support mt-hide_all_fields',
					),	

					'privilages' => array(
						'id'      => 'privileges',
						'name'    => __('Privileges', 'pdfextended'),												
						'desc'    => 'Restrict end-user capabilities.',
						'type'    => 'select',
						'options' => self::get_privilages(),
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
						'tooltip'     => '<h6>' . __('Privileges', 'pdfextended') . '</h6>' . __("You can prevent the end-user completing certain actions to the PDF, such as copying text, printing, adding annotations or extracting pages.", 'pdfextended'),
						'multiple'    => true,
						'placeholder' => __('Select PDF Privileges', 'pdfextended'),
					),

					'image_dpi' => array(
						'id'    => 'image_dpi',
						'name'  => __('Image DPI', 'pdfextended'),						
						'type'  => 'number',
						'size'  => 'small',
						'std'   => 96,
						'tooltip' => '<h6>' . __('Image DPI', 'pdfextended') . '</h6>' . __("Control the image DPI (dots per inch). Set to 300 when professionally printing.", 'pdfextended'),
					),					

					'save' => array(
						'id'    => 'save',
						'name'  => __('Always Save PDF?', 'pdfextended'),
						'desc'  => __('Force a PDF to be saved to disk when a new entry is submitted.', 'pdfextended'),						
						'type'  => 'radio',						
						'options' => array(
							'Yes' => __('Yes', 'pdfextended'),
							'No'  => __('No', 'pdfextended')
						),
						'std'   => __('No', 'pdfextended'),
						'tooltip' => '<h6>' . __('Save PDF', 'pdfextended') . '</h6>' . __("When notifications are disabled a PDF is not automatically saved to disk. Enable this option to force the PDF to be generated and saved. Useful when using the 'gfpdf_post_pdf_save' hook to copy / manipulate the PDF further.", 'pdfextended'),
					),

				)
			),
		);

		return apply_filters( 'gfpdf_registered_settings', $gfpdf_settings );
	}

	/**
	 * [highlight_errors description]
	 * @param  [type] $settings [description]
	 * @return [type]           [description]
	 */
	public static function highlight_errors($settings) {
		global $gfpdf;
		
		/* we fire too late to tap into get_settings_error() so our data storage holds the details */
		$errors = $gfpdf->data->form_settings_errors;

		/* loop through errors if any and highlight the appropriate settings */
		if(is_array($errors) && sizeof($errors) > 0) {
			foreach($errors as $error) {
				/* exit if not an error */
				if($error['type'] !== 'error') {
					continue;
				}

				/* loop through our data until we find a match */
				$found = false;
				foreach($settings as $key => &$group) {
					foreach($group as $id => &$item) {
						if($item['id'] === $error['code']) {
							$item['class'] = (isset($item['class'])) ? $item['class'] . ' gfield_error' : 'gfield_error';
							$found = true;
							break;
						}
					}

					/* exit outer loop */
					if($found) {
						break;
					}
				}
			}
		}		

		return $settings;
	}

	/**
	 * Return our paper size 
	 * @return array The array of paper sizes avaiable 
	 * @todo allow "Other" option
	 * @since 4.0
	 */	
	public static function get_paper_size() {
		return apply_filters( 'gfpdf_get_paper_size', array(
			'Common Sizes' => array(
				'A4'        => __('A4 (210 x 297mm)', 'pdfextended'),
				'letter'    => __('Letter (8.5 x 11in)', 'pdfextended'),
				'legal'     => __('Legal (8.5 x 14in)', 'pdfextended'),
				'ledger'    => __('Ledger / Tabloid (11 x 17in)', 'pdfextended'),
				'executive' => __('Executive (7 x 10in)', 'pdfextended'),
				'custom'    => __('Custom Paper Size', 'pdfextended'),
			),

			'"A" Sizes' => array(
				'A0' => __('A0 (841 x 1189mm)', 'pdfextended'),
				'A1' => __('A1 (594 x 841mm)', 'pdfextended'),
				'A2' => __('A2 (420 x 594mm)', 'pdfextended'),
				'A3' => __('A3 (297 x 420mm)', 'pdfextended'),				
				'A5' => __('A5 (210 x 297mm)', 'pdfextended'),
				'A6' => __('A6 (105 x 148mm)', 'pdfextended'),
				'A7' => __('A7 (74 x 105mm)', 'pdfextended'),
				'A8' => __('A8 (52 x 74mm)', 'pdfextended'),
				'A9' => __('A9 (37 x 52mm)', 'pdfextended'),
				'A10' => __('A10 (26 x 37mm)', 'pdfextended'),
			),

			'"B" Sizes' => array(
				'B0' => __('B0 (1414 x 1000mm)', 'pdfextended'),
				'B1' => __('B1 (1000 x 707mm)', 'pdfextended'),
				'B2' => __('B2 (707 x 500mm)', 'pdfextended'),
				'B3' => __('B3 (500 x 353mm)', 'pdfextended'),				
				'B4' => __('B4 (353 x 250mm)', 'pdfextended'),				
				'B5' => __('B5 (250 x 176mm)', 'pdfextended'),
				'B6' => __('B6 (176 x 125mm)', 'pdfextended'),
				'B7' => __('B7 (125 x 88mm)', 'pdfextended'),
				'B8' => __('B8 (88 x 62mm)', 'pdfextended'),
				'B9' => __('B9 (62 x 44mm)', 'pdfextended'),
				'B10' => __('B10 (44 x 31mm)', 'pdfextended'),
			),		

			'"C" Sizes' => array(
				'C0' => __('C0 (1297 x 917mm)', 'pdfextended'),
				'C1' => __('C1 (917 x 648mm)', 'pdfextended'),
				'C2' => __('C2 (648 x 458mm)', 'pdfextended'),
				'C3' => __('C3 (458 x 324mm)', 'pdfextended'),				
				'C4' => __('C4 (324 x 229mm)', 'pdfextended'),				
				'C5' => __('C5 (229 x 162mm)', 'pdfextended'),
				'C6' => __('C6 (162 x 114mm)', 'pdfextended'),
				'C7' => __('C7 (114 x 81mm)', 'pdfextended'),
				'C8' => __('C8 (81 x 57mm)', 'pdfextended'),
				'C9' => __('C9 (57 x 40mm)', 'pdfextended'),
				'C10' => __('C10 (40 x 28mm)', 'pdfextended'),
			),		
			
			'"RA" and "SRA" Sizes' => array(
				'RA0' => __('RA0 (860 x 1220mm)', 'pdfextended'),
				'RA1' => __('RA1 (610 x 860mm)', 'pdfextended'),
				'RA2' => __('RA2 (430 x 610mm)', 'pdfextended'),
				'RA3' => __('RA3 (305 x 430mm)', 'pdfextended'),				
				'RA4' => __('RA4 (215 x 305mm)', 'pdfextended'),				
				'SRA0' => __('SRA0 (900 x 1280mm)', 'pdfextended'),
				'SRA1' => __('SRA1 (640 x 900mm)', 'pdfextended'),
				'SRA2' => __('SRA2 (450 x 640mm)', 'pdfextended'),
				'SRA3' => __('SRA3 (320 x 450mm)', 'pdfextended'),
				'SRA4' => __('SRA4 (225 x 320mm)', 'pdfextended'),				
			),						
		)); 		
	}

	/**
	 * Parse our installed PDF template files
	 * @return array The array of templates 
	 * @since 4.0
	 */
	public static function get_templates() {
		$templates = array(
			'Pre-Installed' => array(
				'Awesomeness' => 'Awesomeness',
				'Gravity Forms Style' => 'Gravity Forms Style',
			),
			'Custom Templates' => array(
				'Example1' => 'Example1',
				'Example2' => 'Example2',
			),
		);

		return $templates;
	}

	public static function get_installed_fonts() {
		$fonts = array(
			'dejavusans' => __('Dejavu Sans', 'pdfextended'),
			'dejavusansserif' => __('Dejavu Sans Serif', 'pdfextended'),
		);

		return $fonts;
	}

	public static function get_privilages() {
		$privilages = array(
			'copy'          => __('Copy', 'pdfextended'),
			'print'         => __('Print - Low Resolution', 'pdfextended'),
			'print-highres' => __('Print - High Resolution', 'pdfextended'),
			'modify'        => __('Modify', 'pdfextended'),
			'annot-forms'   => __('Annotate', 'pdfextended'),
			'fill-forms'    => __('Fill Forms', 'pdfextended'),
			'extract'       => __('Extract', 'pdfextended'),
			'assemble'      => __('Assemble', 'pdfextended'),
			
		);

		return $privilages;
	}

	/**
	 * Settings Sanitization
	 *
	 * Adds a settings error (for the updated message)
	 * At some point this will validate input
	 *
	 * @since 3.8
	 *
	 * @param array $input The value inputted in the field
	 *
	 * @return string $input Sanitizied value
	 */
	public static function settings_sanitize( $input = array() ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		if ( empty( $_POST['_wp_http_referer'] ) ) {
			return $input;
		}

		parse_str( $_POST['_wp_http_referer'], $referrer );

		$settings = self::get_registered_settings();
		$tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';

		$input = $input ? $input : array();
		$input = apply_filters( 'gfpdf_settings_' . $tab . '_sanitize', $input );

		// Loop through each setting being saved and pass it through a sanitization filter
		foreach ( $input as $key => $value ) {

			// Get the setting type (checkbox, select, etc)
			$type = isset( $settings[$tab][$key]['type'] ) ? $settings[$tab][$key]['type'] : false;

			if ( $type ) {
				// Field type specific filter
				$input[$key] = apply_filters( 'gfpdf_settings_sanitize_' . $type, $value, $key, $input );
			}

			// General filter
			$input[$key] = apply_filters( 'gfpdf_settings_sanitize', $input[$key], $key, $input );
		}

		// Loop through the whitelist and unset any that are empty for the tab being saved
		if ( ! empty( $settings[$tab] ) ) {
			foreach ( $settings[$tab] as $key => $value ) {

				// settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
				if ( is_numeric( $key ) ) {
					$key = $value['id'];
				}

				if ( empty( $input[$key] ) ) {
					unset( $gfpdf_options[$key] );
				}

			}
		}

		/* check for errors */
		if(count(get_settings_errors()) === 0) {
			/* Merge our new settings with the existing */
			$output = array_merge( $gfpdf_options, $input );
			add_settings_error( 'gfpdf-notices', '', __( 'Settings updated.', 'pdfextended' ), 'updated' );
		} else {
			/* store the user data in a transient */
			set_transient('gfpdf_settings_user_data', array_merge( $gfpdf_options, $input ), 30);
		}

		return $output;
	}


	/**
	 * Sanitize text fields
	 *
	 * @since 3.8
	 * @param array $input The field value
	 * @return string $input Sanitizied value
	 */
	public static function sanitize_text_field( $input ) {
		return trim( $input );
	}

	/**
	 * Sanitize paper size fields
	 *
	 * @since 3.8
	 * @param array $input The field value
	 * @return string $input Sanitizied value
	 */
	public static function sanitize_paper_size_field( $value, $key, $input ) {
		if($input['default_pdf_size'] === 'custom') {
            $size = sizeof($value);
            if(sizeof(array_filter($value)) !== $size) {
               /* throw error */
               add_settings_error( 'gfpdf-notices', $key, __( 'PDF Settings could not be saved. Please enter all required information below.', 'pdfextended' ) );
            }			
		}

		return $value;
	}	


	/**
	 * Checkbox Callback
	 *
	 * Renders checkboxes.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function checkbox_callback( $args ) {
		global $gfpdf;		
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();

		$checked = isset( $gfpdf_options[ $args['id'] ] ) ? checked( 1, $gfpdf_options[ $args['id'] ], false ) : '';
		$checked = (empty($checked) && isset( $gfpdf_form_settings[ $pid ][ $args['id']])) ? checked( 1, $gfpdf_form_settings[ $pid ][ $args['id']], false ) : '';

		$class = '';
		if(isset($args['inputClass'])) {
			$class = $args['inputClass'];
		}		

		$id = 'gfpdf_settings[' . $args['id'] . ']';
		if(isset($args['idOverride'])) {
			$id = $args['idOverride'];
		}

		$html = '<input type="checkbox" id="'. $id .'" class="gfpdf_settings_' . $args['id'] . ' '. $class .'" name="gfpdf_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
		$html .= '<label for="'. $id .'"> '  . $args['desc'] . '</label>';
		
		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
		}		

		echo $html;
	}

	/**
	 * Multicheck Callback
	 *
	 * Renders multiple checkboxes.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function multicheck_callback( $args ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();		

		if ( ! empty( $args['options'] ) ) {
			foreach( $args['options'] as $key => $option ):
				if( isset( $gfpdf_options[$args['id']][$key] ) ) { $enabled = $option; } else { $enabled = NULL; }
				if( empty($enabled) && isset( $gfpdf_form_settings[ $pid ][ $args['id']]) ) { $enabled = $option; } else { $enabled = NULL; }
				echo '<input name="gfpdf_settings[' . $args['id'] . '][' . $key . ']" id="gfpdf_settings[' . $args['id'] . '][' . $key . ']" class="gfpdf_settings_' . $args['id'] . '" type="checkbox" value="' . $option . '" ' . checked($option, $enabled, false) . '/>&nbsp;';
				echo '<label for="gfpdf_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br />';
			endforeach;
			echo '<span class="gf_settings_description">' . $args['desc'] . '</span>';
			
			if(isset($args['tooltip'])) {
				echo '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
			}			
		}
	}

	/**
	 * Radio Callback
	 *
	 * Renders radio boxes.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function radio_callback( $args ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();			

		/**
		 * Determine which key should be selected
		 */
		if(isset($gfpdf_options[ $args['id'] ]) && isset($args['options'][$gfpdf_options[ $args['id'] ]])) {
			$selected = $gfpdf_options[ $args['id'] ];
		} elseif(isset($gfpdf_form_settings[ $pid ][ $args['id']]) && isset($args['options'][ $gfpdf_form_settings[ $pid ][ $args['id']] ])) {
			$selected = $gfpdf_form_settings[ $pid ][ $args['id']];
		} elseif(isset( $args['std']) && isset($args['std'])) {
			$selected = $args['std'];
		}

		foreach ( $args['options'] as $key => $option ) :
			$checked = false;
			if($selected == $key) {
				$checked = true;
			}

			echo '<label for="gfpdf_settings[' . $args['id'] . '][' . $key . ']"><input name="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked(true, $checked, false) . '/>';
			echo $option . '</label> &nbsp;&nbsp;';
		endforeach;

		echo '<span class="gf_settings_description">' . $args['desc'] . '</span>';
		
		if(isset($args['tooltip'])) {
			echo '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
		}
	}

	/**
	 * Text Callback
	 *
	 * Renders text fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function text_callback( $args ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();	

		if ( isset( $gfpdf_options[ $args['id'] ] ) ) {
			$value = $gfpdf_options[ $args['id'] ];
		} elseif(empty($value) && isset( $gfpdf_form_settings[ $pid ][ $args['id']]) ) {
			$value = $gfpdf_form_settings[ $pid ][ $args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$required = '';
		if(isset($args['required']) && $args['required'] === true) {
			$required = 'required';
		}

		$class = '';
		if(isset($args['inputClass'])) {
			$class = $args['inputClass'];
		}		

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text '. $class .'" id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '" '. $required .' />';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
		}

		echo $html;
	}

	/**
	 * Number Callback
	 *
	 * Renders number fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function number_callback( $args ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();	

	    if ( isset( $gfpdf_options[ $args['id'] ] ) ) {
			$value = $gfpdf_options[ $args['id'] ];
	    } elseif(empty($value) && isset( $gfpdf_form_settings[ $pid ][ $args['id']]) ) {
	    	$value = $gfpdf_form_settings[ $pid ][ $args['id']];
	    } else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$max  = isset( $args['max'] ) ? $args['max'] : 999999;
		$min  = isset( $args['min'] ) ? $args['min'] : 0;
		$step = isset( $args['step'] ) ? $args['step'] : 1;

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/> ' . $args['desc2'];
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
		}

		echo $html;
	}

	/**
	 * Textarea Callback
	 *
	 * Renders textarea fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function textarea_callback( $args ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();	

		if ( isset( $gfpdf_options[ $args['id'] ] ) ) {
			$value = $gfpdf_options[ $args['id'] ];
		} elseif(empty($value) && isset( $gfpdf_form_settings[ $pid ][ $args['id']]) ) {
			$value = $gfpdf_form_settings[ $pid ][ $args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$html = '<textarea class="large-text" cols="50" rows="5" id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
		}

		echo $html;
	}

	/**
	 * Password Callback
	 *
	 * Renders password fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function password_callback( $args ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();	

		if ( isset( $gfpdf_options[ $args['id'] ] ) ) {
			$value = $gfpdf_options[ $args['id'] ];
		} elseif(empty($value) && isset( $gfpdf_form_settings[ $pid ][ $args['id']]) ) {
			$value = $gfpdf_form_settings[ $pid ][ $args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="password" class="' . $size . '-text" id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
		}

		echo $html;
	}

	/**
	 * Missing Callback
	 *
	 * If a public static function is missing for settings callbacks alert the user.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	public static function missing_callback($args) {
		printf( __( 'The callback used for the <strong>%s</strong> setting is missing.', 'pdfextended' ), $args['id'] );
	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function select_callback($args) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();	


		
		if ( isset( $gfpdf_options[ $args['id'] ] ) ) {
			$value = $gfpdf_options[ $args['id'] ];
		} elseif(empty($value) && isset( $gfpdf_form_settings[ $pid ][ $args['id']]) ) {
			$value = $gfpdf_form_settings[ $pid ][ $args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$placeholder = '';
	    if ( isset( $args['placeholder'] ) ) {
	        $placeholder = $args['placeholder'];	   
	    }			

		$chosen = '';
		if ( isset( $args['chosen'] ) ) {
			$chosen = 'gfpdf-chosen';			
		}

		$class = '';
		if(isset($args['inputClass'])) {
			$class = $args['inputClass'];
		}

		$multiple = $multipleExt = '';
		if(isset($args['multiple'])) {
			$multiple    = 'multiple';
			$multipleExt = '[]';
		}

	    $html = '<select id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . ' '. $class .' ' . $chosen . '" name="gfpdf_settings[' . $args['id'] . ']' . $multipleExt .'" data-placeholder="' . $placeholder . '" '. $multiple .'>';
	    
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
				
				$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
			} else {
				$html .= '<optgroup label="' . esc_html($option) . '">';
				foreach($name as $op_value => $op_label) {
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
					
					$html .= '<option value="' . $op_value . '" ' . $selected . '>' . $op_label . '</option>';
				}
				$html .= '</optgroup>';
			}
		}

		$html .= '</select>';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
		}

		echo $html;
	}

	/**
	 * Color select Callback
	 *
	 * Renders color select fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function color_select_callback( $args ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();	

		if ( isset( $gfpdf_options[ $args['id'] ] ) ) {
			$value = $gfpdf_options[ $args['id'] ];
		} elseif(empty($value) && isset( $gfpdf_form_settings[ $pid ][ $args['id']]) ) {
			$value = $gfpdf_form_settings[ $pid ][ $args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$html = '<select id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']"/>';

		foreach ( $args['options'] as $option => $color ) :
			$selected = selected( $option, $value, false );
			$html .= '<option value="' . $option . '" ' . $selected . '>' . $color['label'] . '</option>';
		endforeach;

		$html .= '</select>';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
		}

		echo $html;
	}

	/**
	 * Rich Editor Callback
	 *
	 * Renders rich editor fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @global $wp_version WordPress Version
	 */
	public static function rich_editor_callback( $args ) {
		global $gfpdf, $wp_version;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();			

		if ( isset( $gfpdf_options[ $args['id'] ] ) ) {
			$value = $gfpdf_options[ $args['id'] ];

			if( empty( $args['allow_blank'] ) && empty( $value ) ) {
				$value = isset( $args['std'] ) ? $args['std'] : '';
			}
		} elseif(empty($value) && isset( $gfpdf_form_settings[ $pid ][ $args['id']]) ) {
			$value = $gfpdf_form_settings[ $pid ][ $args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$rows = isset( $args['size'] ) ? $args['size'] : 20;

		if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
			ob_start();
			wp_editor( stripslashes( $value ), 'gfpdf_settings_' . $args['id'], array( 'textarea_name' => 'gfpdf_settings[' . $args['id'] . ']', 'textarea_rows' => $rows ) );
			$html = ob_get_clean();
		} else {
			$html = '<textarea class="large-text" rows="10" class="gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		}

		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
		}

		echo $html;
	}

	/**
	 * Upload Callback
	 *
	 * Renders upload fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function upload_callback( $args ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();			

		if ( isset( $gfpdf_options[ $args['id'] ] ) ) {
			$value = $gfpdf_options[$args['id']];
		} elseif(empty($value) && isset( $gfpdf_form_settings[ $pid ][ $args['id']]) ) {
			$value = $gfpdf_form_settings[ $pid ][ $args['id']];
		} else {
			$value = isset($args['std']) ? $args['std'] : '';
		}

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text" class="gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<span>&nbsp;<input type="button" class="gfpdf_settings_upload_button button-secondary" value="' . __( 'Upload File', 'pdfextended' ) . '"/></span>';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
		}

		echo $html;
	}


	/**
	 * Color picker Callback
	 *
	 * Renders color picker fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdf Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function color_callback( $args ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();	

		if ( isset( $gfpdf_options[ $args['id'] ] ) ) {
			$value = $gfpdf_options[ $args['id'] ];
		} elseif(empty($value) && isset( $gfpdf_form_settings[ $pid ][ $args['id']]) ) {
			$value = $gfpdf_form_settings[ $pid ][ $args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$default = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="gfpdf-color-picker" class="gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label></span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
		}

		echo $html;
	}

	/**
	 * Descriptive text callback.
	 *
	 * Renders descriptive text onto the settings field.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	public static function button_callback( $args ) {
		global $gfpdf;

		$tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'general';
		$nonce = wp_create_nonce('gfpdf_settings[' . $args['id'] . ']"');

		$link = esc_url( add_query_arg( array(
			'tab'    => $tab,
			'action' => $args['options'],
			'_nonce' => $nonce,
		), $gfpdf->data->settings_url));

		$html = '<a  href="' . $link .'" id="gfpdf_settings[' . $args['id'] . ']" class="button gfpdf-button">' . $args['std'] .'</a>';
		$html .= '<span class="gf_settings_description">'  . $args['desc'] . '</span>';

		if(isset($args['tooltip'])) {
			$html .= '<span class="gf_hidden_tooltip" style="display: none;">' . $args['tooltip'] . '</span>';
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
	public static function conditional_logic_callback( $args ) {
		$args['idOverride'] = $args['id'] . '_conditional_logic';

		self::checkbox_callback($args);
		
		$html = '<div id="'. $args['id'] .'_conditional_logic_container" class="gfpdf_conditional_logic">
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
	public static function hidden_callback( $args ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();	

		if ( isset( $gfpdf_options[ $args['id'] ] ) ) {
			$value = $gfpdf_options[ $args['id'] ];
		} elseif(empty($value) && isset( $gfpdf_form_settings[ $pid ][ $args['id']]) ) {
			$value = $gfpdf_form_settings[ $pid ][ $args['id']];
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		$class = '';
		if(isset($args['inputClass'])) {
			$class = $args['inputClass'];
		}		

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
	public static function paper_size_callback( $args ) {
		global $gfpdf;
		$gfpdf_options = $gfpdf->data->settings;

		/* add GF settings */
		$pid = (rgget('pid')) ? rgget('pid') : rgpost('gform_pdf_id');
		$gfpdf_form_settings = self::get_form_settings();	

		if ( isset( $gfpdf_options[ $args['id'] ] ) ) {
			$value = $gfpdf_options[ $args['id'] ];

		} elseif(empty($value) && isset( $gfpdf_form_settings[ $pid ][ $args['id']]) ) {
			$value = $gfpdf_form_settings[ $pid ][ $args['id']];
		} else {
			$value = (isset($args['std']) && is_array($args['std']) ) ? $args['std'] : array('', '', 'mm');
		}

		$placeholder = '';
	    if ( isset( $args['placeholder'] ) ) {
	        $placeholder = $args['placeholder'];	   
	    }			

		$chosen = '';
		if ( isset( $args['chosen'] ) ) {
			$chosen = 'gfpdf-chosen';			
		}	

		$class = '';
		if(isset($args['inputClass'])) {
			$class = $args['inputClass'];
		}				

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';

		$html = '<input type="number" class="'. $size .'-text gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . ']_width" min="1" name="gfpdf_settings[' . $args['id'] . '][]" value="' . esc_attr( stripslashes( $value[0] ) ) . '" required /> ' . __('Width', 'pdfextended');
		$html .= ' <input type="number" class="'. $size .'-text gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . ']_height" min="1" name="gfpdf_settings[' . $args['id'] . '][]" value="' . esc_attr( stripslashes( $value[1] ) ) . '" required /> ' . __('Height', 'pdfextended');

		$measurement = apply_filters( 'gfpdf_paper_size_dimensions', array(
			'millimeters' => __('mm', 'pdfextended'),
			'inches' => __('inches', 'pdfextended'),
		));

		$html .= '&nbsp; — &nbsp; <select id="gfpdf_settings[' . $args['id'] . ']_measurement" style="width: 75px" class="gfpdf_settings_' . $args['id'] . ' '. $class .' ' . $chosen . '" name="gfpdf_settings[' . $args['id'] . '][]" data-placeholder="' . $placeholder . '">';		

		$measure_value = esc_attr( stripslashes( $value[2] ) );
		foreach($measurement as $key => $val) {
			$selected = ($measure_value === $key) ? 'selected="selected"' : '';
			$html .= '<option value="'. $key .'" '. $selected .'>' . $val . '</option>';
		}

		$html .= '</select> ';

		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label></span>';

		echo $html;		
	}

	/**
	 * Descriptive text callback.
	 *
	 * Renders descriptive text onto the settings field.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	public static function descriptive_text_callback( $args ) {
		echo esc_html( $args['desc'] );
	}

	/**
	 * Hook Callback
	 *
	 * Adds a do_action() hook in place of the field
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @return void
	 */
	public static function hook_callback( $args ) {
		do_action( 'gfpdf_' . $args['id'], $args );
	}

}