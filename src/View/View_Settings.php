<?php

namespace GFPDF\View;

use GFPDF\Helper\Helper_Abstract_Form;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Abstract_View;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Form;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Options_Fields;
use GFPDF\Helper\Helper_Templates;
use GFPDF_Major_Compatibility_Checks;
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
 * View_Settings
 *
 * A general class for About / Intro Screen
 *
 * @since 4.0
 */
class View_Settings extends Helper_Abstract_View {

	/**
	 * Set the view's name
	 *
	 * @var string
	 *
	 * @since 4.0
	 */
	protected $view_type = 'Settings';

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
	 * Holds our Helper_Abstract_Options / Helper_Options_Fields object
	 * Makes it easy to access global PDF settings and individual form PDF settings
	 *
	 * @var Helper_Options_Fields
	 *
	 * @since 4.0
	 */
	protected $options;

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
	 * Holds our Helper_Misc object
	 * Makes it easy to access common methods throughout the plugin
	 *
	 * @var Helper_Misc
	 *
	 * @since 4.0
	 */
	protected $misc;

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
	 * @param array                            $data_cache An array of data to pass to the view
	 * @param Helper_Form|Helper_Abstract_Form $gform      Our abstracted Gravity Forms helper functions
	 * @param LoggerInterface                  $log        Our logger class
	 * @param Helper_Abstract_Options          $options    Our options class which allows us to access any settings
	 * @param Helper_Data                      $data       Our plugin data store
	 * @param Helper_Misc                      $misc       Our miscellaneous class
	 * @param Helper_Templates                 $templates
	 *
	 * @since 4.0
	 */
	public function __construct( array $data_cache, Helper_Abstract_Form $gform, LoggerInterface $log, Helper_Abstract_Options $options, Helper_Data $data, Helper_Misc $misc, Helper_Templates $templates ) {

		/* Call our parent constructor */
		parent::__construct( $data_cache );

		/* Assign our internal variables */
		$this->gform     = $gform;
		$this->log       = $log;
		$this->options   = $options;
		$this->data      = $data;
		$this->misc      = $misc;
		$this->templates = $templates;
	}

	/**
	 * Load the Welcome Tab tabs
	 *
	 * @return string
	 * @since 4.0
	 *
	 */
	public function tabs() {

		/* Set up any variables we need for the view and display */
		$vars = [
			'selected' => isset( $_GET['tab'] ) ? $_GET['tab'] : 'general',
			'tabs'     => $this->get_available_tabs(),
			'data'     => $this->data,
		];

		/* load the tabs view */
		return $this->load( 'tabs', $vars, false );
	}

	/**
	 * Set up our settings navigation
	 *
	 * @return array The navigation array
	 *
	 * @since 4.0
	 */
	public function get_available_tabs() {

		/* The array key is the settings order */
		$navigation = [
			5   => [
				'name' => esc_html__( 'Settings', 'gravity-forms-pdf-extended' ),
				'id'   => 'general',
			],

			100 => [
				'name' => esc_html__( 'Tools', 'gravity-forms-pdf-extended' ),
				'id'   => 'tools',
			],

			120 => [
				'name' => esc_html__( 'Help', 'gravity-forms-pdf-extended' ),
				'id'   => 'help',
			],
		];

		/* Add License tab if necessary */
		if ( count( $this->data->addon ) > 0 ) {
			$navigation[10] = [
				'name' => esc_html__( 'License', 'gravity-forms-pdf-extended' ),
				'id'   => 'license',
			];
		}

		/* Add Extensions tab if necessary */
		$settings = $this->options->get_registered_fields();
		if ( count( $settings['extensions'] ) > 0 ) {
			$navigation[20] = [
				'name' => esc_html__( 'Extensions', 'gravity-forms-pdf-extended' ),
				'id'   => 'extensions',
			];
		}

		/**
		 * Allow additional navigation to be added to the settings page
		 *
		 * @since 3.8
		 */
		$navigation = apply_filters( 'gfpdf_settings_navigation', $navigation );

		/* sort the navigation by the array key */
		ksort( $navigation, SORT_NUMERIC );

		return $navigation;
	}

