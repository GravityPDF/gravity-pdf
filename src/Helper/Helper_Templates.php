<?php

namespace GFPDF\Helper;

use Exception;
use GPDFAPI;
use Psr\Log\LoggerInterface;
use stdClass;

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
 * Class Helper_Templates
 *
 * @package GFPDF\Helper
 *
 * @since   4.1
 */
class Helper_Templates {

	/**
	 * Holds our log class
	 *
	 * @var LoggerInterface
	 *
	 * @since 4.1
	 */
	protected $log;

	/**
	 * Holds our Helper_Data object
	 * which we can autoload with any data needed
	 *
	 * @var Helper_Data
	 *
	 * @since 4.1
	 */
	protected $data;

	/**
	 * Holds the abstracted Gravity Forms API specific to Gravity PDF
	 *
	 * @var Helper_Form
	 *
	 * @since 4.3
	 */
	protected $gform;

	/**
	 * Setup our class by injecting all our dependencies
	 *
	 * @param LoggerInterface      $log  Our logger class
	 * @param Helper_Data          $data Our plugin data store
	 * @param Helper_Abstract_Form $gform
	 *
	 * @since 4.1
	 */
	public function __construct( LoggerInterface $log, Helper_Data $data, Helper_Abstract_Form $gform ) {

		/* Assign our internal variables */
		$this->log   = $log;
		$this->data  = $data;
		$this->gform = $gform;
	}

	/**
	 * Check if running single or multisite and return the working directory path
	 *
	 * @return string Path to working directory
	 *
	 * @since 4.1
	 */
	public function get_template_path() {
		if ( is_multisite() ) {
			return $this->data->multisite_template_location;
		}

		return $this->data->template_location;
	}

	/**
	 * Check if running single or multisite and return the working directory URL
	 *
	 * @return string URL to working directory
	 *
	 * @since 4.1
	 */
	public function get_template_url() {
		if ( is_multisite() ) {
			return $this->data->multisite_template_location_url;
		}

		return $this->data->template_location_url;
	}

	/**
	 * Gets the full list of available PDF templates
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function get_all_templates() {

		$template_list                   = [];
		$matched_templates_basename_list = [];
		$raw_templates                   = $this->get_unfiltered_template_list();

		/* Loop through all files, filter out any duplicates and return a single array with all the templates */
		foreach ( $raw_templates as $template_group ) {
			$unique_templates = $this->parse_unique_templates( $template_group, $matched_templates_basename_list );
			$template_list    = array_merge( $template_list, $unique_templates );

			/* Keep track of all matches by their basename (template ID) */
			$matched_templates_basename_list = array_merge(
				$matched_templates_basename_list,
				array_map(
					function( $file ) {
						return basename( $file, '.php' );
					},
					$unique_templates
				)
			);
		}

