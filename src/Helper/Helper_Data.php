<?php

namespace GFPDF\Helper;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2025, Blue Liquid Designs
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
 * @property bool    $allow_url_fopen                 The current PHP allow_url_fopen ini setting status
 *
 */
class Helper_Data {

	/**
	 * @since 6.0
	 */
	public const REST_API_BASENAME = 'gravity-pdf/';

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
	 * @param string $name  Name of the property being interacted with
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
	 * @throws \Exception
	 *
	 * @since 4.0
	 */
	public function &__get( $name ) {

		/* Check if we actually have a key matching what was requested */
		if ( array_key_exists( $name, $this->data ) ) {
			/* key exists, so return */
			return $this->data[ $name ];
		}

		throw new \Exception( 'Could not find stored Gravity PDF data with matching name: ' . esc_html( $name ) );
	}

	/**
	 * PHP Magic Method __isset()
	 * Triggered when isset() or empty() is called on inaccessible properties
	 *
	 * @param string $name Name of the property being interacted with
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
	 * @param string $name Name of the property being interacted with
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
		$this->short_title = esc_html__( 'PDF', 'gravity-pdf' );
		$this->title       = esc_html__( 'Gravity PDF', 'gravity-pdf' );
		$this->slug        = 'PDF';
	}

	/**
	 * Set up addon array for use tracking active addons
	 *
	 * @since 3.8
	 */
	public function set_addon_details() {
		$this->store_url = GPDF_API_URL;
		$this->addon     = [];
	}

	/**
	 * Gravity PDF add-ons should register their details with this method so we can handle the licensing centrally
	 *
	 * @param Helper_Abstract_Addon $addon_class The plugin bootstrap class
	 *
	 * @since 4.2
	 */
	public function add_addon( Helper_Abstract_Addon $addon_class ) {
		$this->addon[ $addon_class->get_slug() ] = $addon_class;
	}

	/**
	 * Get the various responses for license key activations
	 *
	 * @param string $addon_name
	 *
	 * @return array
	 */
	public function addon_license_responses( $addon_name ) {
		return [
			'expired'             => sprintf( __( 'This license key expired on %%s. %1$sPlease renew your license to continue receiving updates and support%2$s.', 'gravity-pdf' ), '<a href="%s">', '</a>' ),
			'revoked'             => sprintf( __( 'This license key has been cancelled (most likely due to a refund request). %1$sPlease consider purchasing a new license%2$s.', 'gravity-pdf' ), '<a href="%s">', '</a>' ),
			'disabled'            => sprintf( __( 'This license key has been cancelled (most likely due to a refund request). %1$sPlease consider purchasing a new license%2$s.', 'gravity-pdf' ), '<a href="%s">', '</a>' ),
			'missing'             => __( 'This license key is invalid. Please check your key has been entered correctly.', 'gravity-pdf' ),
			'invalid'             => __( 'The license key is invalid. Please check your key has been entered correctly.', 'gravity-pdf' ),
			'site_inactive'       => __( 'Your license key is valid but does not match your current domain. This usually occurs if your domain URL changes. Please resave the settings to activate the license for this website.', 'gravity-pdf' ),
			'item_name_mismatch'  => sprintf( __( 'This license key is not valid for %s. Please check your key is for this product.', 'gravity-pdf' ), $addon_name ),
			'invalid_item_id'     => sprintf( __( 'This license key is not valid for %s. Please check your key is for this product.', 'gravity-pdf' ), $addon_name ),
			'no_activations_left' => sprintf( __( 'This license key has reached its activation limit. %1$sPlease upgrade your license to increase the site limit (you only pay the difference)%2$s.', 'gravity-pdf' ), '<a href="%s">', '</a>' ),
			'default'             => __( 'An error occurred, please try again.', 'gravity-pdf' ),
			'generic'             => __( 'An error occurred, please try again.', 'gravity-pdf' ),
		];
	}

