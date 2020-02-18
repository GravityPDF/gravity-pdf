<?php

namespace GFPDF\Helper;

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
 * @since 4.0
 *
 * @property string  $short_title                     The plugin's short title used with Gravity Forms
 * @property string  $title                           The plugin's main title used with Gravity Forms
 * @property string  $slug                            The plugin's slug used with Gravity Forms
 * @property boolean $is_installed                    If the plugin has been successfully installed
 * @property string  $permalink                       The plugin's PDF permalink regex
 * @property string  $working_folder                  The plugin's working directory name
 * @property string  $settings_url                    The plugin's URL to the settings page
 * @property string  $memory_limit                    The current PHP memory limit
 * @property string  $upload_dir                      The current path to the WP upload directory
 * @property string  $upload_dir_url                  The current URL to the WP upload directory
 * @property string  $store_url                       The URL of our online store
 * @property array   $form_settings                   A cache of the current form's PDF settings
 * @property array   $addon                           An array of current active / registered add-ons
 * @property string  $template_location               The current path to the PDF working directory
 * @property string  $template_location_url           The current URL to the PDF working directory
 * @property string  $template_font_location          The current path to the PDF font directory
 * @property string  $template_tmp_location           The current path to the PDF tmp location
 * @property string  $mpdf_tmp_location               The current path to the mPDF tmp directory (including fonts)
 * @property string  $multisite_template_location     The current path to the multisite PDF working directory
 * @property string  $multisite_template_location_url The current URL to the multisite PDF working directory
 * @property string  $template_transient_cache        The ID for the template header transient cache
 *
 */
class Helper_Data {

	/**
	 * Location for the overloaded data
	 *
	 * @var array
	 *
	 * @since 4.0
	 */
	private $data = [];

