<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_PDF_List_Table;
use GFPDF\Helper\Helper_Interface_Config;

use WP_Error;
use _WP_Editors;

/**
 * Settings Model
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2015, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.0
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

/**
 * Model_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class Model_Form_Settings extends Helper_Abstract_Model {

	/**
	 * Add the form settings tab.
	 *
	 * Override this function to add the tab conditionally.
	 *
	 * @param $tabs array The list of existing tags
	 * @param $form_id integer The current form ID
	 * @return Array modified list of $tabs
	 * @since 4.0
	 */
	public function add_form_settings_menu( $tabs, $form_id ) {
		global $gfpdf;
		$tabs[] = array( 'name' => $gfpdf->data->slug, 'label' => $gfpdf->data->short_title, 'query' => array( 'pid' => null ) );
		return $tabs;
	}

	/**
	 * Setup the PDF Settings List View Logic
	 * @param  Integer $form_id The Gravity Form ID
	 * @return void
	 * @since 4.0
	 */
	public function process_list_view( $form_id ) {
		global $gfpdf;

		/* prevent unauthorized access */
		if ( ! $gfpdf->form->has_capability( 'gravityforms_edit_settings' ) ) {
			wp_die( __( 'You do not have permission to access this page', 'gravitypdf' ) );
		}

		global $gfpdf;
		$controller = $this->getController();

		/* get the form object */
		$form = $gfpdf->form->get_form( $form_id );

		/* load our list table */
		$pdf_table = new Helper_PDF_List_Table( $form );
		$pdf_table->prepare_items();

		/* pass to view */
		$controller->view->list(array(
			'title'       => $gfpdf->data->title,
			'add_new_url' => $add_new_url = add_query_arg( array( 'pid' => 0 ) ),
			'list_items'  => $pdf_table,
		));
	}

	/**
	 * Setup the PDF Settings Add/Edit View Logic
	 * @param  Integer $form_id The Gravity Form ID
	 * @param  Integer $pdf_id The PDF configuration ID
	 * @return void
	 * @since 4.0
	 */
	public function show_edit_view( $form_id, $pdf_id ) {
		global $gfpdf;

		/* prevent unauthorized access */
		if ( ! $gfpdf->form->has_capability( 'gravityforms_edit_settings' ) ) {
			wp_die( __( 'You do not have permission to access this page', 'gravitypdf' ) );
		}

		$controller = $this->getController();

		/* get the form object */
		$form = $gfpdf->form->get_form( $form_id );

		/* parse input and get required information */
		if ( ! $pdf_id ) {
			if ( rgpost( 'gform_pdf_id' ) ) {
				$pdf_id = rgpost( 'gform_pdf_id' );
			} else {
				$pdf_id = uniqid();
			}
		}

		/* prepare our data */
		$label = (rgget( 'pid' )) ? __( 'Update PDF', 'gravitypdf' ) : __( 'Add PDF', 'gravitypdf' );

		/* re-register our Gravity Forms Notifications */
		$this->register_notifications( $form['notifications'] );

		/* re-register all our settings to show form-specific options */
		$gfpdf->options->register_settings( $gfpdf->options->get_registered_fields() );

		/* pass to view */
		$controller->view->add_edit(array(
			'pdf_id'           => $pdf_id,
			'title'            => $label,
			'button_label'     => $label,
			'form'             => $form,
			'pdf'              => $this->get_pdf( $form_id, $pdf_id ),
			'wp_editor_loaded' => class_exists( '_WP_Editors' ),
		));
	}

	/**
	 * Get Form Settings
	 *
	 * Retrieves all form PDF settings
	 *
	 * @since 4.0
	 * @return Array/Object GFPDF settings or WP_Error
	 */
	public function get_settings( $form_id ) {
		global $gfpdf;

		if ( ! isset($gfpdf->data->form_settings) ) {
			$gfpdf->data->form_settings = array();
		}

		$form_id = (int) $form_id;
		if ( (int) $form_id === 0 ) {
			return new WP_Error( 'invalid_id', __( 'You must pass in a valid form ID', 'gravitypdf' ) );
		}

		/* If we haven't pulled the form meta data from the database do so now */
		if ( ! isset($gfpdf->data->form_settings[$form_id]) ) {
			$form = $gfpdf->form->get_form( $form_id );

			if ( empty($form) ) {
				return new WP_Error( 'invalid_id', __( 'You must pass in a valid form ID', 'gravitypdf' ) );
			}

			$settings = (isset($form['gfpdf_form_settings'])) ? $form['gfpdf_form_settings'] : array();
			$gfpdf->data->form_settings[$form_id] = apply_filters( 'gfpdf_get_form_settings', $settings );

		}

		/* return the form meta data */
		return $gfpdf->data->form_settings[$form_id];
	}

	/**
	 * Get pdf config
	 *
	 * Looks to see if the specified setting exists, returns default if not
	 *
	 * @since 4.0
	 * @return mixed
	 */
	public function get_pdf( $form_id, $pdf_id ) {
		$gfpdf_options = $this->get_settings( $form_id );

		if ( ! is_wp_error( $gfpdf_options ) ) {
			$value         = ! empty( $gfpdf_options[ $pdf_id ] ) ? $gfpdf_options[ $pdf_id ] : new WP_Error( 'invalid_pdf_id', __( 'You must pass in a valid PDF ID', 'gravitypdf' ) );
			return apply_filters( 'gfpdf_pdf_config', apply_filters( 'gfpdf_pdf_config_' . $form_id, $value ) );
		}

		/* return WP_Error */
		return $gfpdf_options;
	}


	/**
	 * Create a new PDF configuration option for that form
	 * @param Integer $form_id The form ID
	 * @param array   $value   The settings array
	 * @return mixed
	 * @since 4.0
	 */
	public function add_pdf( $form_id, $value = array() ) {
		/* First let's grab the current settings */
		$options = $this->get_settings( $form_id );

		if ( ! is_wp_error( $options ) ) {
			/* check the ID, if any */
			$value['id']     = (isset($value['id'])) ? $value['id'] : uniqid();
			$value['active'] = (isset($value['active'])) ? $value['active'] : true;

			/* Let's let devs alter that value coming in */
			$value = apply_filters( 'gfpdf_form_add_pdf', $value, $form_id );
			$value = apply_filters( 'gfpdf_form_add_pdf_' . $form_id, $value, $form_id );

			$results = $this->update_pdf( $form_id, $value['id'], $value, true, false );

			if ( $results ) {
				/* return the ID if successful */
				return $value['id'];
			}
		}

		return false;
	}

	/**
	 * Update an pdf config
	 *
	 * Updates a Gravity PDF setting value in both the db and the global variable.
	 * Warning: Passing in an empty, false or null string value will remove
	 *          the key from the gfpdf_options array.
	 *
	 * @since 4.0
	 * @param integer         $form_id The Gravity Form ID
	 * @param string          $pdf_id The PDF Setting ID
	 * @param string|bool|int $value The value to set the key to
	 * @param array           $value The PDF settings array
	 * @param boolean         $filter Whether to apply the update filters or not
	 * @return boolean True if updated, false if not.
	 */
	public function update_pdf( $form_id, $pdf_id, $value = '', $update_db = true, $filters = true ) {
		global $gfpdf;

		if ( empty( $value ) || ! is_array( $value ) || sizeof( $value ) == 0 ) {
			$remove_option = $this->delete_pdf( $form_id, $pdf_id );
			return $remove_option;
		}

		/* First let's grab the current settings */
		$options = $this->get_settings( $form_id );

		if ( ! is_wp_error( $options ) ) {
			/* don't run when adding a new PDF */
			if ( $filters ) {
				/* Let's let devs alter that value coming in */
				$value = apply_filters( 'gfpdf_form_update_pdf', $value, $form_id, $pdf_id );
				$value = apply_filters( 'gfpdf_form_update_pdf_' . $form_id, $value, $form_id, $pdf_id );
			}

			/* Next let's try to update the value */
			$options[ $pdf_id ] = $value;

			/* get the up-to-date form object and merge in the results */
			$form = $gfpdf->form->get_form( $form_id );

			/* Update our GFPDF settings */
			$form['gfpdf_form_settings'] = $options;

			$did_update = false;
			if ( $update_db ) {
				/* update the database, if able */
				$did_update = $gfpdf->form->update_form( $form );
			}

			/* If it updated, let's update the global variable */
			if ( ! $update_db || $did_update !== false ) {
				global $gfpdf;
				$gfpdf->data->form_settings[$form_id] = $options;
			}

			/* true if successful, false if failed */
			return $did_update;
		}
		return false;
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
	public function delete_pdf( $form_id, $pdf_id ) {
		global $gfpdf;

		/* First let's grab the current settings */
		$options = $this->get_settings( $form_id );

		if ( ! is_wp_error( $options ) ) {

			/* Next let's try to update the value */
			if ( isset( $options[ $pdf_id ] ) ) {
				unset( $options[ $pdf_id ] );
			}

			/* get the form and merge in the results */
			$form = $gfpdf->form->get_form( $form_id );

			/* Update our GFPDF settings */
			$form['gfpdf_form_settings'] = $options;

			/* update the database, if able */
			$did_update = $gfpdf->form->update_form( $form );

			/* If it updated, let's update the global variable */
			if ( $did_update !== false ) {
				global $gfpdf;
				$gfpdf->data->form_settings[$form_id] = $options;
			}

			/* true if successful, false if failed */
			return $did_update;
		}
		return false;
	}

	/**
	 * Validate, Sanatize and Update PDF settings
	 * @param  Integer $form_id The Gravity Form ID
	 * @param  Integer $pdf_id The PDF configuration ID
	 * @return void
	 * @since 4.0
	 */
	public function process_submission( $form_id, $pdf_id ) {
		global $gfpdf;

		/* prevent unauthorized access */
		if ( ! $gfpdf->form->has_capability( 'gravityforms_edit_settings' ) ) {
			wp_die( __( 'You do not have permission to access this page', 'gravitypdf' ) );
		}

		/* Check Nonce is valid */
		if ( ! wp_verify_nonce( rgpost( 'gfpdf_save_pdf' ), 'gfpdf_save_pdf' ) ) {
			$gfpdf->notices->add_error( __( 'There was a problem saving your PDF settings. Please try again.', 'gravitypdf' ) );
			 return false;
		}

		/* Check if we have a new PDF ID */
		if ( empty($pdf_id) ) {
			$pdf_id = (rgpost( 'gform_pdf_id' )) ? rgpost( 'gform_pdf_id' ) : false;
		}

		$input = rgpost( 'gfpdf_settings' );

		/* check appropriate settings */
		if ( ! is_array( $input ) || ! $pdf_id ) {
			 $gfpdf->notices->add_error( __( 'There was a problem saving your PDF settings. Please try again.', 'gravitypdf' ) );
			 return false;
		}

		$sanitized = $this->settings_sanitize( $input );

		/* Update our GFPDF settings */
		$sanitized['id']     = $pdf_id;
		$sanitized['active'] = true;

		$this->update_pdf( $form_id, $pdf_id, $sanitized, false );

		/* Do validation */
		if ( empty($sanitized['name']) || empty($sanitized['filename']) ||
			($sanitized['pdf_size'] == 'custom' && ((int) $sanitized['custom_pdf_size'][0] === 0 || (int) $sanitized['custom_pdf_size'][1] === 0)) ) {

			$gfpdf->notices->add_error( __( 'PDF could not be saved. Please enter all required information below.', 'gravitypdf' ) );
			return false;
		}

		/* get the form and merge in the results */
		$form = $gfpdf->form->get_form( $form_id );

		/* Update our GFPDF settings */
		$form['gfpdf_form_settings'][$pdf_id] = $sanitized;

		/* Update database */
		$did_update = $gfpdf->form->update_form( $form );

		/* If it updated, let's update the global variable */
		if ( $did_update !== false ) {
			$gfpdf->notices->add_notice( sprintf( __( 'PDF saved successfully. %sBack to PDF list.%s', 'gravitypdf' ), '<a href="' . remove_query_arg( 'pid' ) . '">', '</a>' ) );
			return true;
		}

		$gfpdf->notices->add_error( __( 'There was a problem saving your PDF settings. Please try again.', 'gravitypdf' ) );
		return false;
	}

	/**
	 * Apply gfield_error class when validation fails, highlighting field blocks with problems
	 * @param  array $fields Array of fields to process
	 * @return array         Modified list of fields
	 * @since 4.0
	 */
	public function validation_error( $fields ) {
		global $gfpdf;

		/**
		 * Check if we actually need to do any validating
		 * Because of the way the Gravity Forms Settings page is processed we are hooking into the core
		 * "gfpdf_form_settings" filter which runs on both the GF Settings page and the Settings page
		 * We don't need to do any validation when not on the GF PDF Settings page
		 */
		if ( empty($_POST['gfpdf_save_pdf']) ) {
			return $fields;
		}

		/**
		 * Check we have a valid nonce, or throw an error
		 */
		if ( ! wp_verify_nonce( rgpost( 'gfpdf_save_pdf' ), 'gfpdf_save_pdf' ) ) {
			$gfpdf->notices->add_error( __( 'There was a problem saving your PDF settings. Please try again.', 'gravitypdf' ) );
			return false;
		}

		$input = rgpost( 'gfpdf_settings' );

		/* throw errors on required fields */
		foreach ( $fields as $key => &$field ) {
			if ( isset($field['required']) && $field['required'] === true ) {

				/* get field value */
				$value          = (isset($input[$field['id']])) ? $input[$field['id']] : '';

				/* set a class if it doesn't exist */
				$field['class'] = (isset($field['class'])) ? $field['class'] : '';

				/* if the value is an array ensure all items have values */
				if ( is_array( $value ) ) {
					$size = sizeof( $value );
					if ( sizeof( array_filter( $value ) ) !== $size ) {
						$field['class'] .= ' gfield_error' ;
					}
				} else {
					/* if string, sanitize and add error if appropriate */
					$value = apply_filters( 'gfpdf_form_settings_sanitize_text', $value, $key );
					if ( empty($value) ) {
						$field['class'] .= ' gfield_error' ;
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Similar to Helper_Options->settings_sanitize() except we are storing/processing values
	 * in Gravity Forms meta table
	 * @param  array $input Fields to process
	 * @return array         Sanitized fields
	 * @return void
	 * @since 4.0
	 */
	public function settings_sanitize( $input = array() ) {
		global $gfpdf;

		$settings = $gfpdf->options->get_registered_fields();
		$sections = array( 'form_settings', 'form_settings_appearance', 'form_settings_custom_appearance', 'form_settings_advanced' );

		foreach ( $sections as $s ) {
			$input = apply_filters( 'gfpdf_settings_'. $s .'_sanitize', $input );
		}

		/* Loop through each setting being saved and pass it through a sanitization filter */
		foreach ( $input as $key => $value ) {

			foreach ( $sections as $s ) {

				/* only process field if found in the section */
				if ( isset($settings[$s][$key]) ) {
					$type = isset( $settings[$s][$key]['type'] ) ? $settings[$s][$key]['type'] : false;

					if ( $type ) {
						/* Field type specific filter */
						$input[$key] = apply_filters( 'gfpdf_form_settings_sanitize_' . $type, $input[$key], $key, $input, $settings[$s][$key] );
					}

					/* General filter */
					$input[$key] = apply_filters( 'gfpdf_form_settings_sanitize', $input[$key], $key, $input, $settings[$s][$key] );
				}
			}
		}

		return $input;
	}

	/**
	 * If the PDF ID exists (either POST or GET) and we have a template with a config file
	 * we will load any fields loaded in the config file
	 * @param  Array $settings Any existing settings loaded
	 * @return Array
	 * @since  4.0
	 */
	public function register_custom_appearance_settings( $settings ) {
		global $gfpdf;

		$pid     = rgget( 'pid' );
		$form_id = (isset($_GET['id'])) ? (int) $_GET['id'] : 0;

		/* If we don't have a specific PDF we'll use the defaults */
		if ( empty($pid) || empty($form_id) ) {
			$template = $gfpdf->options->get_option( 'default_template', 'core-simple' );
		} else {
			/* Load the PDF configuration */
			$pdf      = $this->get_pdf( $form_id, $pid );
			$template = $pdf['template'];
		}

		$class = $this->get_template_configuration( $template );

		return $this->setup_custom_appearance_settings( $class, $settings );
	}

	/**
	 * Add an image of the current selected template (if any)
	 * @param Array $settings Any existing settings loaded
	 */
	public function add_template_image( $settings ) {
		global $gfpdf;

		if ( isset( $settings['template'] ) ) {
			$current_template = $gfpdf->options->get_form_value( $settings['template'] );
			$template_image   = $gfpdf->misc->get_template_image( $current_template );

			if ( ! empty($template_image) ) {
				$img              = '<img src="'. esc_url( $template_image ) . '" alt="' . __( 'Template Example' ) . '" id="gfpdf-template-example" />';
				$settings['template']['desc'] = $settings['template']['desc'] . $img;
			}
		}
		return $settings;
	}

	/**
	 * Load our custom appearance settings (if needed)
	 * @param  Object $class    The template configuration class
	 * @param  Array  $settings Any current settings
	 * @return Array
	 * @since 4.0
	 */
	public function setup_custom_appearance_settings( $class, $settings = array() ) {
		/* If class isn't an instance of our interface return $settings */
		if ( ! ($class instanceof Helper_Interface_Config ) ) {
			return $settings;
		}

		/**
		 * Now we have the class initialised, let's load our configuration array
		 */
		$template_settings = $class->configuration();

		/* register any custom fields */
		if ( isset($template_settings['fields']) && is_array( $template_settings['fields'] ) ) {
			foreach ( $template_settings['fields'] as $key => $field ) {
				$settings[$key] = $field;
			}
		}

		if ( isset($template_settings['core']['background']) && $template_settings['core']['background'] === true ) {
			$settings['background'] = $this->get_background_field();
		}

		/* register our core fields */
		if ( isset($template_settings['core']['header']) && $template_settings['core']['header'] === true ) {
			$settings['header'] = $this->get_header_field();
		}

		if ( isset($template_settings['core']['firstHeader']) && $template_settings['core']['firstHeader'] === true ) {
			$settings['firstHeader'] = $this->get_first_page_header_field();
		}

		if ( isset($template_settings['core']['footer']) && $template_settings['core']['footer'] === true ) {
			$settings['footer'] = $this->get_footer_field();
		}

		if ( isset($template_settings['core']['firstFooter']) && $template_settings['core']['firstFooter'] === true ) {
			$settings['firstFooter'] = $this->get_first_page_footer_field();
		}

		return $settings;
	}

	public function get_header_field() {
		return array(
			'id'         => 'header',
			'name'       => __( 'Header', 'gravitypdf' ),
			'type'       => 'rich_editor',
			'size'       => 8,
			'desc'       => sprintf( __( 'The header is included at the top of each page. For best results, keep the formatting simple.', 'gravitypdf' ), '<em>', '</em>', '<em>', '</em>' ),
			'inputClass' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right mt-hide_all_fields',
			'tooltip'    => '<h6>' . __( 'Header', 'gravitypdf' ) . '</h6>' . sprintf( __( 'For the best image quality, ensure you insert images at %sFull Size%s. Left and right image alignment work as expected, but to center align you need to wrap the image in a %s tag.', 'gravitypdf' ), '<em>', '</em>', esc_html( '<div class="centeralign">...</div>' ) ),
		);
	}

	public function get_first_page_header_field() {
		return array(
			'id'         => 'first_header',
			'name'       => __( 'First Page Header', 'gravitypdf' ),
			'type'       => 'rich_editor',
			'size'       => 8,
			'desc'       => __( 'Override the header on the first page of your PDF.', 'gravitypdf' ),
			'inputClass' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right mt-hide_all_fields',
			'toggle'     => __( 'Use different header on first page of PDF?', 'gravitypdf' ),
		);
	}

	public function get_footer_field() {
		return array(
			'id'         => 'footer',
			'name'       => __( 'Footer', 'gravitypdf' ),
			'type'       => 'rich_editor',
			'size'       => 8,
			'desc'       => sprintf( __( 'The footer is included at the bottom of every page. For simple columns %stry this HTML table snippet%s.', 'gravitypdf' ), '<a href="https://gist.github.com/blueliquiddesigns/e6179a96cd97ef0a8457">', '</a>' ),
			'inputClass' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right mt-hide_all_fields',
			'tooltip'    => '<h6>' . __( 'Footer', 'gravitypdf' ) . '</h6>' . sprintf( __( 'For simple text footers try use the left, center and right alignment buttons in the editor. You can also use the special %s{PAGENO}%s and %s{nbpg}%s tags to display page numbering.', 'gravitypdf' ), '<em>', '</em>', '<em>', '</em>' ),
		);
	}

	public function get_first_page_footer_field() {
		return array(
			'id'         => 'first_footer',
			'name'       => __( 'First Page Footer', 'gravitypdf' ),
			'type'       => 'rich_editor',
			'size'       => 8,
			'desc'       => __( 'Override the footer on the first page of your PDF.', 'gravitypdf' ),
			'inputClass' => 'merge-tag-support mt-wp_editor mt-manual_position mt-position-right mt-hide_all_fields',
			'toggle'     => __( 'Use different footer on first page of PDF?', 'gravitypdf' ),
		);
	}

	public function get_background_field() {
		return array(
			'id'      => 'background',
			'name'    => __( 'Background Image', 'gravitypdf' ),
			'type'    => 'upload',
			'desc'    => __( 'The background image is included on all pages. For optimal results, use an image the same dimensions as your paper size.', 'gravitypdf' ),
			'tooltip' => '<h6>' . __( 'Background Image', 'gravitypdf' ) . '</h6>' . __( 'For the best results, use a JPG or non-interlaced 8-Bit PNG that has the same dimensions as your paper size.', 'gravitypdf' ),
		);
	}

	/**
	 * Attempts to load the current template configuration (if any)
	 * We first look in the PDF_EXTENDED_TEMPLATE directory (in case a user has overridden the file)
	 * Then we try and load the core configuration file
	 * @param  String $template The template config to load
	 * @return Object
	 * @since 4.0
	 */
	public function get_template_configuration( $template ) {
		global $gfpdf;

		$file  = $gfpdf->data->template_location . 'config/' . $template . '.php';
		$class = $this->load_template_configuration( $file );

		$file = PDF_PLUGIN_DIR . 'initialisation/templates/config/' . $template . '.php';
		if ( empty($class) ) {
			$class = $this->load_template_configuration( $file );
		}

		return $class;
	}

	/**
	 * Load our template configuration file, if it exists
	 * @param  String $file      The file to load
	 * @return Object
	 * @since 4.0
	 */
	public function load_template_configuration( $file ) {
		$namespace = 'GFPDF\Templates\Config\\';
		$class     = false;

		if ( is_file( $file ) && is_readable( $file ) ) {
			require_once($file);

			$class_name = str_replace( '-', '_', basename( $file, '.php' ) );
			$fqcn = $namespace . $class_name;

			/* insure the class we are trying to load exists and impliments our Helper_Interface_Config interface */
			if ( class_exists( $fqcn ) && in_array( 'GFPDF\Helper\Helper_Interface_Config', class_implements( $fqcn ) ) ) {
				$class = new $fqcn();
			}
		}

		return $class;
	}

	/**
	 * Auto strip the .pdf extension when sanitizing
	 * @param  String $value The value entered by the user
	 * @param  String $key   The field to be parsed
	 * @return String        The sanitized data
	 */
	public function strip_filename_extension( $value, $key ) {

		if ( $key == 'filename' ) {
			if ( mb_strtolower( mb_substr( $value, -4 ) ) === '.pdf' ) {
				$value = mb_substr( $value, 0, -4 );
			}
		}

		return $value;
	}

	/**
	 * Auto decode the JSON conditional logic string
	 * @param  String $value The value entered by the user
	 * @param  String $key   The field to be parsed
	 * @return String        The sanitized data
	 */
	public function decode_json( $value, $key ) {

		if ( $key == 'conditionalLogic' ) {
			return json_decode( $value, true );
		}

		return $value;
	}


	/**
	 * Update our notification form settings which is specific to the PDF Form Settings Page
	 * @param  Array $notifications The current form notifications
	 * @return void
	 * @since 4.0
	 */
	public function register_notifications( $notifications ) {
		global $gfpdf;

		/* Loop through notifications and format it to our standard */
		if ( is_array( $notifications ) ) {
			$options = array();

			foreach ( $notifications as $notif ) {
				$options[ $notif['id'] ] = $notif['name'];
			}

			/* Apply our settings update */
			$gfpdf->options->update_registered_field( 'form_settings', 'notification', 'options', $options );
		}
	}

	/**
	 * AJAX Endpoint for deleting PDF Settings
	 * @param $_POST['nonce'] a valid nonce
	 * @param $_POST['fid'] a valid form ID
	 * @param $_POST['pid'] a valid PDF ID
	 * @return JSON
	 * @since 4.0
	 */
	public function delete_gf_pdf_setting() {
		global $gfpdf;

		/* prevent unauthorized access */
		if ( ! $gfpdf->form->has_capability( 'gravityforms_edit_settings' ) ) {
			/* fail */
			header( 'HTTP/1.1 401 Unauthorized' );
			wp_die( '401' );
		}

		/*
         * Validate Endpoint
         */

		$nonce = $_POST['nonce'];
		$fid   = (int) $_POST['fid'];
		$pid   = $_POST['pid'];

		$nonce_id = "gfpdf_delete_nonce_{$fid}_{$pid}";

		if ( ! wp_verify_nonce( $nonce, $nonce_id ) ) {
			/* fail */
			header( 'HTTP/1.1 401 Unauthorized' );
			wp_die( '401' );
		}

		$results = $this->delete_pdf( $fid, $pid );

		if ( $results && ! is_wp_error( $results ) ) {
			$return = array(
				'msg' => __( 'PDF successfully deleted.', 'gravitypdf' ),
			);

			echo json_encode( $return );
			wp_die();
		}

		header( 'HTTP/1.1 500 Internal Server Error' );
		wp_die( '500' );
	}

	/**
	 * AJAX Endpoint for duplicating PDF Settings
	 * @param $_POST['nonce'] a valid nonce
	 * @param $_POST['fid'] a valid form ID
	 * @param $_POST['pid'] a valid PDF ID
	 * @return JSON
	 * @since 4.0
	 */
	public function duplicate_gf_pdf_setting() {
		global $gfpdf;

		/* prevent unauthorized access */
		if ( ! $gfpdf->form->has_capability( 'gravityforms_edit_settings' ) ) {
			/* fail */
			header( 'HTTP/1.1 401 Unauthorized' );
			wp_die( '401' );
		}

		/*
         * Validate Endpoint
         */
		$nonce = $_POST['nonce'];
		$fid   = (int) $_POST['fid'];
		$pid   = $_POST['pid'];

		$nonce_id = "gfpdf_duplicate_nonce_{$fid}_{$pid}";

		if ( ! wp_verify_nonce( $nonce, $nonce_id ) ) {
			/* fail */
			header( 'HTTP/1.1 401 Unauthorized' );
			wp_die( '401' );
		}

		$config = $this->get_pdf( $fid, $pid );

		if ( ! is_wp_error( $config ) ) {
			$config['id']   = uniqid();
			$config['name'] = $config['name'] . ' (copy)';

			$results = $this->update_pdf( $fid, $config['id'], $config );

			if ( $results ) {
				$dup_nonce   = wp_create_nonce( "gfpdf_duplicate_nonce_{$fid}_{$config['id']}" );
				$del_nonce   = wp_create_nonce( "gfpdf_delete_nonce_{$fid}_{$config['id']}" );
				$state_nonce = wp_create_nonce( "gfpdf_state_nonce_{$fid}_{$config['id']}" );

				$return = array(
					'msg'         => __( 'PDF successfully duplicated.', 'gravitypdf' ),
					'pid'         => $config['id'],
					'name'        => $config['name'],
					'dup_nonce'   => $dup_nonce,
					'del_nonce'   => $del_nonce,
					'state_nonce' => $state_nonce,
				);

				echo json_encode( $return );
				wp_die();
			}
		}

		header( 'HTTP/1.1 500 Internal Server Error' );
		wp_die( '500' );
	}

	/**
	 * AJAX Endpoint for changing the PDF Settings state
	 * @param $_POST['nonce'] a valid nonce
	 * @param $_POST['fid'] a valid form ID
	 * @param $_POST['pid'] a valid PDF ID
	 * @return JSON
	 * @since 4.0
	 */
	public function change_state_pdf_setting() {
		global $gfpdf;

		/* prevent unauthorized access */
		if ( ! $gfpdf->form->has_capability( 'gravityforms_edit_settings' ) ) {
			/* fail */
			header( 'HTTP/1.1 401 Unauthorized' );
			wp_die( '401' );
		}

		/*
         * Validate Endpoint
         */
		$nonce    = $_POST['nonce'];
		$fid      = (int) $_POST['fid'];
		$pid      = $_POST['pid'];
		$nonce_id = "gfpdf_state_nonce_{$fid}_{$pid}";

		if ( ! wp_verify_nonce( $nonce, $nonce_id ) ) {
			/* fail */
			header( 'HTTP/1.1 401 Unauthorized' );
			wp_die( '401' );
		}

		$config = $this->get_pdf( $fid, $pid );

		if ( ! is_wp_error( $config ) ) {

			/* toggle state */
			$config['active'] = ($config['active'] === true) ? false : true;
			$state            = ($config['active']) ? __( 'Active', 'gravitypdf' ) : __( 'Inactive', 'gravitypdf' );
			$src              = $gfpdf->form->get_plugin_url() . '/images/active' . intval( $config['active'] ) . '.png';

			$results = $this->update_pdf( $fid, $config['id'], $config );

			if ( $results ) {
				$return = array(
					'state' => $state,
					'src'   => $src,
				);

				echo json_encode( $return );
				wp_die();
			}
		}

		header( 'HTTP/1.1 500 Internal Server Error' );
		wp_die( '500' );
	}

	/**
	 * AJAX Endpoint for rendering the template field settings options
	 * @param $_POST['template'] the template to select
	 * @return JSON
	 * @since 4.0
	 */
	public function render_template_fields() {
		global $gfpdf;

		/* prevent unauthorized access */
		if ( ! $gfpdf->form->has_capability( 'gravityforms_edit_settings' ) ) {
			/* fail */
			header( 'HTTP/1.1 401 Unauthorized' );
			wp_die( '401' );
		}

		/* get the current template */
		$template = $_POST['template'];
		$type     = $_POST['type'];
		$class    = $this->get_template_configuration( $template );
		$settings = $this->setup_custom_appearance_settings( $class );

		/* Check if the selected template has a preview */
		$template_image = $gfpdf->misc->get_template_image( $template );

		/* Only handle fields when in the PDF Forms Settings, and not in the general settings */
		if ( $type != 'gfpdf_settings[default_template]' ) {

			/* add our filter to override what template gets rendered (by default it is the current selected template in the config) */
			add_filter('gfpdf_form_settings_custom_appearance', function () use ( &$settings ) {
				/* check if the template has any configuration */
				return $settings;
			}, 100);

			/* Ensure our new fields are registered */
			$gfpdf->options->register_settings( $gfpdf->options->get_registered_fields() );

			/* generate the HTML */
			ob_start();

			do_settings_fields( 'gfpdf_settings_form_settings_custom_appearance', 'gfpdf_settings_form_settings_custom_appearance' );

			$html = ob_get_clean();

			/*
             * Pass the required wp_editor IDs and settings in our AJAX response so the client
             * can correctly load the instances.
             */
			$editors = array();

			foreach ( $settings as $field ) {
				if ( isset($field['type']) && $field['type'] == 'rich_editor' ) {
					$editors[] = 'gfpdf_settings_' . $field['id'];
				}
			}
		}

		$editor_init = ( isset($gfpdf->data->tiny_mce_editor_settings) ) ? $gfpdf->data->tiny_mce_editor_settings : null;
		$html        = ( isset($html) && strlen( trim( $html ) ) > 0 ) ? $html : null;
		$editors     = ( isset($editors) ) ? $editors : null;

		echo json_encode(array(
			'fields'      => $html,
			'preview'     => $template_image,
			'editors'     => $editors,
			'editor_init' => $editor_init,
		));

		/* end AJAX function */
		wp_die();
	}
}