	/**
	 * A key-value array to be used in a localized script call for our Gravity PDF javascript files
	 *
	 * @param Helper_Abstract_Options $options
	 * @param Helper_Abstract_Form    $gform
	 *
	 * @return array
	 *
	 * @since  4.0
	 */
	public function get_localised_script_data( Helper_Abstract_Options $options, Helper_Abstract_Form $gform ) {

		$custom_fonts      = array_values( $options->get_custom_fonts() );
		$user_data         = get_userdata( get_current_user_id() );
		$user_capabilities = is_object( $user_data ) ? $user_data->allcaps : [];
		$user_capabilities = is_super_admin() ? [ 'administrator' => true ] : $user_capabilities;

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_localised_script_array/ for more details about this filter */

		return apply_filters(
			'gfpdf_localised_script_array',
			[
				'ajaxUrl'                              => admin_url( 'admin-ajax.php' ),
				'ajaxNonce'                            => wp_create_nonce( 'gfpdf_ajax_nonce' ),
				'restUrl'                              => rest_url( 'gravity-pdf/v1/' ),
				'restNonce'                            => wp_create_nonce( 'wp_rest' ),
				'currentVersion'                       => PDF_EXTENDED_VERSION,
				'pdfWorkingDir'                        => PDF_TEMPLATE_LOCATION,
				'pluginUrl'                            => PDF_PLUGIN_URL,
				'pluginPath'                           => PDF_PLUGIN_DIR,
				'customFontData'                       => wp_json_encode( $custom_fonts ),
				'userCapabilities'                     => $user_capabilities,

				'spinnerUrl'                           => admin_url( 'images/spinner-2x.gif' ),
				'spinnerAlt'                           => esc_html__( 'Loading...', 'gravity-pdf' ),
				'continue'                             => esc_html__( 'Continue', 'gravity-pdf' ),
				'uninstall'                            => esc_html__( 'Uninstall', 'gravity-pdf' ),
				'cancel'                               => esc_html__( 'Cancel', 'gravity-pdf' ),
				'delete'                               => esc_html__( 'Delete', 'gravity-pdf' ),
				'active'                               => esc_html__( 'Active', 'gravity-pdf' ),
				'inactive'                             => esc_html__( 'Inactive', 'gravity-pdf' ),
				'conditionalText'                      => esc_html__( 'this PDF if', 'gravity-pdf' ),
				'enable'                               => esc_html__( 'Enable', 'gravity-pdf' ),
				'disable'                              => esc_html__( 'Disable', 'gravity-pdf' ),
				'updateSuccess'                        => esc_html__( 'Successfully Updated', 'gravity-pdf' ),
				'deleteSuccess'                        => esc_html__( 'Successfully Deleted', 'gravity-pdf' ),
				'no'                                   => esc_html__( 'No', 'gravity-pdf' ),
				'yes'                                  => esc_html__( 'Yes', 'gravity-pdf' ),
				'standard'                             => esc_html__( 'Standard', 'gravity-pdf' ),
				'advanced'                             => esc_html__( 'Advanced', 'gravity-pdf' ),
				'manage'                               => esc_html__( 'Manage', 'gravity-pdf' ),
				'details'                              => esc_html__( 'Details', 'gravity-pdf' ),
				'select'                               => esc_html__( 'Select', 'gravity-pdf' ),
				'version'                              => esc_html__( 'Version', 'gravity-pdf' ),
				'group'                                => esc_html__( 'Group', 'gravity-pdf' ),
				'tags'                                 => esc_html__( 'Tags', 'gravity-pdf' ),

				'template'                             => esc_html__( 'Template', 'gravity-pdf' ),
				'manageTemplates'                      => esc_html__( 'Manage PDF Templates', 'gravity-pdf' ),
				'addNewTemplate'                       => esc_html__( 'Add New Template', 'gravity-pdf' ),
				'thisFormHasNoPdfs'                    => esc_html__( "This form doesn't have any PDFs.", 'gravity-pdf' ),
				'letsGoCreateOne'                      => esc_html__( "Let's go create one", 'gravity-pdf' ),
				'installedPdfs'                        => esc_html__( 'Installed PDFs', 'gravity-pdf' ),
				'searchTemplatePlaceholder'            => esc_html__( 'Search Installed Templates', 'gravity-pdf' ),

				'searchPlaceholder'                    => esc_html__( 'Search the Gravity PDF Knowledgebase...', 'gravity-pdf' ),
				'searchResultHeadingText'              => esc_html__( 'Gravity PDF Documentation', 'gravity-pdf' ),
				'noResultText'                         => esc_html__( 'It doesn\'t look like there are any topics related to your issue.', 'gravity-pdf' ),
				'getSearchResultError'                 => esc_html__( 'An error occurred. Please try again', 'gravity-pdf' ),

				'requiresGravityPdfVersion'            => esc_html__( 'Requires Gravity PDF v%s', 'gravity-pdf' ),
				'templateNotCompatibleWithGravityPdfVersion' => esc_html__( 'This PDF template is not compatible with your version of Gravity PDF. This template required Gravity PDF v%s.', 'gravity-pdf' ),
				'templateDetails'                      => esc_html__( 'Template Details', 'gravity-pdf' ),
				'currentTemplate'                      => esc_html__( 'Current Template', 'gravity-pdf' ),
				'showPreviousTemplate'                 => esc_html__( 'Show previous template', 'gravity-pdf' ),
				'showNextTemplate'                     => esc_html__( 'Show next template', 'gravity-pdf' ),
				'uploadInvalidNotZipFile'              => esc_html__( 'Upload is not a valid template. Upload a .zip file.', 'gravity-pdf' ),
				'uploadInvalidExceedsFileSizeLimit'    => esc_html__( 'Upload exceeds the 10MB limit.', 'gravity-pdf' ),
				'templateSuccessfullyInstalled'        => esc_html__( 'Template successfully installed', 'gravity-pdf' ),
				'templateSuccessfullyUpdated'          => esc_html__( 'Template successfully updated', 'gravity-pdf' ),
				'templateSuccessfullyInstalledUpdated' => esc_html__( 'PDF Template(s) Successfully Installed / Updated', 'gravity-pdf' ),
				'problemWithTheUpload'                 => esc_html__( 'There was a problem with the upload. Reload the page and try again.', 'gravity-pdf' ),
				'doYouWantToDeleteTemplate'            => sprintf( esc_html__( "Do you really want to delete this PDF template?%sClick 'Cancel' to go back, 'OK' to confirm the delete.", 'gravity-pdf' ), "\n\n" ),
				'couldNotDeleteTemplate'               => esc_html__( 'Could not delete template.', 'gravity-pdf' ),
				'templateInstallInstructions'          => esc_html__( 'If you have a PDF template in .zip format you may install it here. You can also update an existing PDF template (this will override any changes you have made).', 'gravity-pdf' ),

				'coreFontSuccess'                      => esc_html__( 'ALL CORE FONTS SUCCESSFULLY INSTALLED', 'gravity-pdf' ),
				'coreFontError'                        => esc_html__( '%s CORE FONT(S) DID NOT INSTALL CORRECTLY', 'gravity-pdf' ),
				'coreFontGithubError'                  => esc_html__( 'Could not download Core Font list. Try again.', 'gravity-pdf' ),
				'coreFontItemPendingMessage'           => esc_html__( 'Downloading %s...', 'gravity-pdf' ),
				'coreFontItemSuccessMessage'           => esc_html__( 'Completed installation of %s', 'gravity-pdf' ),
				'coreFontItemErrorMessage'             => esc_html__( 'Failed installation of %s', 'gravity-pdf' ),
				'coreFontCounter'                      => esc_html__( 'Fonts remaining:', 'gravity-pdf' ),
				'coreFontRetry'                        => esc_html__( 'Retry Failed Downloads?', 'gravity-pdf' ),
				'coreFontAriaLabel'                    => esc_html__( 'Core font installation', 'gravity-pdf' ),

				/* Font Manager */
				'fontManagerSearchPlaceHolder'         => esc_html__( 'Search installed fonts', 'gravity-pdf' ),
				'fontListInstalledFonts'               => esc_html__( 'Installed Fonts', 'gravity-pdf' ),
				'fontListRegular'                      => esc_html__( 'Regular', 'gravity-pdf' ),
				'fontListRegularRequired'              => esc_html__( 'Regular %1$s(required)%2$s', 'gravity-pdf' ),
				'fontListItalics'                      => esc_html__( 'Italics', 'gravity-pdf' ),
				'fontListBold'                         => esc_html__( 'Bold', 'gravity-pdf' ),
				'fontListBoldItalics'                  => esc_html__( 'Bold Italics', 'gravity-pdf' ),
				'fontManagerAddTitle'                  => esc_html__( 'Add Font', 'gravity-pdf' ),
				'fontManagerUpdateTitle'               => esc_html__( 'Update Font', 'gravity-pdf' ),
				'fontManagerAddDesc'                   => esc_html__( 'Install new fonts for use in your PDF documents.', 'gravity-pdf' ),
				'fontManagerUpdateDesc'                => esc_html__( 'Once saved, PDFs configured to use this font will have your changes applied automatically for newly-generated documents.', 'gravity-pdf' ),
				'fontManagerFontNameLabel'             => esc_html__( 'Font Name %1$s(required)%2$s', 'gravity-pdf' ),
				'fontManagerFontNameDesc'              => esc_html__( 'The font name can only contain letters, numbers and spaces.', 'gravity-pdf' ),
				'fontManagerFontNameValidationError'   => esc_html__( 'Please choose a name contains letters and/or numbers (and a space if you want it).', 'gravity-pdf' ),
				'fontManagerFontFilesLabel'            => esc_html__( 'Font Files', 'gravity-pdf' ),
				'fontManagerFontFilesDesc'             => esc_html__( 'Select or drag and drop your .ttf font file for the variants below. Only the Regular type is required.', 'gravity-pdf' ),
				'fontManagerFontFileRequiredRegular'   => esc_html__( 'Add a .ttf font file.', 'gravity-pdf' ),
				'fontManagerTemplateTooltipLabel'      => esc_html__( 'View template usage', 'gravity-pdf' ),
				'fontManagerCancelButtonText'          => esc_html__( '← Cancel', 'gravity-pdf' ),
				'fontManagerDeleteFontConfirmation'    => esc_html__( 'Are you sure you want to delete this font?', 'gravity-pdf' ),
				'fontManagerTemplateTooltipDesc'       => esc_html__( 'Add this snippet %1$sin a custom template%3$s to selectively set the font on blocks of text. If you want to apply the font to the entire PDF, %2$suse the Font setting%3$s when configuring the PDF on the form.', 'gravity-pdf' ),
				'fontManagerUpdateFontAriaLabel'       => esc_html__( 'Update font', 'gravity-pdf' ),
				'fontManagerSelectFontAriaLabel'       => esc_html__( 'Select font', 'gravity-pdf' ),
				'fontManagerDeleteFontAriaLabel'       => esc_html__( 'Delete font', 'gravity-pdf' ),

				/* Font Manager API response */
				'fontListEmpty'                        => esc_html__( 'Font list empty.', 'gravity-pdf' ),
				'searchResultEmpty'                    => esc_html__( 'No fonts matching your search found.', 'gravity-pdf' ),
				'searchClear'                          => esc_html__( 'Clear Search.', 'gravity-pdf' ),
				'addUpdateFontSuccess'                 => esc_html__( 'Your font has been saved.', 'gravity-pdf' ),
				'addUpdateFontError'                   => esc_html__( '%1$sThe action could not be completed.%2$s Resolve the highlighted issues above and then try again.', 'gravity-pdf' ),
				'addFatalError'                        => esc_html__( 'A problem occurred. Reload the page and try again.', 'gravity-pdf' ),
				'fontFileMissing'                      => esc_html__( '%1$sFont file(s) missing from the server.%2$s Please upload the font(s) again and then save.', 'gravity-pdf' ),
				'fontFileInvalid'                      => esc_html__( '%1$sFont file(s) are malformed%2$s and cannot be used with Gravity PDF.', 'gravity-pdf' ),

				'uninstallWarning'                     => esc_html__( "Warning! ALL Gravity PDF data, including templates, will be deleted. This cannot be undone. 'OK' to delete, 'Cancel' to stop.", 'gravity-pdf' ),
				'pdfDeleteWarning'                     => esc_html__( "WARNING: You are about to delete this PDF. 'Cancel' to stop, 'OK' to delete.", 'gravity-pdf' ),

				/* Help page */
				'searchBoxPlaceHolderText'             => esc_html__( 'Search the Gravity PDF Documentation...', 'gravity-pdf' ),
				'searchBoxSubmitTitle'                 => esc_html__( 'Submit your search query.', 'gravity-pdf' ),
				'searchBoxResetTitle'                  => esc_html__( 'Clear your search query.', 'gravity-pdf' ),
			]
		);
	}

