<?php

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Addon;
use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Notices;
use GFPDF\Helper\Helper_Templates;
use Psr\Log\LoggerInterface;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Model_Welcome_Screen
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class Model_Settings extends Helper_Abstract_Model {

	/**
	 * Errors with the global form submission process are stored here
	 *
	 * @var array
	 *
	 * @since 4.0
	 *
	 * @Internal Deprecated method
	 */
	public $form_settings_errors;

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var \GFPDF\Helper\Helper_Form
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
	 * Holds our Helper_Notices object
	 * which we can use to queue up admin messages for the user
	 *
	 * @var \GFPDF\Helper\Helper_Notices
	 *
	 * @since 4.0
	 */
	protected $notices;

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
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var \GFPDF\Helper\Helper_Data
	 *
	 * @since 4.0
	 */
	protected $data;

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
	 * Holds our Helper_Templates object
	 * used to ease access to our PDF templates
	 *
	 * @var \GFPDF\Helper\Helper_Templates
	 *
	 * @since 4.0
	 */
	protected $templates;

	/**
	 * Set up our dependencies
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Form    $gform   Our abstracted Gravity Forms helper functions
	 * @param LoggerInterface                       $log     Our logger class
	 * @param \GFPDF\Helper\Helper_Notices          $notices Our notice class used to queue admin messages and errors
	 * @param \GFPDF\Helper\Helper_Abstract_Options $options Our options class which allows us to access any settings
	 * @param \GFPDF\Helper\Helper_Data             $data    Our plugin data store
	 * @param \GFPDF\Helper\Helper_Misc             $misc    Our miscellaneous class
	 * @param \GFPDF\Helper\Helper_Templates        $templates
	 *
	 * @since 4.0
	 */
	public function __construct( Helper_Abstract_Form $gform, LoggerInterface $log, Helper_Notices $notices, Helper_Abstract_Options $options, Helper_Data $data, Helper_Misc $misc, Helper_Templates $templates ) {

		/* Assign our internal variables */
		$this->gform     = $gform;
		$this->log       = $log;
		$this->options   = $options;
		$this->notices   = $notices;
		$this->data      = $data;
		$this->misc      = $misc;
		$this->templates = $templates;
	}

	/**
	 * If any errors have been passed back from the options.php page we will highlight the actual fields that caused them
	 *
	 * @param  array $settings The get_registered_fields() array
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function highlight_errors( $settings ) {

		/* We fire too late to tap into get_settings_error() so our data storage holds the details */
		$errors = get_transient( 'settings_errors' );

		/* Loop through errors if any and highlight the appropriate settings */
		if ( is_array( $errors ) && sizeof( $errors ) > 0 ) {
			foreach ( $errors as $error ) {

				/* Skip over if not an error */
				if ( $error['type'] !== 'error' ) {
					continue;
				}

				/* Loop through our data until we find a match */
				$found = false;
				foreach ( $settings as $key => &$group ) {
					foreach ( $group as $id => &$item ) {
						if ( $item['id'] === $error['code'] ) {
							$item['class'] = ( isset( $item['class'] ) ) ? $item['class'] . ' gfield_error' : 'gfield_error';
							$found         = true;
							break;
						}
					}

					/* exit outer loop */
					if ( $found ) {
						break;
					}
				}
			}
		}

		return $settings;
	}

	/**
	 * Install the files stored in /src/templates/ to the user's template directory
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function install_templates() {

		$destination = $this->templates->get_template_path();
		$copy        = $this->misc->copyr( PDF_PLUGIN_DIR . 'src/templates/', $destination );
		if ( is_wp_error( $copy ) ) {
			$this->log->error( 'Template Installation Error.' );
			$this->notices->add_error( sprintf( esc_html__( 'There was a problem copying all PDF templates to %s. Please try again.', 'gravity-forms-pdf-extended' ), '<code>' . $this->misc->relative_path( $destination ) . '</code>' ) );

			return false;
		}

		$this->notices->add_notice( sprintf( esc_html__( 'Gravity PDF Custom Templates successfully installed to %s.', 'gravity-forms-pdf-extended' ), '<code>' . $this->misc->relative_path( $destination ) . '</code>' ) );
		$this->options->update_option( 'custom_pdf_template_files_installed', true );

		return true;
	}


	/**
	 * Removes the current font's TTF files from our font directory
	 *
	 * @param  array $fonts The font config
	 *
	 * @return boolean        True on success, false on failure
	 *
	 * @since  4.0
	 */
	public function remove_font_file( $fonts ) {

		$fonts = array_filter( $fonts );
		$types = [ 'regular', 'bold', 'italics', 'bolditalics' ];

		foreach ( $types as $type ) {
			if ( isset( $fonts[ $type ] ) ) {
				$filename = basename( $fonts[ $type ] );

				if ( is_file( $this->data->template_font_location . $filename ) && ! unlink( $this->data->template_font_location . $filename ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Check that the font name passed conforms to our expected nameing convesion
	 *
	 * @param  string $name The font name to check
	 *
	 * @return boolean       True on valid, false on failure
	 *
	 * @since 4.0
	 */
	public function is_font_name_valid( $name ) {

		$regex = '^[A-Za-z0-9 ]+$';

		if ( preg_match( "/$regex/", $name ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Query our custom fonts options table and check if the font name already exists
	 *
	 * @param  string    $name The font name to check
	 * @param int|string $id   The configuration ID (if any)
	 *
	 * @return bool True if valid, false on failure
	 *
	 * @since 4.0
	 */
	public function is_font_name_unique( $name, $id = '' ) {

		/* Get the shortname of the current font */
		$name = $this->options->get_font_short_name( $name );

		/* Loop through default fonts and check for duplicate */
		$default_fonts = $this->options->get_installed_fonts();

		unset( $default_fonts[ esc_html__( 'User-Defined Fonts', 'gravity-forms-pdf-extended' ) ] );

		/* check for exact match */
		foreach ( $default_fonts as $group ) {
			if ( isset( $group[ $name ] ) ) {
				return false;
			}
		}

		$custom_fonts = $this->options->get_option( 'custom_fonts' );

		if ( is_array( $custom_fonts ) ) {
			foreach ( $custom_fonts as $font ) {

				/* Skip over itself */
				if ( ! empty( $id ) && $font['id'] === $id ) {
					continue;
				}

				if ( $name === $this->options->get_font_short_name( $font['font_name'] ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Handles the database updates required to save a new font
	 *
	 * @param  array $fonts
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function install_fonts( $fonts ) {

		$types  = [ 'regular', 'bold', 'italics', 'bolditalics' ];
		$errors = [];

		foreach ( $types as $type ) {

			/* Check if a key exists for this type and process */
			if ( isset( $fonts[ $type ] ) ) {
				$path = $this->misc->convert_url_to_path( $fonts[ $type ] );

				/* Couldn't find file so throw error */
				if ( is_wp_error( $path ) ) {
					$errors[] = sprintf( esc_html__( 'Could not locate font on web server: %s', 'gravity-forms-pdf-extended' ), $fonts[ $type ] );
				}

				/* Copy font to our fonts folder */
				$filename = basename( $path );
				if ( ! is_file( $this->data->template_font_location . $filename ) && ! copy( $path, $this->data->template_font_location . $filename ) ) {
					$errors[] = sprintf( esc_html__( 'There was a problem installing the font %s. Please try again.', 'gravity-forms-pdf-extended' ), $filename );
				}
			}
		}

		/* If errors were found then return */
		if ( sizeof( $errors ) > 0 ) {
			$this->log->error(
				'Install Error.',
				[
					'errors' => $errors,
				]
			);

			return [ 'errors' => $errors ];
		} else {
			/* Insert our font into the database */
			$custom_fonts = $this->options->get_option( 'custom_fonts' );

			/* Prepare our font data and give it a unique id */
			if ( empty( $fonts['id'] ) ) {
				$id          = uniqid();
				$fonts['id'] = $id;
			}

			$custom_fonts[ $fonts['id'] ] = $fonts;

			/* Update our font database */
			$this->options->update_option( 'custom_fonts', $custom_fonts );

			/* Cleanup the mPDF tmp directory to prevent font caching issues  */
			$this->misc->cleanup_dir( $this->data->mpdf_tmp_location );

		}

		/* Fonts sucessfully installed so return font data */

		return $fonts;
	}

	/**
	 * Turn capabilities into more friendly strings
	 *
	 * @param  string $cap The wordpress-style capability
	 *
	 * @return string
	 *
	 * @since 4.0
	 */
	public function style_capabilities( $cap ) {
		$cap = str_replace( 'gravityforms', 'gravity_forms', $cap );
		$cap = str_replace( '_', ' ', $cap );
		$cap = ucwords( $cap );

		return $cap;
	}

	/**
	 * AJAX Endpoint for saving the custom font
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function save_font() {

		/* User / CORS validation */
		$this->misc->handle_ajax_authentication( 'Save Font', 'gravityforms_edit_settings', 'gfpdf_font_nonce' );

		/* Handle the validation and saving of the font */
		$payload = isset( $_POST['payload'] ) ? $_POST['payload'] : '';
		$results = $this->process_font( $payload );

		/* If we reached this point the results were successful so return the new object */
		$this->log->notice(
			'AJAX – Successfully Saved Font',
			[
				'results' => $results,
			]
		);

		echo json_encode( $results );
		wp_die();
	}

	/**
	 * AJAX Endpoint for deleting a custom font
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function delete_font() {

		/* User / CORS validation */
		$this->misc->handle_ajax_authentication( 'Delete Font', 'gravityforms_edit_settings', 'gfpdf_font_nonce' );

		/* Get the required details for deleting fonts */
		$id    = ( isset( $_POST['id'] ) ) ? $_POST['id'] : '';
		$fonts = $this->options->get_option( 'custom_fonts' );

		/* Check font actually exists and remove */
		if ( isset( $fonts[ $id ] ) ) {

			if ( $this->remove_font_file( $fonts[ $id ] ) ) {
				unset( $fonts[ $id ] );

				/* Cleanup the mPDF tmp directory to prevent font caching issues  */
				$this->misc->cleanup_dir( $this->data->mpdf_tmp_location );

				if ( $this->options->update_option( 'custom_fonts', $fonts ) ) {
					/* Success */
					$this->log->notice( 'AJAX – Successfully Deleted Font' );
					echo json_encode( [ 'success' => true ] );
					wp_die();
				}
			}
		}

		$return = [
			'error' => esc_html__( 'Could not delete Gravity PDF font correctly. Please try again.', 'gravity-forms-pdf-extended' ),
		];

		$this->log->error( 'AJAX Endpoint Error', $return );

		echo json_encode( $return );

		/* Bad Request */
		wp_die( '', 400 );
	}

	/**
	 * Validate user input and save as new font
	 *
	 * @param  array $font The four font fields to be processed
	 *
	 * @return array
	 *
	 * @since 4.0
	 */
	public function process_font( $font ) {

		/* remove any empty fields */
		$font = array_filter( $font );

		/* Check we have the required data */
		if ( ! isset( $font['font_name'] ) || ! isset( $font['regular'] ) ||
			 strlen( $font['font_name'] ) === 0 || strlen( $font['regular'] ) === 0
		) {

			$return = [
				'error' => esc_html__( 'Required fields have not been included.', 'gravity-forms-pdf-extended' ),
			];

			$this->log->warning( 'Font Validation Failed', $return );

			echo json_encode( $return );

			/* Bad Request */
			wp_die( '', 400 );
		}

		/* Check we have a valid font name */
		$name = $font['font_name'];

		if ( ! $this->is_font_name_valid( $name ) ) {

			$return = [
				'error' => esc_html__( 'Font name is not valid. Only alphanumeric characters and spaces are accepted.', 'gravity-forms-pdf-extended' ),
			];

			$this->log->warning( 'Font Validation Failed', $return );

			echo json_encode( $return );

			/* Bad Request */
			wp_die( '', 400 );
		}

		/* Check the font name is unique */
		$shortname = $this->options->get_font_short_name( $name );
		$id        = ( isset( $font['id'] ) ) ? $font['id'] : '';

		if ( ! $this->is_font_name_unique( $shortname, $id ) ) {

			$return = [
				'error' => esc_html__( 'A font with the same name already exists. Try a different name.', 'gravity-forms-pdf-extended' ),
			];

			$this->log->warning( 'Font Validation Failed', $return );

			echo json_encode( $return );

			/* Bad Request */
			wp_die( '', 400 );
		}

		/* Move fonts to our Gravity PDF font folder */
		$installation = $this->install_fonts( $font );

		/* Check if any errors occured installing the fonts */
		if ( isset( $installation['errors'] ) ) {

			$return = [
				'error' => $installation,
			];

			$this->log->warning( 'Font Validation Failed', $return );

			echo json_encode( $return );

			/* Bad Request */
			wp_die( '', 400 );
		}

		/* If we got here the installation was successful so return the data */
		return $installation;
	}

	/**
	 * Find the font unique ID from the font name
	 *
	 * @param string $font_name
	 *
	 * @return string The font ID, if any
	 *
	 * @since 4.1
	 */
	public function get_font_id_by_name( $font_name ) {
		$fonts = $this->options->get_option( 'custom_fonts', [] );

		foreach ( $fonts as $id => $font ) {
			if ( $font['font_name'] === $font_name ) {
				return $id;
			}
		}

		return null;
	}

	/**
	 * Create a file in our tmp directory and check if it is publically accessible (i.e no .htaccess protection)
	 *
	 * @param $_POST ['nonce']
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function check_tmp_pdf_security() {

		/* User / CORS validation */
		$this->misc->handle_ajax_authentication( 'Check Tmp Directory', 'gravityforms_view_settings', 'gfpdf-direct-pdf-protection' );

		/* Create our tmp file and do our actual check */
		echo json_encode( $this->test_public_tmp_directory_access() );
		wp_die();
	}

	/**
	 * Create a file in our tmp directory and verify if it's protected from the public
	 *
	 * @return boolean
	 *
	 * @since 4.0
	 */
	public function test_public_tmp_directory_access() {
		$tmp_dir       = $this->data->template_tmp_location;
		$tmp_test_file = 'public_tmp_directory_test.txt';
		$return        = true;

		/* create our file */
		file_put_contents( $tmp_dir . $tmp_test_file, 'failed-if-read' );

		/* verify it exists */
		if ( is_file( $tmp_dir . $tmp_test_file ) ) {

			/* Run our test */
			$site_url = $this->misc->convert_path_to_url( $tmp_dir );

			if ( $site_url !== false ) {

				$response = wp_remote_get( $site_url . $tmp_test_file );

				if ( ! is_wp_error( $response ) ) {

					/* Check if the web server responded with a OK status code and we can read the contents of our file, then fail our test */
					if ( isset( $response['response']['code'] ) && $response['response']['code'] === 200 &&
						 isset( $response['body'] ) && $response['body'] === 'failed-if-read'
					) {
						$response_object = $response['http_response'];
						$raw_response    = $response_object->get_response_object();
						$this->log->warning(
							'PDF temporary directory not protected',
							[
								'url'         => $raw_response->url,
								'status_code' => $raw_response->status_code,
								'response'    => $raw_response->raw,
							]
						);

						$return = false;
					}
				}
			}
		}

		/* Cleanup our test file */
		@unlink( $tmp_dir . $tmp_test_file );

		return $return;
	}

	/**
	 * Gets all the template information for use with our JS template selector
	 *
	 * @param array $strings
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function get_template_data( $strings ) {
		$strings['templateList']          = $this->templates->get_all_template_info();
		$strings['activeDefaultTemplate'] = $this->options->get_option( 'default_template' );

		$form_id = rgget( 'id' );

		if ( $form_id ) {
			$pid = ( rgget( 'pid' ) ) ? rgget( 'pid' ) : false;
			if ( $pid === false ) {
				$pid = ( rgpost( 'gform_pdf_id' ) ) ? rgpost( 'gform_pdf_id' ) : false;
			}

			$pdf = $this->options->get_pdf( $form_id, $pid );

			if ( ! is_wp_error( $pdf ) ) {
				$strings['activeTemplate'] = $pdf['template'];
			}
		}

		return $strings;
	}

	/**
	 * Include License fields in the PDF Settings for each registered add-on
	 *
	 * @param array $fields The licensing fields
	 *
	 * @return array
	 *
	 * @since 4.2
	 */
	public function register_addons_for_licensing( $fields ) {

		foreach ( $this->data->addon as $addon ) {
			$fields[ 'license_' . $addon->get_slug() ] = [
				'id'   => 'license_' . $addon->get_slug(),
				'name' => $addon->get_short_name(),
				'type' => 'license',
			];

			$fields[ 'license_' . $addon->get_slug() . '_message' ] = [
				'id'    => 'license_' . $addon->get_slug() . '_message',
				'type'  => 'hidden',
				'class' => 'gfpdf-hidden',
			];

			$fields[ 'license_' . $addon->get_slug() . '_status' ] = [
				'id'    => 'license_' . $addon->get_slug() . '_status',
				'type'  => 'hidden',
				'class' => 'gfpdf-hidden',
			];
		}

		return $fields;
	}

	/**
	 * Check the current add-on license key status and do an API call if the status isn't already active and the
	 * license key has been included. Update special hidden "message" and "license" fields with API response
	 *
	 * @param array $input The $_POST data provided by the Settings API
	 *
	 * @return array
	 *
	 * @since 4.2
	 */
	public function maybe_active_licenses( $input ) {

		$settings = $this->options->get_settings();

		/* Check if we are submitting our settings and there's an active key */
		foreach ( $this->data->addon as $addon ) {
			$option_key = 'license_' . $addon->get_slug();

			/* Check this add-on key was submitted, it isn't the same as previously, or it's not active */
			if ( isset( $input[ $option_key ] )
				 && (
					 ( isset( $settings[ $option_key ] ) && $settings[ $option_key ] !== $input[ $option_key ] ) ||
					 $input[ $option_key . '_status' ] !== 'active'
				 )
			) {
				$results = $this->activate_license( $addon, $input[ $option_key ] );

				$input[ $option_key . '_message' ] = $results['message'];
				$input[ $option_key . '_status' ]  = $results['status'];
			}

			/* Check if the license key is now empty */
			if ( isset( $input[ $option_key ] ) && strlen( trim( $input[ $option_key ] ) ) === 0 ) {
				$input[ $option_key . '_message' ] = '';
				$input[ $option_key . '_status' ]  = '';
			}
		}

		return $input;
	}

	/**
	 * Do API call to GravityPDF.com to activate the current add-on license key
	 *
	 * @param Helper_Abstract_Addon $addon       The current add-on class (stored in $data->addon)
	 * @param string                $license_key The current license key for this add-on
	 *
	 * @return array The API response and license status
	 *
	 * @since 4.2
	 */
	protected function activate_license( Helper_Abstract_Addon $addon, $license_key ) {

		$response = wp_remote_post(
			$this->data->store_url,
			[
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => [
					'edd_action' => 'activate_license',
					'license'    => $license_key,
					'item_name'  => urlencode( $addon->get_short_name() ), // the name of our product in EDD
					'url'        => home_url(),
				],
			]
		);

		$possible_responses = $this->data->addon_license_responses( $addon->get_name() );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			$message = ( is_wp_error( $response ) ) ? $response->get_error_message() : $possible_responses['generic'];
			$status  = 'error';

			$this->log->error(
				'License activation failure',
				[
					'data' => $message,
				]
			);
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			$message      = '';
			$status       = 'active';

			if ( ! isset( $license_data->success ) || false === $license_data->success ) {
				$message = $possible_responses['generic'];
				$status  = 'error';

				if ( isset( $license_data->error ) && isset( $possible_responses[ $license_data->error ] ) ) {
					$message = $possible_responses[ $license_data->error ];
					$status  = $license_data->error;

					/* Include the expiry date if license expired */
					if ( $license_data->error === 'expired' ) {
						$message = sprintf( $message, date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) ) );
					}
				}

				$this->log->error(
					'License activation failure',
					[
						'data' => $license_data,
					]
				);
			}
		}

		return [
			'message' => $message,
			'status'  => $status,
		];
	}

	/**
	 * An AJAX endpoint for processing license deactivations
	 *
	 * @Internal Expected parameters include:
	 *           $_POST['addon_name']
	 *           $_POST['license']
	 *
	 * @since 4.2
	 */
	public function process_license_deactivation() {

		/* User / CORS validation */
		$this->misc->handle_ajax_authentication( 'Deactivate License', 'gravityforms_edit_settings', 'gfpdf_deactivate_license' );

		/* Get the required details */
		$addon_slug = ( isset( $_POST['addon_name'] ) ) ? $_POST['addon_name'] : '';
		$license    = ( isset( $_POST['license'] ) ) ? $_POST['license'] : '';
		$addon      = ( isset( $this->data->addon[ $addon_slug ] ) ) ? $this->data->addon[ $addon_slug ] : false;

		/* Check add-on currently installed */
		if ( ! empty( $addon ) ) {
			if ( $this->deactivate_license_key( $addon, $license ) ) {
				$this->log->notice( 'AJAX – Successfully Deactivated License' );
				echo json_encode(
					[
						'success' => esc_html__( 'License deactivated.', 'gravity-forms-pdf-extended' ),
					]
				);

				wp_die();
			} elseif ( $addon->schedule_license_check() ) {
				$license_info = $addon->get_license_info();

				echo json_encode(
					[
						'error' => $license_info['message'],
					]
				);

				wp_die();
			}
		}

		$this->log->error( 'AJAX Endpoint Error' );

		echo json_encode(
			[
				'error' => esc_html__( 'An error occurred during deactivation, please try again', 'gravity-forms-pdf-extended' ),
			]
		);

		wp_die();
	}

	/**
	 * Do API call to GravityPDF.com to deactivate add-on license
	 *
	 * @param Helper_Abstract_Addon $addon
	 * @param string                $license_key
	 *
	 * @return bool
	 *
	 * @since 4.2
	 */
	public function deactivate_license_key( Helper_Abstract_Addon $addon, $license_key ) {

		$response = wp_remote_post(
			$this->data->store_url,
			[
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => [
					'edd_action' => 'deactivate_license',
					'license'    => $license_key,
					'item_name'  => urlencode( $addon->get_short_name() ), // the name of our product in EDD
					'url'        => home_url(),
				],
			]
		);

		/* If API error exit early */
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		/* Get API response and check license is now deactivated */
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! isset( $license_data->license ) || $license_data->license !== 'deactivated' ) {
			return false;
		}

		/* Remove license data from database */
		$addon->delete_license_info();

		$this->log->notice(
			'License successfully deactivated',
			[
				'slug'    => $addon->get_slug(),
				'license' => $license_key,
			]
		);

		return true;
	}
}
