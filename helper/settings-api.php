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
 * Pulled straight from the EDD register-settings.php file (props to Pippin and team)
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
	 * Updates an edd setting value in both the db and the global variable.
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
	 * Removes an edd setting value in both the db and the global variable.
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

		print_r(get_option( 'gfpdf_settings' )); 

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
						'name'        => isset( $option['name'] )        ? $option['name']    : null,
						'size'        => isset( $option['size'] )        ? $option['size']    : null,
						'options'     => isset( $option['options'] )     ? $option['options'] : '',
						'std'         => isset( $option['std'] )         ? $option['std']     : '',
						'min'         => isset( $option['min'] )         ? $option['min']     : null,
						'max'         => isset( $option['max'] )         ? $option['max']     : null,
	                    'step'        => isset( $option['step'] )        ? $option['step']    : null,
	                    'chosen'      => isset( $option['chosen'] )      ? $option['chosen']  : null,
	                    'placeholder' => isset( $option['placeholder'] ) ? $option['placeholder'] : null,
	                    'allow_blank' => isset( $option['allow_blank'] ) ? $option['allow_blank'] : true
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

		/**
		 * 'Whitelisted' EDD settings, filters are provided for each settings
		 * section to allow extensions and other plugins to add their own settings
		 */
		$gfpdf_settings = array(
			/** General Settings */
			'general' => apply_filters( 'gfpdf_settings_general',
				array(
					'test_mode' => array(
						'id' => 'test_mode',
						'name' => __( 'Test Mode', 'edd' ),
						'desc' => __( 'While in test mode no live transactions are processed. To fully use test mode, you must have a sandbox (test) account for the payment gateway you are testing.', 'edd' ),
						'type' => 'checkbox'
					),

					'currency_position' => array(
						'id' => 'currency_position',
						'name' => __( 'Currency Position', 'edd' ),
						'desc' => __( 'Choose the location of the currency sign.', 'edd' ),
						'type' => 'select',
						'options' => array(
							'before' => __( 'Before - $10', 'edd' ),
							'after' => __( 'After - 10$', 'edd' )
						)
					),
					'thousands_separator' => array(
						'id' => 'thousands_separator',
						'name' => __( 'Thousands Separator', 'edd' ),
						'desc' => __( 'The symbol (usually , or .) to separate thousands', 'edd' ),
						'type' => 'text',
						'size' => 'small',
						'std' => ','
					),
					'decimal_separator' => array(
						'id' => 'decimal_separator',
						'name' => __( 'Decimal Separator', 'edd' ),
						'desc' => __( 'The symbol (usually , or .) to separate decimal points', 'edd' ),
						'type' => 'text',
						'size' => 'small',
						'std' => '.'
					),
					'api_settings' => array(
						'id' => 'api_settings',
						'name' => '<strong>' . __( 'API Settings', 'edd' ) . '</strong>',
						'desc' => '',
						'type' => 'header'
					),
					'api_allow_user_keys' => array(
						'id' => 'api_allow_user_keys',
						'name' => __( 'Allow User Keys', 'edd' ),
						'desc' => __( 'Check this box to allow all users to generate API keys. Users with the \'manage_shop_settings\' capability are always allowed to generate keys.', 'edd' ),
						'type' => 'checkbox'
					),
					'tracking_settings' => array(
						'id' => 'tracking_settings',
						'name' => '<strong>' . __( 'Tracking Settings', 'edd' ) . '</strong>',
						'desc' => '',
						'type' => 'header'
					),
					'allow_tracking' => array(
						'id' => 'allow_tracking',
						'name' => __( 'Allow Usage Tracking?', 'edd' ),
						'desc' => __( 'Allow Easy Digital Downloads to anonymously track how this plugin is used and help us make the plugin better. Opt-in and receive a 20% discount code for any purchase from the <a href="https://easydigitaldownloads.com/extensions" target="_blank">Easy Digital Downloads store</a>. Your discount code will be emailed to you.', 'edd' ),
						'type' => 'checkbox'
					),
					'uninstall_on_delete' => array(
						'id' => 'uninstall_on_delete',
						'name' => __( 'Remove Data on Uninstall?', 'edd' ),
						'desc' => __( 'Check this box if you would like EDD to completely remove all of its data when the plugin is deleted.', 'edd' ),
						'type' => 'checkbox'
					)
				)
			),

			
			/** Extension Settings */
			'extensions' => apply_filters('gfpdf_settings_extensions',
				array()
			),
			'licenses' => apply_filters('gfpdf_settings_licenses',
				array()
			),
		);

		return apply_filters( 'gfpdf_registered_settings', $gfpdf_settings );
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
	 * @global $gfpdfe_data Array of all the EDD Options
	 * @return void
	 */
	public static function checkbox_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		$checked = isset( $gfpdf_options[ $args['id'] ] ) ? checked( 1, $gfpdf_options[ $args['id'] ], false ) : '';
		$html = '<input type="checkbox" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
		$html .= '<label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Multicheck Callback
	 *
	 * Renders multiple checkboxes.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdfe_data Array of all the EDD Options
	 * @return void
	 */
	public static function multicheck_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		if ( ! empty( $args['options'] ) ) {
			foreach( $args['options'] as $key => $option ):
				if( isset( $gfpdf_options[$args['id']][$key] ) ) { $enabled = $option; } else { $enabled = NULL; }
				echo '<input name="gfpdf_settings[' . $args['id'] . '][' . $key . ']" id="gfpdf_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="' . $option . '" ' . checked($option, $enabled, false) . '/>&nbsp;';
				echo '<label for="gfpdf_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
			endforeach;
			echo '<p class="description">' . $args['desc'] . '</p>';
		}
	}

	/**
	 * Radio Callback
	 *
	 * Renders radio boxes.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdfe_data Array of all the EDD Options
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

			echo '<input name="gfpdf_settings[' . $args['id'] . ']"" id="gfpdf_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked(true, $checked, false) . '/>&nbsp;';
			echo '<label for="gfpdf_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
		endforeach;

		echo '<p class="description">' . $args['desc'] . '</p>';
	}

	/**
	 * Text Callback
	 *
	 * Renders text fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdfe_data Array of all the EDD Options
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
		$html = '<input type="text" class="' . $size . '-text" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Number Callback
	 *
	 * Renders number fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdfe_data Array of all the EDD Options
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
		$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Textarea Callback
	 *
	 * Renders textarea fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdfe_data Array of all the EDD Options
	 * @return void
	 */
	public static function textarea_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		if ( isset( $gfpdf_options[ $args['id'] ] ) )
			$value = $gfpdf_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$html = '<textarea class="large-text" cols="50" rows="5" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		$html .= '<label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Password Callback
	 *
	 * Renders password fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdfe_data Array of all the EDD Options
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
		$html = '<input type="password" class="' . $size . '-text" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
		$html .= '<label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

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
		printf( __( 'The callback public static function used for the <strong>%s</strong> setting is missing.', 'edd' ), $args['id'] );
	}

	/**
	 * Select Callback
	 *
	 * Renders select fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdfe_data Array of all the EDD Options
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
			$chosen = 'class="edd-chosen"';
		else
			$chosen = '';

	    $html = '<select id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" ' . $chosen . 'data-placeholder="' . $placeholder . '" />';

		foreach ( $args['options'] as $option => $name ) :
			$selected = selected( $option, $value, false );
			$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
		endforeach;

		$html .= '</select>';
		$html .= '<label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Color select Callback
	 *
	 * Renders color select fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdfe_data Array of all the EDD Options
	 * @return void
	 */
	public static function color_select_callback( $args ) {
		global $gfpdfe_data;
		$gfpdf_options = $gfpdfe_data->settings;

		if ( isset( $gfpdf_options[ $args['id'] ] ) )
			$value = $gfpdf_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$html = '<select id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']"/>';

		foreach ( $args['options'] as $option => $color ) :
			$selected = selected( $option, $value, false );
			$html .= '<option value="' . $option . '" ' . $selected . '>' . $color['label'] . '</option>';
		endforeach;

		$html .= '</select>';
		$html .= '<label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Rich Editor Callback
	 *
	 * Renders rich editor fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdfe_data Array of all the EDD Options
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
			$html = '<textarea class="large-text" rows="10" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		}

		$html .= '<br/><label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}

	/**
	 * Upload Callback
	 *
	 * Renders upload fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdfe_data Array of all the EDD Options
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
		$html = '<input type="text" class="' . $size . '-text" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
		$html .= '<span>&nbsp;<input type="button" class="gfpdf_settings_upload_button button-secondary" value="' . __( 'Upload File', 'edd' ) . '"/></span>';
		$html .= '<label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}


	/**
	 * Color picker Callback
	 *
	 * Renders color picker fields.
	 *
	 * @since 3.8
	 * @param array $args Arguments passed by the setting
	 * @global $gfpdfe_data Array of all the EDD Options
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
		$html = '<input type="text" class="edd-color-picker" id="gfpdf_settings[' . $args['id'] . ']" name="gfpdf_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
		$html .= '<label for="gfpdf_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

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