	/**
	 * Pull the general details and display
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function general() {

		$markup = new View_GravityForm_Settings_Markup();

		$sections = [
			[
				'id'            => 'gfpdf_settings_general',
				'width'         => 'full',
				'title'         => __( 'Default PDF Options', 'gravity-forms-pdf-extended' ),
				'desc'          => __( 'Control the default settings to use when you create new PDFs on your forms.', 'gravity-forms-pdf-extended' ),
				'content'       => $markup->do_settings_fields( 'gfpdf_settings_general_defaults', $markup::ENABLE_PANEL_TITLE ),
				'content_class' => 'gform_settings_form',
			],
		];

		$sections = array_merge(
			$sections,
			$markup->do_settings_fields_as_individual_fieldset(
				'gfpdf_settings_general',
				[
					'default_action' => [
						'width' => 'full',
					],
				]
			)
		);

		$sections[] = [
			'id'            => 'gfpdf_settings_general_security',
			'width'         => 'full',
			'collapsible'   => true,
			'title'         => __( 'Security', 'gravity-forms-pdf-extended' ),
			'content'       => $markup->do_settings_fields( 'gfpdf_settings_general_security', $markup::ENABLE_PANEL_TITLE ),
			'content_class' => 'gform_settings_form',
		];

		$vars = [
			'edit_cap' => $this->gform->has_capability( 'gravityforms_edit_settings' ),
			'content'  => $markup->do_settings_sections( $sections ),
			'menu'     => $this->tabs(),
		];

		/* load the system status view */
		$this->load( 'general', $vars );
	}

	/**
	 * Pull the license details and displays
	 *
	 * @return void
	 *
	 * @since 4.2
	 */
	public function license() {

		$markup = new View_GravityForm_Settings_Markup();

		$sections = [
			[
				'id'      => 'gfpdf_settings_general_view',
				'width'   => 'full',
				'title'   => __( 'Licensing', 'gravity-forms-pdf-extended' ),
				'content' => $this->load( 'licence-info', [], false ),
				'menu'    => $this->tabs(),
			],
		];

		/* Group the common license settings together in the one container (every 3 settings) */
		$i = 1;
		foreach ( $markup->get_section_fields( 'gfpdf_settings_licenses' ) as $field ) {

			if ( empty( $args ) ) {
				$args = [
					'id'      => $field['args']['id'],
					'width'   => 'half',
					'title'   => $field['title'],
					'content' => $markup->get_field_content( $field, $markup::DISABLE_PANEL_TITLE ),
				];
			} else {
				$args['content'] .= $markup->get_field_content( $field, $markup::DISABLE_PANEL_TITLE );
			}

			if ( $i % 3 === 0 ) {
				$sections[] = $args;
				$args       = [];
			}

			$i++;
		}

		$vars = [
			'edit_cap' => $this->gform->has_capability( 'gravityforms_edit_settings' ),
			'content'  => $markup->do_settings_sections( $sections ),
			'menu'     => $this->tabs(),
		];

		/* load the system status view */
		$this->load( 'licence', $vars );
	}

	/**
	 * Display the extensions settings page
	 *
	 * @return void
	 *
	 * @since 4.2
	 */
	public function extensions() {
		$vars = [
			'edit_cap' => $this->gform->has_capability( 'gravityforms_edit_settings' ),
			'menu'     => $this->tabs(),
		];

		/* load the system status view */
		$this->load( 'extensions', $vars );
	}

	/**
	 * Display the help settings page
	 *
	 * @return void
	 *
	 * @since 6.0
	 */
	public function help() {
		$vars = [
			'edit_cap' => $this->gform->has_capability( 'gravityforms_edit_settings' ),
			'menu'     => $this->tabs(),
		];

		/* load the system status view */
		$this->load( 'help', $vars );
	}

	/**
	 * Pull the tools details and show
	 *
	 * @return void
	 *
	 * @since 4.0
	 */
	public function tools() {

		/* prevent unauthorized access */
		if ( ! $this->gform->has_capability( 'gravityforms_edit_settings' ) ) {
			$this->log->warning( 'Lack of User Capabilities.' );

			wp_die( esc_html__( 'You do not have permission to access this page', 'gravity-forms-pdf-extended' ) );
		}

		$markup             = new View_GravityForm_Settings_Markup();
		$template_directory = $this->templates->get_template_path();
		$sections           = $markup->do_settings_fields_as_individual_fieldset( 'gfpdf_settings_tools' );

		$vars = [
			'template_directory'            => $this->misc->relative_path( $template_directory, '/' ),
			'template_files'                => $this->templates->get_core_pdf_templates(),
			'custom_template_setup_warning' => $this->options->get_option( 'custom_pdf_template_files_installed' ),
			'content'                       => $markup->do_settings_sections( $sections ),
			'menu'                          => $this->tabs(),
		];

		/* load the system status view */
		$this->load( 'tools', $vars );
	}



	/**
	 * Add Gravity Forms Tooltips
	 *
	 * @param array $tooltips The existing tooltips
	 *
	 * @return string
	 * @since 4.0
	 *
	 */
	public function add_tooltips( $tooltips ) {

		$tooltips['pdf_status_wp_memory'] = '<h6>' . esc_html__( 'WP Memory Available', 'gravity-forms-pdf-extended' ) . '</h6>' . sprintf( esc_html__( 'Producing PDF documents is hard work and Gravity PDF requires more resources than most plugins. We strongly recommend you have at least 128MB, but you may need more.', 'gravity-forms-pdf-extended' ) );
		$tooltips['pdf_protection']       = '<h6>' . esc_html__( 'Direct PDF Protection', 'gravity-forms-pdf-extended' ) . '</h6>' . esc_html__( 'Your PDFs might be saved to a temporary directory that is publicly accessible. We will check if your PDFs are automatically protected, and let you know what you can do if they are not.', 'gravity-forms-pdf-extended' );
		$tooltips['pdf_allow_url_fopen']  = '<h6>allow_url_fopen</h6>' . esc_html__( 'Having trouble displaying images in PDFs? If this PHP setting is disabled it could be the cause.', 'gravity-forms-pdf-extended' );

		return apply_filters( 'gravitypdf_registered_tooltips', $tooltips );
	}
}