		return $template_list;
	}

	/**
	 * Get a multi-dimensional array with the PDF template files
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function get_unfiltered_template_list() {
		$raw_templates = [];

		/* Get current multisite templates, if any */
		if ( is_multisite() ) {
			$raw_templates[] = glob( $this->data->multisite_template_location . '*.php' );
		}

		/* Get the current user-templates and the core templates */
		$raw_templates[] = glob( $this->data->template_location . '*.php' );
		$raw_templates[] = $this->get_core_pdf_templates();

		return apply_filters( 'gfpdf_unfiltered_template_list', $raw_templates );
	}

	/**
	 * Parse our installed PDF template files
	 *
	 * @return array The array of templates
	 *
	 * @since 4.1
	 */
	public function get_all_templates_by_group() {

		$template_groups = [];
		$template_list   = $this->get_all_templates();

		foreach ( $template_list as $template_path ) {
			$info = $this->get_template_info_by_path( $template_path );

			if ( $this->is_template_compatible( $info['required_pdf_version'] ) ) {
				$template_groups[ $info['group'] ][ $info['id'] ] = $info['template'];
			}
		}

		return apply_filters( 'gfpdf_template_list', $template_groups );
	}

	/**
	 * Checks if the version is compatible with Gravity PDF
	 *
	 * @param int|float|string $required_version The version to check against
	 *
	 * @return bool
	 *
	 * @since 4.1
	 */
	public function is_template_compatible( $required_version ) {
		if ( version_compare( $required_version, PDF_EXTENDED_VERSION, '<=' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Appends a compatibility notice to the template name if current version of Gravity PDF isn't compatible
	 *
	 * @param string           $template_name    The template ID (the PDF template basename)
	 * @param string|int|float $required_version The minimum required version of Gravity PDF the template needs to run
	 *
	 * @return string
	 *
	 * @since 4.1
	 */
	public function maybe_add_template_compatibility_notice( $template_name, $required_version ) {
		if ( ! $this->is_template_compatible( $required_version ) ) {
			return $template_name . ' (' . esc_html__( 'Requires Gravity PDF', 'gravity-forms-pdf-extended' ) . ' v' . $required_version . ')';
		}

		return $template_name;
	}

	/**
	 * Filters out all $templates_to_check templates already found in the $current_template_list
	 *
	 * @param array $templates_to_check
	 * @param array $current_template_list
	 *
	 * @return array Full path to the unique templates
	 *
	 * @since 4.1
	 */
	private function parse_unique_templates( $templates_to_check, $current_template_list = [] ) {
		$template_list = [];

		foreach ( $templates_to_check as $template ) {
			$file_name = basename( $template, '.php' );

			/*
			 * If the template isn't found in the current list and it's our legacy configuration file
			 * we'll include it in our template list
			 */
			if ( $file_name !== 'configuration' && $file_name !== 'configuration.archive'
				 && ! in_array( $file_name, $current_template_list, true )
			) {
				$template_list[] = $template;
			}
		}

		return $template_list;
	}

	/**
	 * Get template header info for all PDF templates
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function get_all_template_info() {
		return array_map(
			function( $template_path ) {
				return $this->get_template_info_by_path( $template_path );
			},
			$this->get_all_templates()
		);
	}

	/**
	 * Get the PDF template path by the template name
	 *
	 * @param string  $template_id  The PDF template name/ID
	 * @param boolean $include_core Whether to check in the core plugin template folder
	 *
	 * @return string The full path to the matching template
	 *
	 * @throws Exception
	 *
	 * @since 4.1
	 */
	public function get_template_path_by_id( $template_id, $include_core = true ) {

		/* Check if template found in multisite PDF working directory */
		$path_to_test = is_multisite() ? realpath( $this->data->multisite_template_location . $template_id . '.php' ) : false;
		if ( $path_to_test !== false && strpos( $path_to_test, realpath( $this->data->multisite_template_location ) ) === 0 ) {
			return $path_to_test;
		}

		/* Check if template found in PDF working directory */
		$path_to_test = realpath( $this->data->template_location . $template_id . '.php' );
		if ( $path_to_test !== false && strpos( $path_to_test, realpath( $this->data->template_location ) ) === 0 ) {
			return $path_to_test;
		}

		/* Check if template found in the core template files */
		$path_to_test = realpath( PDF_PLUGIN_DIR . 'src/templates/' . $template_id . '.php' );
		if ( $include_core && $path_to_test !== false && strpos( $path_to_test, realpath( realpath( PDF_PLUGIN_DIR . 'src/templates/' ) ) ) === 0 ) {
			return $path_to_test;
		}

		$fallback_template = apply_filters( 'gfpdf_fallback_template_path_by_id', false, $template_id );
		if ( is_string( $fallback_template ) ) {
			return $fallback_template;
		}

		throw new Exception( sprintf( 'Could not find the template: %s.php', $template_id ) );
	}

	/**
	 * Get the template information based on the template file's basename (without .php)
	 *
	 * @param string $template_id The PDF template basename (eg. zadani)
	 *
	 * @return array The template information
	 *
	 * @since 4.1
	 */
	public function get_template_info_by_id( $template_id ) {

		try {
			$template_path = $this->get_template_path_by_id( $template_id );

			return $this->get_template_info_by_path( $template_path );
		} catch ( Exception $e ) {
			$this->log->warning( $e->getMessage() );

			return [
				'group' => esc_html__( 'Legacy', 'gravity-forms-pdf-extended' ),
			];
		}
	}

	/**
	 * Get an array of the current template files on the server
	 *
	 * @param string $template_id
	 *
	 * @return array The full path to all files related to the current PDF template
	 *
	 * @throws Exception
	 *
	 * @internal This only includes the base PDF template and associated config and image files
	 *           Any additional files aren't automatically cleaned up and should be removed
	 *           from the PDF template configuration's tearDown() method.
	 *
	 * @since    4.1
	 */
	public function get_template_files_by_id( $template_id ) {

		$files = [];

		try {
			$files[] = $this->get_template_path_by_id( $template_id, false );
		} catch ( Exception $e ) {
			/* Don't process because we couldn't find the file */
			throw new Exception( 'Could not find PDF template file' );
		}

		try {
			$files[] = $this->get_config_path_by_id( $template_id, false );
		} catch ( Exception $e ) {
			/* do nothing */
		}

		$image = $this->get_template_image( $template_id, 'path', false );

		if ( $image !== '' ) {
			$files[] = $image;
		}

		return $files;
	}

	/**
	 * Gets the PDF template header information and returns it in a parsed format
	 *
	 * @param string $template_path The full path to the PDF template file
	 * @param string $cache_name    The ID of the transient we should check first
	 * @param int    $cache_time    How long in microseconds until the transient expires (default 1 week)
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function get_template_info_by_path( $template_path, $cache_name = '', $cache_time = 604800 ) {
		$options = GPDFAPI::get_options_class();
		$debug   = $options->get_option( 'debug_mode', 'No' );

		if ( $debug === 'No' ) {
			$cache_name = ! empty( $cache_name ) ? $cache_name : $this->data->template_transient_cache;
			$cache      = get_transient( $cache_name );

			if ( isset( $cache[ $template_path ] ) ) {
				return $cache[ $template_path ];
			}
		}

		$info = get_file_data( $template_path, $this->get_template_header_details() );

		$info['id']                   = basename( $template_path, '.php' );
		$info['template']             = ( strlen( $info['template'] ) > 0 ) ? $info['template'] : $this->human_readable_template_name( $info['id'] );
		$info['group']                = ( strlen( $info['group'] ) > 0 ) ? $info['group'] : esc_html__( 'Legacy', 'gravity-forms-pdf-extended' );
		$info['description']          = ( strlen( $info['description'] ) > 0 ) ? $info['description'] : '';
		$info['author']               = ( strlen( $info['author'] ) > 0 ) ? $info['author'] : '';
		$info['author uri']           = ( strlen( $info['author uri'] ) > 0 ) ? $info['author uri'] : '';
		$info['version']              = ( strlen( $info['version'] ) > 0 ) ? $info['version'] : '1.0';
		$info['tags']                 = ( strlen( $info['tags'] ) > 0 ) ? $info['tags'] : '';
		$info['path']                 = $template_path;
		$info['screenshot']           = $this->get_template_image( $info['id'] );
		$info['required_pdf_version'] = ( strlen( 'required_pdf_version' ) > 0 ) ? $info['required_pdf_version'] : '4.0';

		/* Save the results to a transient so we don't hit the disk every page load */
		if ( $debug === 'No' ) {
			$cache                   = $cache ?? [];
			$cache[ $template_path ] = $info;

			set_transient( $cache_name, $cache, $cache_time );
		}

		return $info;
	}

	/**
	 * Flush the template transient cache, when required
	 *
	 * @since 5.1
	 */
	public function flush_template_transient_cache() {
		delete_transient( $this->data->template_transient_cache );
	}

	/**
	 * The key / value parsing of the expected PDF template header in v4
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function get_template_header_details() {
		/**
		 * We load in data from the PDF template headers
		 *
		 * @var array
		 */
		return apply_filters(
			'gfpdf_template_header_details',
			[
				'template'             => 'Template Name',
				'version'              => 'Version',
				'description'          => 'Description',
				'author'               => 'Author',
				'author uri'           => 'Author URI',
				'group'                => 'Group',
				'required_pdf_version' => 'Required PDF Version',
				'tags'                 => 'Tags',
			]
		);
	}

	/**
	 * Returns an array of the current PDF templates shipped with Gravity PDF
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function get_core_pdf_templates() {
		$templates = glob( PDF_PLUGIN_DIR . 'src/templates/*.php' );

		return ( is_array( $templates ) ) ? $templates : [];
	}

	/**
	 * Gets the full path to the template's config file (if any)
	 *
	 * @param string $template_id
	 * @param bool   $include_core
	 *
	 * @return string
	 *
	 * @throws Exception
	 *
	 * @since 4.1
	 */
	public function get_config_path_by_id( $template_id, $include_core = true ) {

		/* Check if there's a configuration class in the following directories */
		$config_paths = [
			$this->data->template_location . 'config/',
		];

		if ( is_multisite() ) {
			array_unshift( $config_paths, $this->data->multisite_template_location . 'config/' );
		}

		if ( $include_core ) {
			array_push( $config_paths, PDF_PLUGIN_DIR . 'src/templates/config/' );
		}

		$config_paths = apply_filters( 'gfpdf_template_config_paths', $config_paths );

		foreach ( $config_paths as $path ) {
			$file = realpath( $path . $template_id . '.php' );
			if ( $file !== false && strpos( $file, realpath( $path ) ) === 0 ) {
				return $file;
			}
		}

		throw new Exception( sprintf( 'No optional configuration file exists for %s.php', $template_id ) );
	}

	/**
	 * Attempts to load the current template configuration (if any)
	 * We first look in the PDF_EXTENDED_TEMPLATE directory (in case a user has overridden the file)
	 * Then we try and load the core configuration file
	 *
	 * @param string $template_id The template config to load
	 *
	 * @return object
	 *
	 * @since 4.1
	 */
	public function get_config_class( $template_id ) {

		/* Allow a user to change the current template configuration file if they have the appropriate capabilities */
		if ( rgget( 'template' ) && is_user_logged_in() && $this->gform->has_capability( 'gravityforms_edit_settings' ) ) {
			$template_id = rgget( 'template' );

			/* Handle legacy v3 URL structure and strip .php from the end of the template */
			if ( isset( $_GET['gf_pdf'] ) && isset( $_GET['fid'] ) && isset( $_GET['lid'] ) ) {
				$template_id = substr( $template_id, 0, -4 );
			}
		}

		try {
			$class_path = $this->get_config_path_by_id( $template_id );
		} catch ( Exception $e ) {
			$this->log->notice( $e->getMessage() );
		}

		try {
			if ( ! empty( $class_path ) ) {
				return $this->load_template_config_file( $class_path );
			}
		} catch ( Exception $e ) {
			$this->log->warning( $e->getMessage() );
		}

		/* If class still empty it's either a legacy template or doesn't have a config. Check for legacy templates which support certain fields */
		$legacy_templates = apply_filters(
			'gfpdf_legacy_templates',
			[
				'default-template',
				'default-template-two-rows',
				'default-template-no-style',
			]
		);

		if ( in_array( $template_id, $legacy_templates, true ) ) {
			try {
				$class = $this->load_template_config_file( PDF_PLUGIN_DIR . 'src/templates/config/legacy.php' );
			} catch ( Exception $e ) {
				$this->log->error( 'Legacy Template Configuration Failed to Load' );
			}
		}

		/* If there is still no class loaded we'll pass along a new empty class */
		if ( empty( $class ) ) {
			$class = new stdClass();
		}

		return $class;
	}

	/**
	 * Load our template configuration file, if it exists
	 *
	 * @param string $file The file to load
	 *
	 * @return object
	 *
	 * @throws Exception
	 * @since 4.1
	 *
	 */
	public function load_template_config_file( $file ) {

		$namespace  = 'GFPDF\Templates\Config\\';
		$class_name = $this->get_config_class_name( $file );
		$fqcn       = $namespace . $class_name;

		/* Try and load the file if the class doesn't exist */
		if ( ! class_exists( $fqcn ) && is_file( $file ) && is_readable( $file ) ) {
			require_once( $file );
		}

		/* Insure the class we are trying to load exists */
		if ( class_exists( $fqcn ) ) {
			return new $fqcn();
		}

		throw new Exception( 'Template configuration failed to load: ' . $fqcn );
	}

	/**
	 * Takes a full path to the file and converts it to the appropriate class name
	 * This follows the simple rules the file basename has its hyphens and spaces are converted to underscores
	 * then gets converted to sentence case using the underscore as a delimiter
	 *
	 * @param string $file The path to a file
	 *
	 * @return string
	 *
	 * @since 4.1
	 */
	public function get_config_class_name( $file ) {
		$file = basename( $file, '.php' );
		$file = str_replace( [ '-', ' ' ], '_', $file );

		/* Using a delimiter with ucwords doesn't appear to work correctly so go old school */
		$file_array = explode( '_', $file );
		array_walk(
			$file_array,
			function( &$item ) {
				$item = mb_convert_case( $item, MB_CASE_TITLE, 'UTF-8' );
			}
		);

		$file = implode( '_', $file_array );

		return $file;
	}

	/**
	 * Converts a name into something a human can more easily read
	 *
	 * @param string $name The string to convert
	 *
	 * @return string
	 *
	 * @since  4.1
	 */
	public function human_readable_template_name( $name ) {
		$name = str_replace( [ '-', '_' ], ' ', $name );

		return mb_convert_case( $name, MB_CASE_TITLE );
	}

	/**
	 * Do a lookup for the current template image (if any) and return the url
	 *
	 * @param string $template     The template name to look for
	 * @param string $type         Either 'url' or 'path'
	 * @param bool   $include_core Whether to include the core PDF templates
	 *
	 * @return string Full URL to image
	 *
	 * @since 4.1
	 */
	public function get_template_image( $template, $type = 'url', $include_core = true ) {

		/* Check if there's an image in the following directories */
		$image_paths = [
			$this->data->template_location_url . 'images/' => $this->data->template_location . 'images/',
		];

		if ( is_multisite() ) {
			$image_paths = [ $this->data->multisite_template_location_url . 'images/' => $this->data->multisite_template_location . 'images/' ] + $image_paths;
		}

		if ( $include_core ) {
			$image_paths[ PDF_PLUGIN_URL . 'src/templates/images/' ] = PDF_PLUGIN_DIR . 'src/templates/images/';
		}

		$image_paths = apply_filters( 'gfpdf_template_image_paths', $image_paths );

		/* Check if our image exists in one of our directories and return the URL */
		$template .= '.png';
		foreach ( $image_paths as $url => $path ) {
			$file = realpath( $path . $template );
			if ( $file !== false && strpos( $file, realpath( $path ) ) === 0 ) {
				return ( $type === 'url' ) ? $url . $template : realpath( $path . $template );
			}
		}

		return '';
	}

	/**
	 * Get the arguments array that should be passed to our PDF Template
	 *
	 * @param array  $form       The Gravity Form array
	 * @param array  $fields     The Gravity Form fields array, with the field ID as the array key
	 * @param array  $entry      Gravity Form Entry The Gravity Forms entry array
	 * @param array  $form_data  The form data array, formatted form the $entry array
	 * @param array  $settings   PDF Settings The current PDF settings
	 * @param object $config     The current PDF template configuration class
	 * @param array  $legacy_ids An array of multiple entry IDs for legacy templates only
	 *
	 * @return array
	 *
	 * @since 4.1
	 */
	public function get_template_arguments( $form, $fields, $entry, $form_data, $settings, $config, $legacy_ids ) {
		global $gfpdf;

		/* Disable the field encryption checks which can slow down our entry queries */
		add_filter( 'gform_is_encrypted_field', '__return_false' );

		/* Inject the settings into our config object, if requested */
		if ( $config instanceof Helper_Interface_Config_Settings ) {
			$config->set_settings( $settings );
		}

		/* See https://docs.gravitypdf.com/v6/developers/filters/gfpdf_template_args/ for more details about this filter */

		return apply_filters(
			'gfpdf_template_args',
			[

				'form_id'   => $form['id'], /* backwards compat */
				'lead_ids'  => $legacy_ids, /* backwards compat */
				'lead_id'   => apply_filters( 'gfpdfe_lead_id', $entry['id'], $form, $entry, $gfpdf ), /* backwards compat */

				'form'      => $form,
				'entry'     => $entry,
				'lead'      => $entry,
				'form_data' => $form_data,
				'fields'    => $fields,
				'config'    => $config,

				'settings'  => $settings,

				'gfpdf'     => $gfpdf,

			],
			$entry,
			$settings,
			$form
		);
	}
}