	/**
	 * PHP Magic Method __set()
	 * Run when writing data to inaccessible properties
	 *
	 * @param string $name  Name of the peroperty being interacted with
	 * @param mixed  $value Data to assign to the $name property
	 *
	 * @since 4.0
	 */
	public function __set( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	/**
	 * PHP Magic Method __get()
	 * Run when reading data from inaccessible properties
	 *
	 * @param string $name Name of the property being interacted with
	 *
	 * @return mixed        The data assigned to the $name property is returned
	 *
	 * @since 4.0
	 */
	public function &__get( $name ) {

		/* Check if we actually have a key matching what was requested */
		if ( array_key_exists( $name, $this->data ) ) {
			/* key exists, so return */
			return $this->data[ $name ];
		}

		/* phpcs:disable PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection */
		$trace = debug_backtrace();
		/* phpcs:enable */
		trigger_error(
			'Undefined property via __get(): ' . $name .
			' in ' . $trace[0]['file'] .
			' on line ' . $trace[0]['line'],
			E_USER_NOTICE
		);

		/* because we are returning by reference we need return something that can be referenced */
		$value = null;

		return $value;
	}

	/**
	 * PHP Magic Method __isset()
	 * Triggered when isset() or empty() is called on inaccessible properties
	 *
	 * @param  string $name Name of the property being interacted with
	 *
	 * @return boolean       Whether property exists
	 *
	 * @since 4.0
	 */
	public function __isset( $name ) {
		return isset( $this->data[ $name ] );
	}

	/**
	 * PHP Magic Method __isset()
	 * Triggered when unset() is called on inaccessible properties
	 *
	 * @param  string $name Name of the property being interacted with
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function __unset( $name ) {
		unset( $this->data[ $name ] );
	}

	/**
	 * Set up any default data that should be stored
	 *
	 * @return void
	 *
	 * @since 3.8
	 */
	public function init() {
		$this->set_plugin_titles();
		$this->set_addon_details();
	}

	/**
	 * Set up our short title, long title and slug used in settings pages
	 *
	 * @return  void
	 *
	 * @since  4.0
	 */
	public function set_plugin_titles() {
		$this->short_title = esc_html__( 'PDF', 'gravity-forms-pdf-extended' );
		$this->title       = esc_html__( 'Gravity PDF', 'gravity-forms-pdf-extended' );
		$this->slug        = 'pdf';
	}

	/**
	 * Set up addon array for use tracking active addons
	 *
	 * @since 3.8
	 */
	public function set_addon_details() {
		$this->store_url = 'https://gravitypdf.com?api=1';
		$this->addon     = [];
	}

	/**
	 * Gravity PDF add-ons should register their details with this method so we can handle the licensing centrally
	 *
	 * @param Helper_Abstract_Addon $class The plugin bootstrap class
	 *
	 * @since 4.2
	 */
	public function add_addon( Helper_Abstract_Addon $class ) {
		$this->addon[ $class->get_slug() ] = $class;
	}

	public function addon_license_responses( $addon_name ) {
		return [
			'expired'             => __( 'Your license key expired on %s.', 'gravity-forms-pdf-extended' ),
			'revoked'             => __( 'Your license key has been disabled', 'gravity-forms-pdf-extended' ),
			'missing'             => __( 'Invalid license key provided', 'gravity-forms-pdf-extended' ),
			'invalid'             => __( 'Your license is not active for this URL', 'gravity-forms-pdf-extended' ),
			'site_inactive'       => __( 'Your license is not active for this URL', 'gravity-forms-pdf-extended' ),
			'item_name_mismatch'  => sprintf( __( 'This appears to be an invalid license key for %s', 'gravity-forms-pdf-extended' ), $addon_name ),
			'no_activations_left' => __( 'Your license key has reached its activation limit', 'gravity-forms-pdf-extended' ),
			'default'             => __( 'An error occurred, please try again', 'gravity-forms-pdf-extended' ),
			'generic'             => __( 'An error occurred during activation, please try again', 'gravity-forms-pdf-extended' ),
		];
	}

	/**
	 * A key-value array to be used in a localized script call for our Gravity PDF javascript files
	 *
	 * @param \GFPDF\Helper\Helper_Abstract_Options $options
	 * @param \GFPDF\Helper\Helper_Abstract_Form    $gform
	 *
	 * @return array
	 *
	 * @since  4.0
	 */
	public function get_localised_script_data( Helper_Abstract_Options $options, Helper_Abstract_Form $gform ) {

		$custom_fonts = array_values( $options->get_custom_fonts() );
		$user_data    = get_userdata( get_current_user_id() );

		/* See https://gravitypdf.com/documentation/v5/gfpdf_localised_script_array/ for more details about this filter */

		return apply_filters(
			'gfpdf_localised_script_array',
			[
				'ajaxUrl'                              => admin_url( 'admin-ajax.php' ),
				'ajaxNonce'                            => wp_create_nonce( 'gfpdf_ajax_nonce' ),
				'currentVersion'                       => PDF_EXTENDED_VERSION,
				'pdfWorkingDir'                        => PDF_TEMPLATE_LOCATION,
				'pluginUrl'                            => PDF_PLUGIN_URL,
				'pluginPath'                           => PDF_PLUGIN_DIR,
				'customFontData'                       => json_encode( $custom_fonts ),
				'userCapabilities'                     => is_object( $user_data ) ? $user_data->allcaps : [],

				'spinnerUrl'                           => admin_url( 'images/spinner-2x.gif' ),
				'spinnerAlt'                           => esc_html__( 'Loading...', 'gravity-forms-pdf-extended' ),
				'continue'                             => esc_html__( 'Continue', 'gravity-forms-pdf-extended' ),
				'uninstall'                            => esc_html__( 'Uninstall', 'gravity-forms-pdf-extended' ),
				'cancel'                               => esc_html__( 'Cancel', 'gravity-forms-pdf-extended' ),
				'delete'                               => esc_html__( 'Delete', 'gravity-forms-pdf-extended' ),
				'active'                               => esc_html__( 'Active', 'gravity-forms-pdf-extended' ),
				'inactive'                             => esc_html__( 'Inactive', 'gravity-forms-pdf-extended' ),
				'conditionalText'                      => esc_html__( 'this PDF if', 'gravity-forms-pdf-extended' ),
				'enable'                               => esc_html__( 'Enable', 'gravity-forms-pdf-extended' ),
				'disable'                              => esc_html__( 'Disable', 'gravity-forms-pdf-extended' ),
				'updateSuccess'                        => esc_html__( 'Successfully Updated', 'gravity-forms-pdf-extended' ),
				'deleteSuccess'                        => esc_html__( 'Successfully Deleted', 'gravity-forms-pdf-extended' ),
				'no'                                   => esc_html__( 'No', 'gravity-forms-pdf-extended' ),
				'yes'                                  => esc_html__( 'Yes', 'gravity-forms-pdf-extended' ),
				'standard'                             => esc_html__( 'Standard', 'gravity-forms-pdf-extended' ),
				'advanced'                             => esc_html__( 'Advanced', 'gravity-forms-pdf-extended' ),
				'select'                               => esc_html__( 'Select', 'gravity-forms-pdf-extended' ),
				'version'                              => esc_html__( 'Version', 'gravity-forms-pdf-extended' ),
				'group'                                => esc_html__( 'Group', 'gravity-forms-pdf-extended' ),
				'tags'                                 => esc_html__( 'Tags', 'gravity-forms-pdf-extended' ),

				'migratingSite'                        => esc_html__( 'Migrating site #%s', 'gravity-forms-pdf-extended' ),
				'siteMigrationComplete'                => esc_html__( 'Site #%s migration complete.', 'gravity-forms-pdf-extended' ),
				'migrationError'                       => esc_html__( 'Migration Error', 'gravity-forms-pdf-extended' ),
				'siteMigrationErrors'                  => esc_html__( 'Site #%s migration errors.', 'gravity-forms-pdf-extended' ),

				'addNewTemplate'                       => esc_html__( 'Add New Template', 'gravity-forms-pdf-extended' ),
				'showAdvancedOptions'                  => esc_html__( 'Show Advanced Options...', 'gravity-forms-pdf-extended' ),
				'hideAdvancedOptions'                  => esc_html__( 'Hide Advanced Options...', 'gravity-forms-pdf-extended' ),
				'thisFormHasNoPdfs'                    => esc_html__( "This form doesn't have any PDFs.", 'gravity-forms-pdf-extended' ),
				'letsGoCreateOne'                      => esc_html__( "Let's go create one", 'gravity-forms-pdf-extended' ),
				'installedPdfs'                        => esc_html__( 'Installed PDFs', 'gravity-forms-pdf-extended' ),

				'searchPlaceholder'                    => esc_html__( 'Search the Gravity PDF Knowledgebase...', 'gravity-forms-pdf-extended' ),
				'searchResultHeadingText'              => esc_html__( 'Gravity PDF Documentation', 'gravity-forms-pdf-extended' ),
				'noResultText'                         => esc_html__( 'It doesn\'t look like there are any topics related to your issue.', 'gravity-forms-pdf-extended' ),
				'getSearchResultError'                 => esc_html__( 'An error occurred. Please try again', 'gravity-forms-pdf-extended' ),

				'requiresGravityPdfVersion'            => esc_html__( 'Requires Gravity PDF v%s', 'gravity-forms-pdf-extended' ),
				'templateNotCompatibleWithGravityPdfVersion' => esc_html__( 'This PDF template is not compatible with your version of Gravity PDF. This template required Gravity PDF v%s.', 'gravity-forms-pdf-extended' ),
				'templateDetails'                      => esc_html__( 'Template Details', 'gravity-forms-pdf-extended' ),
				'currentTemplate'                      => esc_html__( 'Current Template', 'gravity-forms-pdf-extended' ),
				'showPreviousTemplate'                 => esc_html__( 'Show previous template', 'gravity-forms-pdf-extended' ),
				'showNextTemplate'                     => esc_html__( 'Show next template', 'gravity-forms-pdf-extended' ),
				'uploadInvalidNotZipFile'              => esc_html__( 'Upload is not a valid template. Upload a .zip file.', 'gravity-forms-pdf-extended' ),
				'uploadInvalidExceedsFileSizeLimit'    => esc_html__( 'Upload exceeds the 10MB limit.', 'gravity-forms-pdf-extended' ),
				'templateSuccessfullyInstalled'        => esc_html__( 'Template successfully installed', 'gravity-forms-pdf-extended' ),
				'templateSuccessfullyUpdated'          => esc_html__( 'Template successfully updated', 'gravity-forms-pdf-extended' ),
				'templateSuccessfullyInstalledUpdated' => esc_html__( 'PDF Template(s) Successfully Installed / Updated', 'gravity-forms-pdf-extended' ),
				'problemWithTheUpload'                 => esc_html__( 'There was a problem with the upload. Reload the page and try again.', 'gravity-forms-pdf-extended' ),
				'doYouWantToDeleteTemplate'            => sprintf( esc_html__( "Do you really want to delete this PDF template?%sClick 'Cancel' to go back, 'OK' to confirm the delete.", 'gravity-forms-pdf-extended' ), "\n\n" ),
				'couldNotDeleteTemplate'               => esc_html__( 'Could not delete template.', 'gravity-forms-pdf-extended' ),
				'templateInstallInstructions'          => esc_html__( 'If you have a PDF template in .zip format you may install it here. You can also update an existing PDF template (this will override any changes you have made).', 'gravity-forms-pdf-extended' ),

				'coreFontSuccess'                      => esc_html__( 'ALL CORE FONTS SUCCESSFULLY INSTALLED', 'gravity-forms-pdf-extended' ),
				'coreFontError'                        => esc_html__( '%s CORE FONT(S) DID NOT INSTALL CORRECTLY', 'gravity-forms-pdf-extended' ),
				'coreFontGithubError'                  => esc_html__( 'Could not download Core Font list. Try again.', 'gravity-forms-pdf-extended' ),
				'coreFontItemPendingMessage'           => esc_html__( 'Downloading %s...', 'gravity-forms-pdf-extended' ),
				'coreFontItemSuccessMessage'           => esc_html__( 'Completed installation of %s', 'gravity-forms-pdf-extended' ),
				'coreFontItemErrorMessage'             => esc_html__( 'Failed installation of %s', 'gravity-forms-pdf-extended' ),
				'coreFontCounter'                      => esc_html__( 'Fonts remaining:', 'gravity-forms-pdf-extended' ),
				'coreFontRetry'                        => esc_html__( 'Retry Failed Downloads?', 'gravity-forms-pdf-extended' ),
			]
		);
	}
}
