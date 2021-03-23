<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
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
 * Model_System_Report
 *
 * A general class for System Report
 *
 * @since 6.0
 */
class Model_System_Report extends Helper_Abstract_Model {

	/**
	 * @var Helper_Abstract_Options
	 *
	 * @since 6.0
	 */
	protected $options;

	/**
	 * @var Helper_Data
	 *
	 * @since 6.0
	 */
	protected $data;

	/**
	 * @var LoggerInterface
	 *
	 * @since 6.0
	 */
	protected $log;

	/**
	 * @var Helper_Misc
	 *
	 * @since 6.0
	 */
	protected $misc;

	/**
	 * @var GFPDF_Major_Compatibility_Checks
	 *
	 * @since 6.0
	 */
	protected $status;

	/**
	 * @var Helper_Templates
	 *
	 * @since 6.0
	 */
	protected $templates;

	public function __construct( Helper_Abstract_Options $options, Helper_Data $data, LoggerInterface $log, Helper_Misc $misc, GFPDF_Major_Compatibility_Checks $status, Helper_Templates $templates ) {
		$this->options   = $options;
		$this->data      = $data;
		$this->log       = $log;
		$this->misc      = $misc;
		$this->status    = $status;
		$this->templates = $templates;
	}

	/**
	 * Build Gravity PDF System Report array.
	 *
	 * @since 6.0
	 */
	public function build_gravitypdf_report(): array {
		$structure = $this->get_report_structure();
		foreach ( $this->get_report_items() as $index => $report ) {
			foreach ( $report as $id => $info ) {
				$structure[0]['tables'][ $index ]['items'][ $id ] = $info;
			}
		}

		return $structure;
	}

	/**
	 * Set up array structure of Gravity PDF System Report
	 *
	 * @since 6.0
	 */
	public function get_report_structure(): array {
		$title_export_prefix = 'Gravity PDF - ';
		return [
			[
				'title'        => esc_html__( 'Gravity PDF Environment', 'gravity-forms-pdf-extended' ),
				'title_export' => 'Gravity PDF Environment',
				'tables'       => [
					[
						'title'        => esc_html__( 'PHP', 'gravity-forms-pdf-extended' ),
						'title_export' => $title_export_prefix . 'PHP',
						'items'        => [],
					],

					[
						'title'        => esc_html__( 'Directories and Permissions', 'gravity-forms-pdf-extended' ),
						'title_export' => $title_export_prefix . 'Directories and Permissions',
						'items'        => [],
					],

					[
						'title'        => esc_html__( 'Global Settings', 'gravity-forms-pdf-extended' ),
						'title_export' => $title_export_prefix . 'Global Settings',
						'items'        => [],
					],

					[
						'title'        => esc_html__( 'Security Settings', 'gravity-forms-pdf-extended' ),
						'title_export' => $title_export_prefix . 'Security Settings',
						'items'        => [],
					],
				],
			],
		];
	}

	/**
	 * Move the Gravity PDF plugins from Active Plugins section to Add-Ons
	 *
	 * @since 6.0
	 */
	public function move_gravitypdf_active_plugins_to_gf_addons( array $system_report ): array {
		$active_plugins = $system_report[1]['tables'][2]['items'] ?? [];

		/* Find any active Gravity PDF plugins and move to GF addons */
		foreach ( $active_plugins as $index => $plugin ) {
			if ( stripos( $plugin['label'], 'Gravity PDF' ) !== false ) {
				$system_report[0]['tables'][1]['items'][] = $plugin;
				unset( $system_report[1]['tables'][2]['items'][ $index ] );
			}
		}

		return $system_report;
	}

