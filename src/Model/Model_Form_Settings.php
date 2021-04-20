<?php

namespace GFPDF\Model;

use GFFormsModel;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Interface_Config;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Options_Fields;
use GFPDF\Helper\Helper_PDF_List_Table;
use GFPDF\Helper\Helper_Templates;
use GFPDF\View\View_GravityForm_Settings_Markup;
use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Model_Form_Settings
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class Model_Form_Settings extends Helper_Abstract_Model {

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var Helper_Form
	 *
	 * @since 4.0
	 */
	protected $gform;

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.0
	 */
	protected $log;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

	/**
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

	/**
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

	/**
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $notices;

	/**
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var Helper_Templates
	 *
	 * @since 4.0
	 */
	protected $templates;

	/**
	 * Setup our class by injecting all our dependencies
	 *
	 * @param Helper_Abstract_Form    $gform   Our abstracted Gravity Forms helper functions
	 * @param LoggerInterface         $log     Our logger class
	 * @param Helper_Data             $data    Our plugin data store
	 * @param Helper_Abstract_Options $options Our options class which allows us to access any settings
	 * @param Helper_Misc             $misc    Our miscellaneous class
	 * @param Helper_Notices          $notices Our notice class used to queue admin messages and errors
	 * @param Helper_Templates        $templates
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Form $gform, LoggerInterface $log, Helper_Data $data, Helper_Abstract_Options $options, Helper_Misc $misc, Helper_Notices $notices, Helper_Templates $templates ) {

		/* Assign our internal variables */
		$this->gform     = $gform;
		$this->log       = $log;
		$this->data      = $data;
		$this->options   = $options;
		$this->misc      = $misc;
		$this->notices   = $notices;
		$this->templates = $templates;
	}

	/**
	 * Add the form settings tab.
	 *
	 * Override this function to add the tab conditionally.
	 *
	 * @param array $tabs The list of existing tags
	 *
	 * @return array modified list of $tabs
	 *
	 * @since 4.0
	 */
	public function add_form_settings_menu( $tabs ) {
		$tabs[] = [
			'name'         => $this->data->slug,
			'label'        => $this->data->short_title,
			'query'        => [ 'pid' => null ],
			'icon'         => 'dashicons-media-document',
			'capabilities' => [ 'gravityforms_edit_settings' ],
		];

		return $tabs;
	}

	/**
	 * Setup the PDF Settings List View Logic
	 *
	 * @param integer $form_id The Gravity Form ID
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function process_list_view( $form_id ) {

		/* prevent unauthorized access */
		if ( ! $this->gform->has_capability( 'gravityforms_edit_settings' ) ) {
			$this->log->warning( 'Lack of User Capabilities.' );
			wp_die( esc_html__( 'You do not have permission to access this page', 'gravity-forms-pdf-extended' ) );
		}

		$controller = $this->getController();

		/* get the form object */
		$form = $this->gform->get_form( $form_id );

		/* load our list table */
		$pdf_table = new Helper_PDF_List_Table( $form, $this->gform, $this->misc, $this->templates );
		$pdf_table->prepare_items();

		/* pass to view */
		$controller->view->list(
			[
				'title'       => $this->data->title,
				'add_new_url' => $add_new_url = add_query_arg( [ 'pid' => 0 ] ),
				'list_items'  => $pdf_table,
			]
		);
	}

	/**
	 * Setup the PDF Settings Add/Edit View Logic
	 *
	 * @param integer $form_id The Gravity Form ID
	 * @param integer $pdf_id  The PDF configuration ID
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function show_edit_view( $form_id, $pdf_id ) {

		/* prevent unauthorized access */
		if ( ! $this->gform->has_capability( 'gravityforms_edit_settings' ) ) {
			$this->log->warning( 'Lack of User Capabilities.' );
			wp_die( esc_html__( 'You do not have permission to access this page', 'gravity-forms-pdf-extended' ) );
		}

		$controller = $this->getController();

		/* get the form object */
		$form = $this->gform->get_form( $form_id );
		$form = apply_filters( 'gform_admin_pre_render', $form );

		/* parse input and get required information */
		if ( ! $pdf_id ) {
			if ( rgpost( 'gform_pdf_id' ) ) {
				$pdf_id = rgpost( 'gform_pdf_id' );
			} else {
				$pdf_id = uniqid();
			}
		}

		$entry_meta = GFFormsModel::get_entry_meta( $form_id );
		$entry_meta = apply_filters( 'gform_entry_meta_conditional_logic_confirmations', $entry_meta, $form, '' );

		/* re-register all our settings to show form-specific options */
		$this->options->register_settings( $this->options->get_registered_fields() );

		/* re-register our Gravity Forms Notifications */
		$this->register_notifications( $form['notifications'] );

		/* Pull the PDF settings */
		$pdf = $this->options->get_pdf( $form_id, $pdf_id );

		/* prepare our data */
		$update_pdf_text = esc_html__( 'Update PDF', 'gravity-forms-pdf-extended' );
		$label           = esc_html__( 'Add PDF', 'gravity-forms-pdf-extended' );

		if ( ( $_POST['submit'] ?? '' ) === $update_pdf_text || ( ! is_wp_error( $pdf ) && ! isset( $pdf['status'] ) ) ) {
			$label = $update_pdf_text;
		}

		wp_enqueue_editor();

		/* pass to view */
		$controller->view->add_edit(
			[
				'pdf_id'       => $pdf_id,
				'title'        => $label,
				'button_label' => $label,
				'form'         => $form,
				'entry_meta'   => $entry_meta,
				'pdf'          => $pdf,
			]
		);
	}

	/**
	 * Update our notification form settings which is specific to the PDF Form Settings Page (i.e we need an actual $form object which isn't present when we originally register the settings)
	 *
	 * @param array $notifications The current form notifications
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function register_notifications( $notifications ) {

		/* Loop through notifications and format it to our standard */
		if ( is_array( $notifications ) ) {
			$options = [];

			/* Filter out the save and continue notifications */
			$omit = [ 'form_saved', 'form_save_email_requested' ];

			foreach ( $notifications as $notification ) {
				$event = ( isset( $notification['event'] ) ) ? $notification['event'] : '';

				if ( ! in_array( $event, $omit, true ) ) {
					$options[ $notification['id'] ] = $notification['name'];
				}
			}

			/* Apply our settings update */
			$this->options->update_registered_field( 'form_settings', 'notification', 'options', $options );
		}
	}

	/**
	 * Validate, Sanitize and Update PDF settings
	 *
	 * @param integer $form_id The Gravity Form ID
	 * @param integer $pdf_id  The PDF configuration ID
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function process_submission( $form_id, $pdf_id ) {

		/* prevent unauthorized access */
		if ( ! $this->gform->has_capability( 'gravityforms_edit_settings' ) ) {
			$this->log->critical(
				'Lack of User Capabilities.',
				[
					'user'      => wp_get_current_user(),
					'user_meta' => get_user_meta( get_current_user_id() ),
				]
			);

			wp_die( esc_html__( 'You do not have permission to access this page', 'gravity-forms-pdf-extended' ) );
		}

		/* Check Nonce is valid */
		if ( ! wp_verify_nonce( rgpost( 'gfpdf_save_pdf' ), 'gfpdf_save_pdf' ) ) {
			$this->log->warning( 'Nonce Verification Failed.' );
			$this->notices->add_error( esc_html__( 'There was a problem saving your PDF settings. Please try again.', 'gravity-forms-pdf-extended' ) );

			return false;
		}

		/* Check if we have a new PDF ID */
		if ( empty( $pdf_id ) ) {
			$pdf_id = ( rgpost( 'gform_pdf_id' ) ) ? rgpost( 'gform_pdf_id' ) : false;
		}

		$input = rgpost( 'gfpdf_settings' );

		/* check appropriate settings */
		if ( ! is_array( $input ) || ! $pdf_id ) {
			$this->log->error(
				'Invalid Data.',
				[
					'post' => $input,
					'pid'  => $pdf_id,
				]
			);

			$this->notices->add_error( esc_html__( 'There was a problem saving your PDF settings. Please try again.', 'gravity-forms-pdf-extended' ) );

			return false;
		}

		$sanitized = $this->settings_sanitize( $input );

		/* Update our GFPDF settings */
		$sanitized['id']     = $pdf_id;
		$sanitized['status'] = 'sanitizing'; /* used as a switch to tell when a record has been saved to the database, or stuck in validation */

		/* Save current PDF state */
		$pdf                 = $this->options->get_pdf( $form_id, $pdf_id );
		$sanitized['active'] = ( ! is_wp_error( $pdf ) && isset( $pdf['active'] ) ) ? $pdf['active'] : true;

		$this->options->update_pdf( $form_id, $pdf_id, $sanitized, false );

		/* Do validation */
		if ( empty( $sanitized['name'] ) || empty( $sanitized['filename'] ) ||
			 ( $sanitized['pdf_size'] === 'CUSTOM' && ( (float) $sanitized['custom_pdf_size'][0] === 0 || (float) $sanitized['custom_pdf_size'][1] === 0 ) )
		) {
			$this->notices->add_error( esc_html__( 'PDF could not be saved. Please enter all required information below.', 'gravity-forms-pdf-extended' ) );

			return false;
		}

		/* Remove our status */
		unset( $sanitized['status'] );

		/* Update the database */
		$did_update = $this->options->update_pdf( $form_id, $pdf_id, $sanitized );

		/* If it updated, let's update the global variable */
		if ( $did_update !== false ) {
			$this->log->notice(
				'Successfully Saved Form PDF Settings.',
				[
					'form_id'  => $form_id,
					'pdf_id'   => $pdf_id,
					'settings' => $sanitized,
				]
			);

			$this->notices->add_notice( sprintf( esc_html__( 'PDF saved successfully. %1$sBack to PDF list.%2$s', 'gravity-forms-pdf-extended' ), '<a href="' . remove_query_arg( 'pid' ) . '">', '</a>' ) );

			return true;
		}

		$this->log->error( 'Failed to Save Form PDF Settings.' );
		$this->notices->add_error( esc_html__( 'There was a problem saving your PDF settings. Please try again.', 'gravity-forms-pdf-extended' ) );

		return false;
	}

	/**
	 * Similar to Helper_Abstract_Options->settings_sanitize() except we don't need as robust validation and error checking
	 *
	 * @param array $input Fields to process
	 *
	 * @return array         Sanitized fields
	 *
	 * @since 4.0
	 */
	public function settings_sanitize( $input = [] ) {

		$settings = $this->options->get_registered_fields();
		$sections = [
			'form_settings',
			'form_settings_appearance',
			'form_settings_custom_appearance',
			'form_settings_advanced',
		];

		foreach ( $sections as $s ) {
			/*
			 * Loop through the settings whitelist and add any missing fields to the $input
			 */
			foreach ( $settings[ $s ] as $key => $value ) {
				switch ( $value['type'] ) {
					case 'select':
					case 'multicheck':
						if ( ! isset( $input[ $key ] ) ) {
							$input[ $key ] = [];
						}
						break;

					case 'checkbox':
						break;

					default:
						if ( ! isset( $input[ $key ] ) ) {
							$input[ $key ] = '';
						}
						break;
				}
			}

			$input = apply_filters( 'gfpdf_settings_' . $s . '_sanitize', $input );
		}

		/* Loop through each setting being saved and pass it through a sanitization filter */
		if ( is_array( $input ) && 0 < count( $input ) ) {
			foreach ( $input as $key => $value ) {

				foreach ( $sections as $s ) {

					/* only process field if found in the section */
					if ( isset( $settings[ $s ][ $key ] ) ) {
						$type = isset( $settings[ $s ][ $key ]['type'] ) ? $settings[ $s ][ $key ]['type'] : false;

						/*
						 * General filter
						 *
						 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_form_settings_sanitize/ for more details about this filter
						 */
						$input[ $key ] = apply_filters( 'gfpdf_form_settings_sanitize', $input[ $key ], $key, $input, $settings[ $s ][ $key ] );

						if ( $type ) {
							/*
							 * Field type specific filter
							 *
							 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_form_settings_sanitize/ for more details about this filter
							 */
							$input[ $key ] = apply_filters( 'gfpdf_form_settings_sanitize_' . $type, $input[ $key ], $key, $input, $settings[ $s ][ $key ] );
						}
					}
				}
			}
		}

		return $input;
	}

	/**
	 * Apply gfield_error class when validation fails, highlighting field blocks with problems
	 *
	 * @param array $fields Array of fields to process
	 *
	 * @return array|false         Modified list of fields
	 *
	 * @since 4.0
	 */
	public function validation_error( $fields ) {

		/**
		 * Check if we actually need to do any validating
		 * Because of the way the Gravity Forms Settings page is processed we are hooking into the core
		 * "gfpdf_form_settings" filter which runs when ever update_option( 'pdf_form_settings' ) is run.
		 * We don't need to do any validation when not on the GF PDF Settings page
		 */
		if ( empty( $_POST['gfpdf_save_pdf'] ) ) {
			return $fields;
		}

		/* Check we have a valid nonce, or throw an error */
		if ( ! wp_verify_nonce( rgpost( 'gfpdf_save_pdf' ), 'gfpdf_save_pdf' ) ) {
			$this->log->warning( 'Nonce Verification Failed.' );
			$this->notices->add_error( esc_html__( 'There was a problem saving your PDF settings. Please try again.', 'gravity-forms-pdf-extended' ) );

			return false;
		}

		$input = rgpost( 'gfpdf_settings' );

		/* Throw errors on required fields */
		foreach ( $fields as $key => &$field ) {

			if ( isset( $field['required'] ) && $field['required'] === true ) {

				$value          = $input[ $field['id'] ] ?? '';
				$field['class'] = $field['class'] ?? '';

				/* Add way to skip the highlighting of errors */
				if ( apply_filters( 'gfpdf_skip_highlight_errors', false, $field, $input ) ) {
					continue;
				}

				/* If the value is an array ensure all items have values */
				if ( is_array( $value ) ) {
					if ( count( array_filter( $value ) ) !== count( $value ) ) {
						$field['class'] .= ' gform-settings-input__container--invalid';
						$field['desc2']  = '<div class="gform-settings-validation__error">' . esc_html__( 'This field is required.', 'gravityforms' ) . '</div>';
					}

					continue;
				}

				/*
				 * If string, sanitize and add error if appropriate
				 *
				 * See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_form_settings_sanitize/ for more details about this filter
				 */
				$value = apply_filters( 'gfpdf_form_settings_sanitize_text', $value, $key );
				if ( empty( $value ) ) {
					$field['class'] .= ' gform-settings-input__container--invalid';
					$field['desc2']  = '<div class="gform-settings-validation__error">' . esc_html__( 'This field is required.', 'gravityforms' ) . '</div>';
				}
			}
		}

		return $fields;
	}

	/**
	 * Do further checks to see if the custom PDF size should in fact be marked as an error
	 * Because it is dependant on the paper size option in some cases it shouldn't be highlighted
	 *
	 * @param boolean $skip  Whether to skip error highlighting checks
	 * @param array   $field The Gravity Form field
	 * @param array   $input The user input
	 *
	 * @return boolean
	 *
	 * @since  4.0
	 */
	public function check_custom_size_error_highlighting( $skip, $field, $input ) {

		if ( $field['id'] === 'custom_pdf_size' ) {

			/* Skip if not currently being shown */
			if ( $input['pdf_size'] !== 'CUSTOM' ) {
				return true;
			}
		}

		return $skip;
	}

	/**
	 * If the PDF ID exists (either POST or GET) and we have a template with a config file
	 * we will load any fields loaded in the config file
	 *
	 * @param array $settings Any existing settings loaded
	 *
	 * @return array
	 *
	 * @since  4.0
	 */
	public function register_custom_appearance_settings( $settings ) {
		$template = $this->get_template_name_from_current_page();
		$class    = $this->templates->get_config_class( $template );

		return $this->setup_custom_appearance_settings( $class, $settings );
	}

	/*
	 * To allow for correct backwards compatibility with our v3 templates we need to hide the font, size and colour
	 * information when selected. To allow this behaviour we're going to assign a 'data-template_group' attribute
	 * to the template select box which our JS can pick up and use to toggle those fields
	 *
	 * @param array $settings The current PDF settings
	 *
	 * @return array
	 *
	 * @since 4.0
	 */

	/**
	 * Check if we are on the Form Settings Edit page and gets the appropriate template name
	 *
	 * @return string The current saved PDF template
	 *
	 * @since 4.0
	 */
	public function get_template_name_from_current_page() {

		$pid     = ( ! empty( $_GET['pid'] ) ) ? rgget( 'pid' ) : rgpost( 'gform_pdf_id' );
		$form_id = ( isset( $_GET['id'] ) ) ? (int) $_GET['id'] : 0;

		/* If we don't have a specific PDF we'll use the defaults */
		if ( empty( $pid ) || empty( $form_id ) ) {
			$template = $this->options->get_option( 'default_template', 'zadani' );
		} else {
			/* Load the PDF configuration */
			$pdf = $this->options->get_pdf( $form_id, $pid );

			if ( ! is_wp_error( $pdf ) ) {
				$template = $pdf['template'];
			} else {
				$template = '';
			}
		}

		return $template;
	}

	/**
	 * Load our custom appearance settings (if needed)
	 *
	 * @param object $class    The template configuration class
	 * @param array  $settings Any current settings
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function setup_custom_appearance_settings( $class, $settings = [] ) {

		/* If class isn't an instance of our interface return $settings */
		if ( ! ( $class instanceof Helper_Interface_Config ) ) {

			$this->log->warning(
				'Instanceof Failed.',
				[
					'object' => get_class( $class ),
					'type'   => 'Helper_Interface_Config',
				]
			);

			return $settings;
		}

		/**
		 * Now we have the class initialised, let's load our configuration array
		 */
		$template_settings = $class->configuration();

		/* register any custom fields */
		if ( isset( $template_settings['fields'] ) && is_array( $template_settings['fields'] ) ) {
			foreach ( $template_settings['fields'] as $key => $field ) {
				$settings[ $key ] = $field;
			}
		}

		$settings = $this->setup_core_custom_appearance_settings( $settings, $class, $template_settings );

		return $settings;
	}

	/**
	 * Setup any core fields that are registered to the PDF template
	 *
	 * @param array                   $settings          Any current settings
	 * @param Helper_Interface_Config $class             The template configuration class
	 * @param array                   $template_settings Loaded configuration array
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function setup_core_custom_appearance_settings( array $settings, Helper_Interface_Config $class, array $template_settings ) {

		/* register our core fields */
		$core_fields = [
			'show_form_title'      => [ $this->options, 'get_form_title_display_field' ],
			'show_page_names'      => [ $this->options, 'get_page_names_display_field' ],
			'show_html'            => [ $this->options, 'get_html_display_field' ],
			'show_section_content' => [ $this->options, 'get_section_content_display_field' ],
			'enable_conditional'   => [ $this->options, 'get_conditional_display_field' ],
			'show_empty'           => [ $this->options, 'get_empty_display_field' ],

			'background_color'     => [ $this->options, 'get_background_color_field' ],
			'background_image'     => [ $this->options, 'get_background_image_field' ],
			'header'               => [ $this->options, 'get_header_field' ],
			'first_header'         => [ $this->options, 'get_first_page_header_field' ],
			'footer'               => [ $this->options, 'get_footer_field' ],
			'first_footer'         => [ $this->options, 'get_first_page_footer_field' ],
		];

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_core_template_fields_list/ for more details about this filter */
		$core_fields = apply_filters( 'gfpdf_core_template_fields_list', $core_fields, $template_settings, $class );

		foreach ( $core_fields as $id => $method ) {

			if ( isset( $template_settings['core'][ $id ] ) && $template_settings['core'][ $id ] === true ) {
				$settings[ $id ] = call_user_func( $method );
			}
		}

		return $settings;
	}

	public function register_template_group( $settings ) {

		/* Add our template group */
		if ( isset( $settings['template'] ) && is_array( $settings['template'] ) ) {

			$template_info = $this->templates->get_template_info_by_id( $this->get_template_name_from_current_page() );

			/* Ensure the key we want is an array, otherwise set it to one */
			$settings['template']['data']                   = ( isset( $settings['template']['data'] ) && is_array( $settings['template']['data'] ) ) ? $settings['template']['data'] : [];
			$settings['template']['data']['template_group'] = $template_info['group'];
		}

		return $settings;
	}

	/**
	 * Auto strip the .pdf extension when sanitizing
	 *
	 * @param string $value The value entered by the user
	 * @param string $key   The field to be parsed
	 *
	 * @return string        The sanitized data
	 */
	public function parse_filename_extension( $value, $key ) {

		if ( $key === 'filename' ) {
			$value = $this->misc->remove_extension_from_string( $value );
		}

		return $value;
	}

	/**
	 * Auto decode the JSON conditional logic string
	 *
	 * @param string $value The value entered by the user
	 * @param string $key   The field to be parsed
	 *
	 * @return array|string        The sanitized data
	 */
	public function decode_json( $value, $key ) {

		if ( $key === 'conditionalLogic' ) {
			return json_decode( $value, true );
		}

		return $value;
	}

	/**
	 * AJAX Endpoint for deleting PDF Settings
	 *
	 * @return string JSON
	 *
	 * @internal param $_POST ['nonce'] a valid nonce
	 * @internal param $_POST ['fid'] a valid form ID
	 * @internal param $_POST ['pid'] a valid PDF ID
	 *
	 * @since    4.0
	 */
	public function delete_gf_pdf_setting() {

		$fid      = ( isset( $_POST['fid'] ) ) ? (int) $_POST['fid'] : 0;
		$pid      = ( isset( $_POST['pid'] ) ) ? $_POST['pid'] : '';
		$nonce_id = "gfpdf_delete_nonce_{$fid}_{$pid}";

		/* User / CORS validation */
		$this->misc->handle_ajax_authentication( 'Delete PDF Settings', 'gravityforms_edit_settings', $nonce_id );

		/* Delete PDF settings */
		$results = $this->options->delete_pdf( $fid, $pid );

		if ( $results && ! is_wp_error( $results ) ) {

			$this->log->notice( 'AJAX – Successfully Deleted PDF Settings' );

			$return = [
				'msg' => esc_html__( 'PDF successfully deleted.', 'gravity-forms-pdf-extended' ),
			];

			echo json_encode( $return );
			wp_die();
		}

		$errors = [];
		if ( is_wp_error( $results ) ) {
			$errors = [
				'WP_Error_Message' => $results->get_error_message(),
				'WP_Error_Code'    => $results->get_error_code(),
			];
		}

		$this->log->error( 'AJAX Endpoint Failed', $errors );

		/* Internal Server Error */
		wp_die( '500', 500 );
	}

	/**
	 * AJAX Endpoint for duplicating PDF Settings
	 *
	 * @return string JSON
	 *
	 * @internal param $_POST ['nonce'] a valid nonce
	 * @internal param $_POST ['fid'] a valid form ID
	 * @internal param $_POST ['pid'] a valid PDF ID
	 *
	 * @since    4.0
	 */
	public function duplicate_gf_pdf_setting() {

		$fid = ( isset( $_POST['fid'] ) ) ? (int) $_POST['fid'] : 0;
		$pid = ( isset( $_POST['pid'] ) ) ? $_POST['pid'] : '';

		$nonce_id = "gfpdf_duplicate_nonce_{$fid}_{$pid}";

		/* User / CORS validation */
		$this->misc->handle_ajax_authentication( 'Duplicate PDF Settings', 'gravityforms_edit_settings', $nonce_id );

		/* Duplicate PDF config */
		$config = $this->options->get_pdf( $fid, $pid );

		if ( ! is_wp_error( $config ) ) {
			$config['id']     = uniqid();
			$config['name']   = $config['name'] . ' (copy)';
			$config['active'] = false;

			$results = $this->options->update_pdf( $fid, $config['id'], $config );

			if ( $results ) {
				$this->log->notice( 'AJAX – Successfully Duplicated PDF Setting' );

				/* @todo just use the same nonce for all requests since WP nonces aren't one-time user (time based) */
				$dup_nonce   = wp_create_nonce( "gfpdf_duplicate_nonce_{$fid}_{$config['id']}" );
				$del_nonce   = wp_create_nonce( "gfpdf_delete_nonce_{$fid}_{$config['id']}" );
				$state_nonce = wp_create_nonce( "gfpdf_state_nonce_{$fid}_{$config['id']}" );

				$return = [
					'msg'         => esc_html__( 'PDF successfully duplicated.', 'gravity-forms-pdf-extended' ),
					'pid'         => $config['id'],
					'name'        => $config['name'],
					'dup_nonce'   => $dup_nonce,
					'del_nonce'   => $del_nonce,
					'state_nonce' => $state_nonce,
					'status'      => esc_html__( 'Inactive', 'gravity-forms-pdf-extended' ),
				];

				echo json_encode( $return );
				wp_die();
			}
		}

		$this->log->error(
			'AJAX Endpoint Failed',
			[
				'WP_Error_Message' => $config->get_error_message(),
				'WP_Error_Code'    => $config->get_error_code(),
			]
		);

		/* Internal Server Error */
		wp_die( '500', 500 );
	}

	/**
	 * AJAX Endpoint for changing the PDF Settings state
	 *
	 * @return string JSON
	 *
	 * @internal param $_POST ['nonce'] a valid nonce
	 * @internal param $_POST ['fid'] a valid form ID
	 * @internal param $_POST ['pid'] a valid PDF ID
	 *
	 * @since    4.0
	 */
	public function change_state_pdf_setting() {

		$fid      = ( isset( $_POST['fid'] ) ) ? (int) $_POST['fid'] : 0;
		$pid      = ( isset( $_POST['pid'] ) ) ? $_POST['pid'] : '';
		$nonce_id = "gfpdf_state_nonce_{$fid}_{$pid}";

		/* User / CORS validation */
		$this->misc->handle_ajax_authentication( 'Change PDF Settings State', 'gravityforms_edit_settings', $nonce_id );

		/* Change the PDF state */
		$config = $this->options->get_pdf( $fid, $pid );

		if ( ! is_wp_error( $config ) ) {

			/* toggle state */
			$config['active'] = ( $config['active'] === true ) ? false : true;
			$state            = ( $config['active'] ) ? esc_attr__( 'Active', 'gravity-forms-pdf-extended' ) : esc_attr__( 'Inactive', 'gravity-forms-pdf-extended' );

			$results = $this->options->update_pdf( $fid, $config['id'], $config );

			if ( $results ) {
				$this->log->notice( 'AJAX – Successfully Updated PDF State' );

				$return = [
					'state' => $state,
					'fid'   => $fid,
					'pid'   => $config['id'],
				];

				echo json_encode( $return );
				wp_die();
			}
		}

		$this->log->error(
			'AJAX Endpoint Failed',
			[
				'WP_Error_Message' => $config->get_error_message(),
				'WP_Error_Code'    => $config->get_error_code(),
			]
		);

		/* Internal Server Error */
		wp_die( '500', 500 );
	}

	/**
	 * AJAX Endpoint for rendering the template field settings options
	 *
	 * @return string JSON
	 *
	 * @internal param $_POST ['template'] the template to select
	 *
	 * @since    4.0
	 */
	public function render_template_fields() {

		/* User / CORS validation */
		$this->misc->handle_ajax_authentication( 'Render Template Custom Fields', 'gravityforms_edit_settings' );

		/* get the current template */
		$template = ( isset( $_POST['template'] ) ) ? $_POST['template'] : '';
		$type     = ( isset( $_POST['type'] ) ) ? $_POST['type'] : '';
		$class    = $this->templates->get_config_class( $template );
		$settings = $this->setup_custom_appearance_settings( $class );

		/* Only handle fields when in the PDF Forms Settings, and not in the general settings */
		if ( $type !== 'gfpdf_settings[default_template]' ) {

			/* Get the template type so we can return out to the browser */
			$template_data = $this->templates->get_template_info_by_id( $template );
			$template_type = mb_strtolower( $template_data['group'] );

			/* add our filter to override what template gets rendered (by default it is the current selected template in the config) */
			add_filter(
				'gfpdf_form_settings_custom_appearance',
				function() use ( &$settings ) {
					/* check if the template has any configuration */
					return $settings;
				},
				100
			);

			/* Remove any TinyMCE custom plugins which causes loading issues */
			remove_all_filters( 'mce_external_plugins' );

			/* Ensure our new fields are registered */
			$this->options->register_settings( $this->options->get_registered_fields() );

			/* generate the HTML */
			$markup = new View_GravityForm_Settings_Markup();
			$html   = $markup->do_settings_fields( 'gfpdf_settings_form_settings_custom_appearance', $markup::ENABLE_PANEL_TITLE );

			/*
			 * Pass the required wp_editor IDs and settings in our AJAX response so the client
			 * can correctly load the instances.
			 */
			$editors = [];

			foreach ( $settings as $field ) {
				if ( isset( $field['type'] ) && $field['type'] === 'rich_editor' ) {
					$editors[] = 'gfpdf_settings_' . $field['id'];
				}
			}
		}

		$editor_init   = ( isset( $this->data->tiny_mce_editor_settings ) ) ? $this->data->tiny_mce_editor_settings : null;
		$html          = ( isset( $html ) && strlen( trim( $html ) ) > 0 ) ? $html : null;
		$editors       = ( isset( $editors ) ) ? $editors : null;
		$template_type = ( isset( $template_type ) ) ? $template_type : null;

		$return = [
			'fields'        => $html,
			'editors'       => $editors,
			'editor_init'   => $editor_init,
			'template_type' => $template_type,
		];

		$this->log->notice( 'AJAX – Successfully Rendered Template Custom Fields', $return );

		echo json_encode( $return );

		/* end AJAX function */
		wp_die();
	}
}
