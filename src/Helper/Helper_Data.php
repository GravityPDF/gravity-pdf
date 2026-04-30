<?php

namespace GFPDF\Helper;

use GFPDF\Helper\Licensing\EDD_SL_Plugin_Updater;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
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
 * @property EDD_SL_Plugin_Updater $updater           The core plugin update class
 * @property array<Helper_Abstract_Addon> $addon      An array of current active / registered add-ons
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
			'active'              => __( 'Your support license key has been activated for this domain.', 'gravity-pdf' ),
			'valid'               => __( 'Your support license key has been activated for this domain.', 'gravity-pdf' ),
			/* translators: 1: Opening <a> tag, 2: Closing </a> tag. Note: %%s is a placeholder for the expiry date filled in later. */
			'expired'             => sprintf( __( 'This license key expired on %%s. %1$sPlease renew your license to continue receiving updates and support%2$s.', 'gravity-pdf' ), '<a href="%s">', '</a>' ),
			/* translators: 1: Opening <a> tag, 2: Closing </a> tag */
			'revoked'             => sprintf( __( 'This license key has been cancelled (most likely due to a refund request). %1$sPlease consider purchasing a new license%2$s.', 'gravity-pdf' ), '<a href="%s">', '</a>' ),
			'disabled'            => sprintf( __( 'This license key has been cancelled (most likely due to a refund request). %1$sPlease consider purchasing a new license%2$s.', 'gravity-pdf' ), '<a href="%s">', '</a>' ),
			'missing'             => __( 'This license key is invalid. Please check your key has been entered correctly.', 'gravity-pdf' ),
			'invalid'             => __( 'The license key is invalid. Please check your key has been entered correctly.', 'gravity-pdf' ),
			'site_inactive'       => __( 'Your license key is valid but does not match your current domain. This usually occurs if your domain URL changes. Please resave the settings to activate the license for this website.', 'gravity-pdf' ),
			/* translators: %s: add-on name */
			'item_name_mismatch'  => sprintf( __( 'This license key is not valid for %s. Please check your key is for this product.', 'gravity-pdf' ), $addon_name ),
			/* translators: %s: add-on name */
			'invalid_item_id'     => sprintf( __( 'This license key is not valid for %s. Please check your key is for this product.', 'gravity-pdf' ), $addon_name ),
			/* translators: 1: Opening <a> tag, 2: Closing </a> tag */
			'no_activations_left' => sprintf( __( 'This license key has reached its activation limit. %1$sPlease upgrade your license to increase the site limit (you only pay the difference)%2$s.', 'gravity-pdf' ), '<a href="%s">', '</a>' ),
			'default'             => __( 'An unknown error occurred while checking the license.', 'gravity-pdf' ),
			'generic'             => __( 'An unknown error occurred while checking the license.', 'gravity-pdf' ),
			'error'               => __( 'An unknown error occurred while checking the license.', 'gravity-pdf' ),
			'rate_limit'          => __( 'The licensing server is temporarily unavailable.', 'gravity-pdf' ),
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

		$user_data         = get_userdata( get_current_user_id() );
		$user_capabilities = is_object( $user_data ) ? $user_data->allcaps : [];
		$user_capabilities = is_super_admin() ? [ 'administrator' => true ] : $user_capabilities;

		/* template_location_url is populated during bootstrap; fall back to
		   an empty string so this method remains safe on bare Helper_Data
		   instances (used by some unit tests). */
		$template_location_url_key = is_multisite() ? 'multisite_template_location_url' : 'template_location_url';
		$template_location_url     = isset( $this->$template_location_url_key ) ? $this->$template_location_url_key : '';

		/* See https://docs.gravitypdf.com/developers/filters/gfpdf_localised_script_array/ for more details about this filter */

		return apply_filters(
			'gfpdf_localised_script_array',
			[
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'ajaxNonce'         => wp_create_nonce( 'gfpdf_ajax_nonce' ),
				'currentVersion'    => PDF_EXTENDED_VERSION,
				'pdfWorkingDir'     => PDF_TEMPLATE_LOCATION,
				'pluginUrl'         => PDF_PLUGIN_URL,
				'userCapabilities'  => $user_capabilities,
				'spinnerUrl'        => admin_url( 'images/spinner-2x.gif' ),
				'customFontUrlBase' => trailingslashit( $template_location_url ) . 'fonts/',
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