	/**
	 * Get array report structure of Gravity PDF System Report
	 *
	 * @return array
	 * @since 6.0
	 */
	protected function get_report_items(): array {
		$items                  = [];
		$memory                 = $this->get_memory_limit();
		$allow_url_fopen        = $this->get_allow_url_fopen();
		$temp_folder_protected  = $this->check_temp_folder_permission();
		$temp_folder_permission = $this->is_temporary_folder_writable();

		/* PHP */
		$items[0] = [
			'memory'            => [
				'label'        => esc_html__( 'WP Memory', 'gravity-forms-pdf-extended' ),
				'value'        => $memory['value'],
				'value_export' => $memory['value_export'],
			],

			'allow_url_fopen'   => [
				'label'        => 'allow_url_fopen',
				'value'        => $allow_url_fopen['value'],
				'value_export' => $allow_url_fopen['value_export'],
			],

			'default_charset'   => [
				'label' => esc_html__( 'Default Charset', 'gravity-forms-pdf-extended' ),
				'value' => ini_get( 'default_charset' ),
			],

			'internal_encoding' => [
				'label' => esc_html__( 'Internal Encoding', 'gravity-forms-pdf-extended' ),
				'value' => ini_get( 'internal_encoding' ) ?: ini_get( 'default_charset' ),
			],
		];

		/* Directory and Permissions */
		$items[1] = [
			'pdf_working_directory'     => [
				'label' => esc_html__( 'PDF Working Directory', 'gravity-forms-pdf-extended' ),
				'value' => $this->templates->get_template_path(),
			],

			'pdf_working_directory_url' => [
				'label' => esc_html__( 'PDF Working Directory URL', 'gravity-forms-pdf-extended' ),
				'value' => $this->templates->get_template_url(),
			],

			'font_folder_location'      => [
				'label' => esc_html__( 'Font Folder location', 'gravity-forms-pdf-extended' ),
				'value' => $this->data->template_font_location,
			],

			'temp_folder_location'      => [
				'label' => esc_html__( 'Temporary Folder location', 'gravity-forms-pdf-extended' ),
				'value' => $this->data->template_tmp_location,
			],

			'temp_folder_permission'    => [
				'label'        => esc_html__( 'Temporary Folder permissions', 'gravity-forms-pdf-extended' ),
				'value'        => $temp_folder_permission['value'],
				'value_export' => $temp_folder_permission['value_export'],
			],

			'temp_folder_protected'     => [
				'label'        => esc_html__( 'Temporary Folder protected', 'gravity-forms-pdf-extended' ),
				'value'        => $temp_folder_protected['value'],
				'value_export' => $temp_folder_protected['value_export'],
			],

			'mpdf_temp_folder_location' => [
				'label' => esc_html__( 'mPDF Temporary location', 'gravity-forms-pdf-extended' ),
				'value' => $this->data->mpdf_tmp_location,
			],
		];

		/* Check if outdated core template overrides and display warning */
		$template_status = $this->check_core_template_override_versions();
		if ( ! empty( $template_status ) ) {
			$items[1]['outdated_templates'] = [
				'label'        => esc_html__( 'Outdated Templates', 'gravity-forms-pdf-extended' ),
				'value'        => $template_status['value'],
				'value_export' => $template_status['value_export'],
			];
		}

		/* Global Settings */
		$items[2] = [
			'pdf_entry_list_action'         => [
				'label'        => esc_html__( 'PDF Entry List Action', 'gravity-forms-pdf-extended' ),
				'value'        => $this->options->get_option( 'default_action', 'View' ) === 'View' ? esc_html__( 'View', 'gravity-forms-pdf-extended' ) : esc_html__( 'Download', 'gravity-forms-pdf-extended' ),
				'value_export' => $this->options->get_option( 'default_action', 'View' ),
			],

			'background_processing_enabled' => [
				'label'        => esc_html__( 'Background Processing', 'gravity-forms-pdf-extended' ),
				'value'        => $this->options->get_option( 'background_processing', 'No' ) === 'Yes' ? $this->getController()->view->get_icon( true ) : esc_html__( 'Off', 'gravity-forms-pdf-extended' ),
				'value_export' => $this->options->get_option( 'background_processing', 'No' ),
			],

			'debug_mode_enabled'            => [
				'label'        => esc_html__( 'Debug Mode', 'gravity-forms-pdf-extended' ),
				'value'        => $this->options->get_option( 'debug_mode', 'No' ) === 'Yes' ? $this->getController()->view->get_icon( true ) : esc_html__( 'Off', 'gravity-forms-pdf-extended' ),
				'value_export' => $this->options->get_option( 'debug_mode', 'No' ),
			],
		];

		/* Security Settings */
		$items[3] = [
			'user_restrictions'  => [
				'label' => esc_html__( 'User Restrictions', 'gravity-forms-pdf-extended' ),
				'value' => implode( ', ', $this->options->get_option( 'admin_capabilities', [ 'gravityforms_view_entries' ] ) ),
			],

			'logged_out_timeout' => [
				'label'        => esc_html__( 'Logged Out Timeout', 'gravity-forms-pdf-extended' ),
				'value'        => $this->options->get_option( 'logged_out_timeout', '20' ) . ' ' . esc_html__( 'minute(s)', 'gravity-forms-pdf-extended' ),
				'value_export' => $this->options->get_option( 'logged_out_timeout', '20' ) . ' minutes(s)',
			],
		];

		return $items;
	}

