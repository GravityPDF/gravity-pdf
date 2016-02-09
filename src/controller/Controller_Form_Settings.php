<?php

namespace GFPDF\Controller;

use GFPDF\Helper\Helper_Abstract_Controller;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Interface_Actions;
use GFPDF\Helper\Helper_Interface_Filters;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Abstract_Options;

/**
 * Form Settings (PDF Configuration) Controller
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
 * Controller_Form_Settings
 * Controls the individual form PDF settings pages
 *
 * @since 4.0
 */
class Controller_Form_Settings extends Helper_Abstract_Controller implements Helper_Interface_Actions, Helper_Interface_Filters {
	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var \GFPDF\Helper\Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var \GFPDF\Helper\Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var \GFPDF\Helper\Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Setup our class by injecting all our dependancies
	 *
	 * @param Helper_Abstract_Model|\GFPDF\Model\Model_Form_Settings $model   Our Form Model the controller will manage
	 * @param Helper_Abstract_View|\GFPDF\View\View_Form_Settings    $view    Our Form View the controller will manage
	 * @param \GFPDF\Helper\Helper_Data                              $data    Our plugin data store
	 * @param \GFPDF\Helper\Helper_Abstract_Options                  $options Our options class which allows us to access any settings
	 * @param \GFPDF\Helper\Helper_Misc                              $misc    Our miscellaneous methods
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Model $model, Helper_Abstract_View $view, Helper_Data $data, Helper_Abstract_Options $options, Helper_Misc $misc ) {

		/* Assign our internal variables */
		$this->data    = $data;
		$this->options = $options;
		$this->misc    = $misc;

		/* Load our model and view */
		$this->model = $model;
		$this->model->setController( $this );

		$this->view = $view;
		$this->view->setController( $this );
	}

	/**
	 * Initialise our class defaults
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function init() {

		/*
         * Tell Gravity Forms to add our form PDF settings pages
         */
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Apply any actions needed for the settings page
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_actions() {

		/* Trigger our save method */
		add_action( 'admin_init', array( $this, 'maybe_save_pdf_settings' ), 5 );

		/* Tell Gravity Forms to add our form PDF settings pages */
		add_action( 'gform_form_settings_menu', array( $this->model, 'add_form_settings_menu' ) );
		add_action( 'gform_form_settings_page_' . $this->data->slug, array( $this, 'display_page' ) );

		/* Add AJAX endpoints */
		add_action( 'wp_ajax_gfpdf_list_delete', array( $this->model, 'delete_gf_pdf_setting' ) );
		add_action( 'wp_ajax_gfpdf_list_duplicate', array( $this->model, 'duplicate_gf_pdf_setting' ) );
		add_action( 'wp_ajax_gfpdf_change_state', array( $this->model, 'change_state_pdf_setting' ) );
		add_action( 'wp_ajax_gfpdf_get_template_fields', array( $this->model, 'render_template_fields' ) );
	}

	/**
	 * Apply any filters needed for the settings page
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function add_filters() {

		/* Add a sample image of what the template looks like */
		add_filter( 'gfpdf_form_settings', array( $this->misc, 'add_template_image' ) );

		/* Add custom field information if we have a template selected */
		add_filter( 'gfpdf_form_settings_custom_appearance', array( $this->model, 'register_custom_appearance_settings' ) );
		add_filter( 'gfpdf_form_settings', array( $this->model, 'register_template_group' ) );

		/* Add Validation Errors */
		add_filter( 'gfpdf_form_settings', array( $this->model, 'validation_error' ) );
		add_filter( 'gfpdf_form_settings_appearance', array( $this->model, 'validation_error' ) );

		/* Sanitize Results */
		add_filter( 'gfpdf_form_settings_sanitize', array( $this->options, 'sanitize_all_fields' ), 10, 4 );
		add_filter( 'gfpdf_form_settings_sanitize_text', array( $this->model, 'parse_filename_extension' ), 15, 2 );
		add_filter( 'gfpdf_form_settings_sanitize_text', array( $this->options, 'sanitize_trim_field' ), 15, 2 );
		add_filter( 'gfpdf_form_settings_sanitize_hidden', array( $this->model, 'decode_json' ), 10, 2 );

		add_filter( 'gfpdf_skip_highlight_errors', array( $this->model, 'check_custom_size_error_highlighting' ), 10, 3 );

		/* Store our TinyMCE Options */
		add_filter( 'tiny_mce_before_init', array( $this, 'store_tinymce_settings' ) );
	}

	/**
	 * Determine if we should be saving the PDF settings
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function maybe_save_pdf_settings() {
		$form_id = ( isset( $_GET['id'] ) ) ? (int) $_GET['id'] : false;
		$pdf_id  = ( isset( $_GET['pid'] ) ) ? $_GET['pid'] : false;

		/* Load the add/edit page */
		if ( $pdf_id !== false && rgpost( 'gfpdf_save_pdf' ) ) {
			$this->model->process_submission( $form_id, $pdf_id );
		}
	}

	/**
	 * Processes / Setup the form settings page.
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function display_page() {

		/* Determine whether to load the add/edit page, or the list view */
		$form_id = ( isset( $_GET['id'] ) ) ? (int) $_GET['id'] : false;
		$pdf_id  = ( isset( $_GET['pid'] ) ) ? $_GET['pid'] : false;

		/* Load the add/edit page */
		if ( $pdf_id !== false ) {
			$this->model->show_edit_view( $form_id, $pdf_id );

			return null;
		}

		/* process list view */
		$this->model->process_list_view( $form_id );
	}

	/**
	 * Store our TinyMCE init settings for use in our AJAX wp_editor calls
	 *
	 * @param array $settings The current TinyMCE Settings
	 *
	 * @return array Original Settings
	 *
	 * @since 4.0
	 */
	public function store_tinymce_settings( $settings ) {

		if ( empty( $this->data->tiny_mce_editor_settings ) ) {
			$this->data->tiny_mce_editor_settings = $settings;
		}

		return $settings;
	}
}
