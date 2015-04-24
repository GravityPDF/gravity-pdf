<?php

/**
 * Plugin: Gravity PDF
 * File: helper/settings-api.php
 *
 * The helper public static function that handles the display and validation of core settings
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
class GFPDF_Settings_API {
	
	/**
	 * Get an option
	 *
	 * Looks to see if the specified setting exists, returns default if not
	 *
	 * @since 3.8
	 * @return mixed
	 */
	public static function get_option( $key = '', $default = false ) {
		global $gfpdfe_data;
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
			global $gfpdfe_data;
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
			global $gfpdfe_data;
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
		$settings = (is_array(get_option( 'gfpdf_settings' ))) ? get_option( 'gfpdf_settings' ) : array();
		return apply_filters( 'gfpdf_get_settings', $settings );
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
					method_exists(  'GFPDF_Settings_API', $option['type'] . '_callback' ) ? array('GFPDF_Settings_API', $option['type'] . '_callback') : array('GFPDF_Settings_API', 'missing_callback'),
					'gfpdf_settings_' . $tab,
					'gfpdf_settings_' . $tab,
					array(
						'section'     => $tab,
						'id'          => isset( $option['id'] )          ? $option['id']      : null,
						'desc'        => ! empty( $option['desc'] )      ? $option['desc']    : '',
						'desc2'       => ! empty( $option['desc2'] )      ? $option['desc2']    : '',
						'name'        => isset( $option['name'] )        ? $option['name']    : null,
						'size'        => isset( $option['size'] )        ? $option['size']    : null,
						'options'     => isset( $option['options'] )     ? $option['options'] : '',
						'std'         => isset( $option['std'] )         ? $option['std']     : '',
						'min'         => isset( $option['min'] )         ? $option['min']     : null,
						'max'         => isset( $option['max'] )         ? $option['max']     : null,
						'step'        => isset( $option['step'] )        ? $option['step']    : null,
						'chosen'      => isset( $option['chosen'] )      ? $option['chosen']  : null,
						'placeholder' => isset( $option['placeholder'] ) ? $option['placeholder'] : null,
						'allow_blank' => isset( $option['allow_blank'] ) ? $option['allow_blank'] : true,	                    
						'tooltip'     => isset( $option['tooltip'] ) ? $option['tooltip'] : null,	 
					)
				);
			}

		}

		/* Creates our settings in the options table */
		register_setting( 'gfpdf_settings', 'gfpdf_settings', array('GFPDF_Settings_API', 'settings_sanitize') );

		/* register our santize functions */
		add_filter( 'gfpdf_settings_sanitize_text', array('GFPDF_Settings_API', 'sanitize_text_field') );		
	}

	/**
	 * Retrieve the array of plugin settings
	 *
	 * @since 3.8
	 * @return array
	*/
	public static function get_registered_settings() {

		global $gfpdfe_data;
		/**
		 * 'Whitelisted' Gravity PDF settings, filters are provided for each settings
		 * section to allow extensions and other plugins to add their own settings
		 */
		$gfpdf_settings = array(
			/** General Settings */
			'general' => apply_filters( 'gfpdf_settings_general',
				array(
					'pdf_size' => array(
						'id'      => 'pdf_size',
						'name'    => __('Default Paper Size', 'pdfextended'),
						'desc'    => __('Set the default paper size used when generating PDFs. This setting is overridden if you set the PDF size when configuring individual PDFs.', 'pdfextended'),
						'type'    => 'select',
						'options' => self::get_paper_size(),
					),

					'cleanup' => array(
						'id'      => 'cleanup',
						'name'    => __('Regularly Cleanup PDFs', 'pdfextended'),
						'desc'    => __('When enabled, the PDF will be removed from your file system when it is no longer needed. Enable to save disk space.', 'pdfextended'),
						'type'    => 'radio',
						'options' => array(
							'Yes' => 'Yes',
							'No'  => 'No'
						),
						'std'     => 'Yes',
						'tooltip' => '<h6>' . __('Cleanup PDFs', 'pdfextended') . '</h6>' . __('If you are using the "notification" or "save" configuration option, by default Gravity PDF will store copies of your PDF on your server. If you have limited disk space you should enable this option. Note: You can regenerate your PDFs at any time.', 'pdfextended'),
					),

					'default_action' => array(
						'id'      => 'default_action',
						'name'    => __('Entry View', 'pdfextended'),
						'desc'    => sprintf(__('Select the default action used when accessing a PDF from the %sGravity Forms entries list%s page.'), '<a href="'. admin_url('admin.php?page=gf_entries') . '">', '</a>'),
						'type'    => 'radio',
						'options' => array(
							'View'     => 'View', 
							'Download' => 'Download',
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
							'Yes' => 'Yes',
							'No'  => 'No'
						),
						'std'   => 'No',
						'tooltip' => '<h6>' . __('Restrict Access to Administrators Only', 'pdfextended') . '</h6>' . __("Enable this option if you don't want users accessing the generated PDFs. This is userful if the documents are for internal use, or security is a major concern.", 'pdfextended'),
					),

					'limit_to_users' => array(
						'id'    => 'limit_to_user',
						'name'  => __('Disable Logged Out User Access', 'pdfextended'),
						'desc'  => __('Restrict PDF access to the logged in <em>owner</em> of the Gravity Form entry, as well as users with the <em>"Gravity Forms View Entries"</em> privilege.', 'pdfextended'),						
						'type'  => 'radio',						
						'options' => array(
							'Yes' => 'Yes',
							'No'  => 'No'
						),
						'std'   => 'No',
						'tooltip' => '<h6>' . __('Disable Logged Out Users Access', 'pdfextended') . '</h6>' . __("Enable this option if your Gravity Forms are restricted to logged in users only. The logged in owner will still be able to view their generated PDF, as well as site administrators.", 'pdfextended'),
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
					'install_fonts' => array(
						'id'    => 'install_fonts',
						'name'  => __('Fonts', 'pdfextended'),
						'desc'  => sprintf(__("Use this action to install new fonts found in the %s/fonts/%s directory. %sSee docs for full installation guide%s.", 'pdfextended'), '<code>', '</code>', '<a href="https://developer.gravitypdf.com/documentation/language-support/#install-custom-fonts">', '</a>'),						
						'type'  => 'button',						
						'std'   => __('Install Fonts', 'pdfextended'),
						'options' => 'install_fonts',
						'tooltip' => '<h6>' . __('Install Fonts', 'pdfextended') . '</h6>' . sprintf(__("Custom fonts can be installed and used in your PDFs. Currently only %s.ttf%s font files are supported. Once installed, fonts can be set in your custom PDF templates using CSS's %sfont-family%s declaration.", 'pdfextended'), '<code>', '</code>', '<code>', '</code>'),
					),

					'cleanup' => array(
						'id'    => 'cleanup',
						'name'  => __('Cleanup PDFs', 'pdfextended'),
						'desc'  => 'Remove all generated PDFs from your file system which are no longer needed. Run to save disk space.',						
						'type'  => 'button',						
						'std'   => __('Start Cleanup', 'pdfextended'),
						'options' => 'cleanup',
						'tooltip' => '<h6>' . __('Cleanup PDFs', 'pdfextended') . '</h6>' . sprintf(__("Any PDFs generated by Gravity PDF which are more than one hour old will be removed from your file system. You should run this when you enable the %sRegularly Cleanup PDFs%s setting.", 'pdfextended'), '<em>', '</em>'),
					),							

					'reinstall' => array(
						'id'    => 'reinstall',
						'name'  => __('Reinstall Gravity PDF', 'pdfextended'),
						'desc'  => __("Is Gravity PDF not functioning correctly? Try reinstall the software.", 'pdfextended'),						
						'type'  => 'button',						
						'std'   => __('Start Reinstall', 'pdfextended'),
						'options' => 'reinstall',
						'tooltip' => '<h6>' . __('Reinstall Gravity PDF', 'pdfextended') . '</h6>' . __('If Gravity PDF detects a configuration problem you might need to reinstall the software. During the reinstall process the PDF templates that ship with the software will be overridden (a backup copy will be created just in case).', 'pdfextended'),
					),						
				)
			),

		);

		return apply_filters( 'gfpdf_registered_settings', $gfpdf_settings );
	}

	public static function get_paper_size() {
		return array(
			'Common Sizes' => array(
				'A4'     => __('A4 (210 x 297mm)', 'pdfextended'),
				'letter' => __('Letter (8.5 x 11in)', 'pdfextended'),
				'legal'  => __('Legal (8.5 x 14in)', 'pdfextended'),
				'ledger' => __('Ledger / Tabloid (11 x 17in)', 'pdfextended'),
				'executive' => __('Executive (7 x 10in)', 'pdfextended'),
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
		); 		
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

		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

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
				$input[$key] = apply_filters( 'gfpdf_settings_sanitize_' . $type, $value, $key );
			}

			// General filter
			$input[$key] = apply_filters( 'gfpdf_settings_sanitize', $input[$key], $key );
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

		// Merge our new settings with the existing
		$output = array_merge( $gfpdf_options, $input );

		add_settings_error( 'gfpdf-notices', '', __( 'Settings updated.', 'pdfextended' ), 'updated' );

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
	 * Checkbox Callback
	 *
	 * Renders checkboxes.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdfe_data Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function checkbox_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		$checked = isset( $gfpdf_options[ $args['id'] ] ) ? checked( 1, $gfpdf_options[ $args['id'] ], false ) : '';
		$html = '<input type="checkbox" id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
		$html .= '<span class="gf_settings_description"><label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label></span>';
		
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
	 * @global $gfpdfe_data Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function multicheck_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		if ( ! empty( $args['options'] ) ) {
			foreach( $args['options'] as $key => $option ):
				if( isset( $gfpdf_options[$args['id']][$key] ) ) { $enabled = $option; } else { $enabled = NULL; }
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
	 * @global $gfpdfe_data Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function radio_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		foreach ( $args['options'] as $key => $option ) :
			$checked = false;

			if ( isset( $gfpdf_options[ $args['id'] ] ) && $gfpdf_options[ $args['id'] ] == $key )
				$checked = true;
			elseif( isset( $args['std'] ) && $args['std'] == $key && ! isset( $gfpdf_options[ $args['id'] ] ) )
				$checked = true;

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
	 * @global $gfpdfe_data Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function text_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		if ( isset( $gfpdf_options[ $args['id'] ] ) )
			$value = $gfpdf_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text" id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
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
	 * @global $gfpdfe_data Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function number_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

	    if ( isset( $gfpdf_options[ $args['id'] ] ) )
			$value = $gfpdf_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$max  = isset( $args['max'] ) ? $args['max'] : 999999;
		$min  = isset( $args['min'] ) ? $args['min'] : 0;
		$step = isset( $args['step'] ) ? $args['step'] : 1;

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" class="gfpdf_settings_' . $args['id'] . '" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/> ' . $args['desc2'];
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
	 * @global $gfpdfe_data Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function textarea_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		if ( isset( $gfpdf_options[ $args['id'] ] ) )
			$value = $gfpdf_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

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
	 * @global $gfpdfe_data Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function password_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		if ( isset( $gfpdf_options[ $args['id'] ] ) )
			$value = $gfpdf_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

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
	 * @global $gfpdfe_data Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function select_callback($args) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		if ( isset( $gfpdf_options[ $args['id'] ] ) )
			$value = $gfpdf_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

	    if ( isset( $args['placeholder'] ) )
	        $placeholder = $args['placeholder'];
	    else
			$placeholder = '';

		if ( isset( $args['chosen'] ) )
			$chosen = 'class="gfpdf-chosen"';
		else
			$chosen = '';

	    $html = '<select id="gfpdf_settings[' . $args['id'] . ']" class="gfpdf_settings_' . $args['id'] . '" name="gfpdf_settings[' . $args['id'] . ']" ' . $chosen . 'data-placeholder="' . $placeholder . '">';
	    
		foreach ( $args['options'] as $option => $name ) {
			if(!is_array($name)) {
				$selected = selected( $option, $value, false );
				$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
			} else {
				$html .= '<optgroup label="' . esc_html($option) . '">';
				foreach($name as $op_value => $op_label) {
					$selected = selected( $op_value, $value, false );
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
	 * @global $gfpdfe_data Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function color_select_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		if ( isset( $gfpdf_options[ $args['id'] ] ) )
			$value = $gfpdf_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

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
	 * @global $gfpdfe_data Array of all the Gravity PDF Options
	 * @global $wp_version WordPress Version
	 */
	public static function rich_editor_callback( $args ) {
		global $gfpdfe_data, $wp_version;
		$gfpdf_options = $gfpdfe_data->settings;

		if ( isset( $gfpdf_options[ $args['id'] ] ) ) {
			$value = $gfpdf_options[ $args['id'] ];

			if( empty( $args['allow_blank'] ) && empty( $value ) ) {
				$value = isset( $args['std'] ) ? $args['std'] : '';
			}
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
	 * @global $gfpdfe_data Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function upload_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		if ( isset( $gfpdf_options[ $args['id'] ] ) )
			$value = $gfpdf_options[$args['id']];
		else
			$value = isset($args['std']) ? $args['std'] : '';

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
	 * @global $gfpdfe_data Array of all the Gravity PDF Options
	 * @return void
	 */
	public static function color_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		if ( isset( $gfpdf_options[ $args['id'] ] ) )
			$value = $gfpdf_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

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
		global $gfpdfe_data;

		$tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'general';
		$nonce = wp_create_nonce('gfpdf_settings[' . $args['id'] . ']"');

		$link = esc_url( add_query_arg( array(
			'tab'    => $tab,
			'action' => $args['options'],
			'_nonce' => $nonce,
		), $gfpdfe_data->settings_url));

		$html = '<a  href="' . $link .'" id="gfpdf_settings[' . $args['id'] . ']" class="button gfpdf-button">' . $args['std'] .'</a>';
		$html .= '<span class="gf_settings_description">'  . $args['desc'] . '</span>';

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