	/**
	 * Returns text and dashicon for Memory Limit
	 *
	 * @since 6.0
	 */
	protected function get_memory_limit(): array {
		$memory = $this->status->get_ram( $this->data->memory_limit );

		return [
			'value'        => $this->getController()->view->memory_limit_markup( $memory ),
			'value_export' => $memory . 'MB',
		];
	}

	/**
	 * @since 6.0
	 */
	protected function get_allow_url_fopen(): array {
		$allow_url_fopen = $this->data->allow_url_fopen;
		$icon            = $this->getController()->view->get_allow_url_fopen( $allow_url_fopen );
		$text            = $allow_url_fopen ? 'Yes' : 'No';

		return [
			'value'        => $icon,
			'value_export' => $text,
		];
	}

	/**
	 * Returns the mark up once the temp folder test is completed.
	 *
	 * @since 6.0
	 */
	protected function check_temp_folder_permission(): array {
		$permission = $this->test_public_tmp_directory_access();

		return [
			'value'        => $this->getController()->view->get_temp_folder_protected( $permission ),
			'value_export' => $permission ? 'Yes' : 'No',
		];
	}

	/**
	 * Check if we can publicly access a file in the PDF Temporary folder
	 *
	 * @since 6.0
	 */
	public function test_public_tmp_directory_access(): bool {
		$tmp_dir       = $this->data->template_tmp_location;
		$tmp_test_file = 'public_tmp_directory_test.txt';
		$return        = true;

		/* create our file */
		file_put_contents( $tmp_dir . $tmp_test_file, 'failed-if-read' );

		/* verify text file exists */
		if ( is_file( $tmp_dir . $tmp_test_file ) ) {

			$site_url = $this->misc->convert_path_to_url( $tmp_dir );
			if ( $site_url !== false ) {

				$response = wp_remote_get( $site_url . $tmp_test_file );

				if ( ! is_wp_error( $response ) ) {

					/*
					 * Check if the web server responded with a OK status code.
					 * If we can read the contents of the file, then mark as failed
					 */
					if (
						isset( $response['response']['code'] ) &&
						$response['response']['code'] === 200 &&
						isset( $response['body'] ) &&
						$response['body'] === 'failed-if-read'
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

			/* Cleanup our test file */
			@unlink( $tmp_dir . $tmp_test_file );
		}

		return $return;
	}

	/**
	 * @since 6.0
	 */
	protected function is_temporary_folder_writable(): array {
		$is_writable = wp_is_writable( $this->data->mpdf_tmp_location );

		$string = $is_writable ? __( 'Writable', 'gravityforms' ) : __( 'Not writable', 'gravityforms' );
		$icon   = $this->getController()->view->get_icon( $is_writable );

		return [
			'value'        => $string . $icon,
			'value_export' => $is_writable ? 'Writable' : 'Not writable',
		];
	}

	/**
	 * Display a warning if the Core template overrides are out of date
	 *
	 * @since 6.0
	 */
	protected function check_core_template_override_versions(): array {
		$templates = $this->get_template_versions( $this->templates->get_core_pdf_templates() );

		$value        = '';
		$value_export = '';

		/* Loop over the Core templates and check if there are any overrides */
		foreach ( $templates as $path => $core_version ) {
			$template = $this->templates->get_template_info_by_id( basename( $path, '.php' ) );
			if ( version_compare( $core_version, $template['version'], '>' ) ) {
				$relative_template_path = str_replace( ABSPATH, '/', $template['path'] );
				$message                = $this->getController()->view->get_template_check_message( $relative_template_path, $template['version'], $core_version );

				$value        .= $message['value'];
				$value_export .= $message['value_export'];
			}
		}

		/* Returns an empty string if all the core template is the latest version */
		if ( empty( $value ) ) {
			return [];
		}

		/* Add an upgrade message and link for more information. */
		$value .= $this->getController()->view->get_template_upgrade_message();

		return [
			'value'        => $value,
			'value_export' => $value_export,
		];
	}

	/**
	 * Get all the template version numbers
	 *
	 * @param array $templates List of template path.
	 *
	 * @return array
	 *
	 * @since 6.0
	 */
	protected function get_template_versions( array $templates ): array {
		$versions = [];
		foreach ( $templates as $path ) {
			$versions[ $path ] = $this->templates->get_template_info_by_path( $path )['version'];
		}

		return $versions;
	}

	/**
	 * Prepare array for the system_report format
	 *
	 * @since 6.0
	 */
	protected function prepare_report( array $item ): array {
		return [
			'label'        => $item['label'],
			'label_export' => $item['label'],
			'value'        => $item['value'],
			'value_export' => $item['value_export'] ?? $item['value'],
		];
	}
}