	/**
	 * Get extra conditional logic options
	 * Props: Gravity Wiz
	 *
	 * @param array $form
	 *
	 * @return array
	 *
	 * @since 6.9.0
	 *
	 * @link https://github.com/gravitywiz/snippet-library/blob/master/gravity-forms/gw-conditional-logic-entry-meta.php
	 */
	public function get_conditional_logic_options( $form ): array {

		$options = [
			'id'             => [
				'label'     => esc_html__( 'Entry ID', 'gravityforms' ),
				'value'     => 'id',
				'operators' => [
					'is'    => 'is',
					'isnot' => 'isNot',
					'>'     => 'greaterThan',
					'<'     => 'lessThan',
				],
			],

			'status'         => [
				'label'     => esc_html__( 'Status', 'gravityforms' ),
				'value'     => 'status',
				'operators' => [
					'is'    => 'is',
					'isnot' => 'isNot',
				],
				'choices'   => [
					[
						'text'  => 'Active',
						'value' => 'active',
					],
					[
						'text'  => 'Spam',
						'value' => 'spam',
					],
					[
						'text'  => 'Trash',
						'value' => 'trash',
					],
				],
			],

			'date_created'   => [
				'label'       => esc_html__( 'Entry Date', 'gravityforms' ),
				'value'       => 'date_created',
				'operators'   => [
					'is'          => 'is',
					'isnot'       => 'isNot',
					'>'           => 'greaterThan',
					'<'           => 'lessThan',
					'contains'    => 'contains',
					'starts_with' => 'startsWith',
					'ends_with'   => 'endsWith',
				],
				'placeholder' => __( 'yyyy-mm-dd', 'gravityforms' ),
			],

			'is_starred'     => [
				'label'     => esc_html__( 'Starred', 'gravityforms' ),
				'value'     => 'is_starred',
				'operators' => [
					'is'    => 'is',
					'isnot' => 'isNot',
				],
				'choices'   => [
					[
						'text'  => 'Yes',
						'value' => '1',
					],
					[
						'text'  => 'No',
						'value' => '0',
					],
				],
			],

			'ip'             => [
				'label'     => esc_html__( 'IP Address', 'gravityforms' ),
				'value'     => 'ip',
				'operators' => [
					'is'          => 'is',
					'isnot'       => 'isNot',
					'contains'    => 'contains',
					'starts_with' => 'startsWith',
					'ends_with'   => 'endsWith',
				],
			],

			'source_url'     => [
				'label'     => esc_html__( 'Source URL', 'gravityforms' ),
				'value'     => 'source_url',
				'operators' => [
					'is'          => 'is',
					'isnot'       => 'isNot',
					'contains'    => 'contains',
					'starts_with' => 'startsWith',
					'ends_with'   => 'endsWith',
				],
			],

			'payment_status' => [
				'label'     => esc_html__( 'Payment Status', 'gravityforms' ),
				'value'     => 'payment_status',
				'operators' => [
					'is'    => 'is',
					'isnot' => 'isNot',
				],
				'choices'   => \GFCommon::get_entry_payment_statuses_as_choices(),
			],

			'payment_date'   => [
				'label'       => esc_html__( 'Payment Date', 'gravityforms' ),
				'value'       => 'payment_date',
				'operators'   => [
					'is'          => 'is',
					'isnot'       => 'isNot',
					'>'           => 'greaterThan',
					'<'           => 'lessThan',
					'contains'    => 'contains',
					'starts_with' => 'startsWith',
					'ends_with'   => 'endsWith',
				],
				'placeholder' => __( 'yyyy-mm-dd', 'gravityforms' ),
			],

			'payment_amount' => [
				'label'       => esc_html__( 'Payment Amount', 'gravityforms' ),
				'value'       => 'payment_amount',
				'operators'   => [
					'is'          => 'is',
					'isnot'       => 'isNot',
					'>'           => 'greaterThan',
					'<'           => 'lessThan',
					'contains'    => 'contains',
					'starts_with' => 'startsWith',
					'ends_with'   => 'endsWith',
				],
				'placeholder' => '0.00',
			],
		];

		/* Handle Entry Meta */
		$entry_meta = \GFFormsModel::get_entry_meta( $form['id'] );

		$choices_by_key = [
			'is_approved' => [
				1 => esc_html__( 'Approved', 'gravity-pdf' ),
				2 => esc_html__( 'Disapproved', 'gravity-pdf' ),
				3 => esc_html__( 'Unapproved', 'gravity-pdf' ),
			],
		];

		foreach ( $entry_meta as $key => $meta ) {
			/* Skip entry meta already registered */
			if ( isset( $options[ $key ] ) ) {
				continue;
			}

			$options[ $key ] = [
				'label'     => $meta['label'],
				'value'     => $key,
				'operators' => [
					'is'    => 'is',
					'isnot' => 'isNot',
				],
			];

			$_choices = rgar( $choices_by_key, $key );

			if ( ! empty( $_choices ) ) {
				$choices = [];
				foreach ( $_choices as $value => $text ) {
					$choices[] = compact( 'text', 'value' );
				}

				$options[ $key ]['choices'] = $choices;
			}
		}

		/* Gravity Wiz Unique ID perk */
		$post_submission_conditional_logic_field_types = [
			'uid' => [
				'operators' => [
					'is'          => 'is',
					'isnot'       => 'isNot',
					'>'           => 'greaterThan',
					'<'           => 'lessThan',
					'contains'    => 'contains',
					'starts_with' => 'startsWith',
					'ends_with'   => 'endsWith',
				],
			],
		];

		$fields = \GFAPI::get_fields_by_type( $form, array_keys( $post_submission_conditional_logic_field_types ) );

		foreach ( $fields as $field ) {
			$options[ $field->id ] = [
				'label'     => $field->label,
				'value'     => $field->id,
				'operators' => rgars( $post_submission_conditional_logic_field_types, $field->type . '/operators', [] ),
			];
		}

		return $options;
	}
}
