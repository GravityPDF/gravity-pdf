<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Templates;
use GFPDF\Statics\Deprecation;
use GFPDF\View\View_System_Report;
use GFPDF_Major_Compatibility_Checks;
use GFPDF_Vendor\Psr\Log\LoggerInterface;

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
 * Model_System_Report
 *
 * A general class for System Report
 *
 * @since 6.0
 */
class Model_System_Report extends Helper_Abstract_Model {

	/**
	 * The ID given to our section heading in the Gravity Forms system report, so it can be linked to directly
	 *
	 * @var string
	 * @since 6.17.0
	 */
	const SECTION_ANCHOR = 'gfpdf-system-report';

	/**
	 * The section each index of the pre-6.17.0 report items array stood for, in order
	 *
	 * Frozen — never extend or reorder. These are the four indexes a pre-6.17.0 listener names.
	 *
	 * @var array
	 *
	 * @since 6.17.0
	 */
	private const POSITIONAL_SECTIONS = [ 'php', 'directories', 'global', 'security' ];

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
		$items     = $this->get_report_items();

		foreach ( $structure[0]['tables'] as $index => $table ) {
			$structure[0]['tables'][ $index ]['items'] = $items[ $table['id'] ] ?? [];
		}

		/* Drop any section with nothing to show, which is how the Deprecated section stays hidden on a clean site */
		$structure[0]['tables'] = array_filter(
			$structure[0]['tables'],
			static function ( $table ) {
				return ! empty( $table['items'] );
			}
		);

		return $structure;
	}

	/**
	 * Set up array structure of Gravity PDF System Report
	 *
	 * @since 6.0
	 */
	public function get_report_structure(): array {
		$title_export_prefix = 'Gravity PDF - ';
		$deprecated_tables   = [];

		/* Deprecation decides which groups there are and in what order; the view names them, once, for all three surfaces */
		foreach ( View_System_Report::get_deprecated_groups() as $group => $names ) {
			$deprecated_tables[] = [
				'id'           => $group,
				'title'        => esc_html( $names['label'] ),
				'title_export' => $title_export_prefix . $names['label'],
			];
		}

		return [
			[
				/* Gravity Forms echoes the title unescaped into an `h3`, which is the only way to anchor our section */
				'title'        => sprintf(
					'<span id="%s" style="scroll-margin-top: 50px">%s</span>',
					self::SECTION_ANCHOR,
					esc_html__( 'Gravity PDF Environment', 'gravity-pdf' )
				),
				'title_export' => 'Gravity PDF Environment',
				'tables'       => array_merge(
					$deprecated_tables,
					[
						[
							'id'           => 'php',
							'title'        => esc_html__( 'PHP', 'gravity-pdf' ),
							'title_export' => $title_export_prefix . 'PHP',
						],

						[
							'id'           => 'directories',
							'title'        => esc_html__( 'Directories and Permissions', 'gravity-pdf' ),
							'title_export' => $title_export_prefix . 'Directories and Permissions',
						],

						[
							'id'           => 'global',
							'title'        => esc_html__( 'Global Settings', 'gravity-pdf' ),
							'title_export' => $title_export_prefix . 'Global Settings',
						],

						[
							'id'           => 'security',
							'title'        => esc_html__( 'Security Settings', 'gravity-pdf' ),
							'title_export' => $title_export_prefix . 'Security Settings',
						],
					]
				),
			],
		];
	}

	/**
	 * The Gravity Forms system report, scrolled to the Gravity PDF section
	 *
	 * @since 6.17.0
	 */
	public static function get_report_url(): string {
		return admin_url( 'admin.php?page=gf_system_status#' . self::SECTION_ANCHOR );
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
	 * @since 6.17.0 Keyed by section name, filtered by `gfpdf_system_status_report_sections`
	 */
	protected function get_report_items(): array {
		$items                  = [];
		$memory                 = $this->get_memory_limit();
		$allow_url_fopen        = $this->get_allow_url_fopen();
		$temp_folder_protected  = $this->check_temp_folder_permission();
		$temp_folder_permission = $this->is_temporary_folder_writable();

		/* Keyed by group, which is what the matching report sections are named after */
		foreach ( Deprecation::group_signals( Deprecation::refresh_signals() ) as $group => $signals ) {
			$items[ $group ] = $this->get_deprecated_feature_items( $signals );
		}

		/* PHP */
		$items['php'] = [
			'memory'            => [
				'label'        => esc_html__( 'WP Memory', 'gravity-pdf' ),
				'label_export' => 'WP Memory',
				'value'        => $memory['value'],
				'value_export' => $memory['value_export'],
			],

			'allow_url_fopen'   => [
				'label'        => 'allow_url_fopen',
				'value'        => $allow_url_fopen['value'],
				'value_export' => $allow_url_fopen['value_export'],
			],

			'default_charset'   => [
				'label'        => esc_html__( 'Default Charset', 'gravity-pdf' ),
				'label_export' => 'Default Charset',
				'value'        => ini_get( 'default_charset' ),
			],

			'internal_encoding' => [
				'label'        => esc_html__( 'Internal Encoding', 'gravity-pdf' ),
				'label_export' => 'Internal Encoding',
				'value'        => ini_get( 'internal_encoding' ) ?: ini_get( 'default_charset' ), //phpcs:ignore Universal.Operators.DisallowShortTernary.Found
			],
		];

		/* Directory and Permissions */
		$items['directories'] = [
			'pdf_working_directory'     => [
				'label'        => esc_html__( 'PDF Working Directory', 'gravity-pdf' ),
				'label_export' => 'PDF Working Directory',
				'value'        => $this->templates->get_template_path(),
			],

			'pdf_working_directory_url' => [
				'label'        => esc_html__( 'PDF Working Directory URL', 'gravity-pdf' ),
				'label_export' => 'PDF Working Directory URL',
				'value'        => $this->templates->get_template_url(),
			],

			'font_folder_location'      => [
				'label'        => esc_html__( 'Font Folder location', 'gravity-pdf' ),
				'label_export' => 'Font Folder location',
				'value'        => $this->data->template_font_location,
			],

			'temp_folder_location'      => [
				'label'        => esc_html__( 'Temporary Folder location', 'gravity-pdf' ),
				'label_export' => 'Temporary Folder location',
				'value'        => $this->data->template_tmp_location,
			],

			'temp_folder_permission'    => [
				'label'        => esc_html__( 'Temporary Folder permissions', 'gravity-pdf' ),
				'label_export' => 'Temporary Folder permissions',
				'value'        => $temp_folder_permission['value'],
				'value_export' => $temp_folder_permission['value_export'],
			],

			'temp_folder_protected'     => [
				'label'        => esc_html__( 'Temporary Folder protected', 'gravity-pdf' ),
				'label_export' => 'Temporary Folder protected',
				'value'        => $temp_folder_protected['value'],
				'value_export' => $temp_folder_protected['value_export'],
			],

			'mpdf_temp_folder_location' => [
				'label'        => esc_html__( 'mPDF Temporary location', 'gravity-pdf' ),
				'label_export' => 'mPDF Temporary location',
				'value'        => $this->data->mpdf_tmp_location,
			],
		];

		/* Check for outdated core template overrides and display a warning */
		$template_status = $this->check_core_template_override_versions();
		if ( ! empty( $template_status ) ) {
			$items['directories']['outdated_templates'] = [
				'label'        => esc_html__( 'Outdated Templates', 'gravity-pdf' ),
				'label_export' => 'Outdated Templates',
				'value'        => $template_status['value'],
				'value_export' => $template_status['value_export'],
			];
		}

		/* Global Settings */
		$is_canonical_release             = is_file( plugin_dir_path( GPDF_PLUGIN_FILE ) . 'gravity-pdf-updater.php' );
		$is_not_canonical_release_message = wp_kses(
			sprintf(
				/* translators: 1: Opening <a> tag, 2: Closing </a> tag */
				__( 'In order to get updates direct from GravityPDF.com %1$syou need to perform a one-time download of the plugin%2$s.', 'gravity-pdf' ),
				'<a href="https://gravitypdf.com/news/installing-and-upgrading-to-the-canonical-version-of-gravity-pdf/">',
				'</a>',
			),
			[ 'a' => [ 'href' => true ] ]
		);

		$items['global'] = [
			'canonical_release'             => [
				'label'        => esc_html__( 'Canonical Release', 'gravity-pdf' ),
				'label_export' => 'Canonical Release',
				'value'        => $is_canonical_release ? $this->getController()->view->get_icon( true ) : $this->getController()->view->get_icon( false ) . $is_not_canonical_release_message,
				'value_export' => $is_canonical_release ? 'Yes' : 'No',
			],

			'pdf_entry_list_action'         => [
				'label'        => esc_html__( 'PDF Entry List Action', 'gravity-pdf' ),
				'label_export' => 'PDF Entry List Action',
				'value'        => $this->options->get_option( 'default_action', 'View' ) === 'View' ? esc_html__( 'View', 'gravity-pdf' ) : esc_html__( 'Download', 'gravity-pdf' ),
				'value_export' => $this->options->get_option( 'default_action', 'View' ),
			],

			'background_processing_enabled' => [
				'label'        => esc_html__( 'Background Processing', 'gravity-pdf' ),
				'label_export' => 'Background Processing',
				'value'        => $this->options->get_option( 'background_processing', 'No' ) === 'Yes' ? $this->getController()->view->get_icon( true ) : esc_html__( 'Off', 'gravity-pdf' ),
				'value_export' => $this->options->get_option( 'background_processing', 'No' ),
			],

			'debug_mode_enabled'            => [
				'label'        => esc_html__( 'Debug Mode', 'gravity-pdf' ),
				'label_export' => 'Debug Mode',
				'value'        => $this->options->get_option( 'debug_mode', 'No' ) === 'Yes' ? $this->getController()->view->get_icon( true ) : esc_html__( 'Off', 'gravity-pdf' ),
				'value_export' => $this->options->get_option( 'debug_mode', 'No' ),
			],
		];

		/* Security Settings */
		$items['security'] = [
			'user_restrictions'  => [
				'label'        => esc_html__( 'User Restrictions', 'gravity-pdf' ),
				'label_export' => 'User Restrictions',
				'value'        => implode( ', ', $this->options->get_option( 'admin_capabilities', [ 'gravityforms_view_entries' ] ) ),
			],

			'logged_out_timeout' => [
				'label'        => esc_html__( 'Logged Out Timeout', 'gravity-pdf' ),
				'label_export' => 'Logged Out Timeout',
				'value'        => $this->options->get_option( 'logged_out_timeout', '20' ) . ' ' . esc_html__( 'minute(s)', 'gravity-pdf' ),
				'value_export' => $this->options->get_option( 'logged_out_timeout', '20' ) . ' minutes(s)',
			],
		];

		$items = $this->apply_deprecated_report_items_filter( $items );

		return apply_filters( 'gfpdf_system_status_report_sections', $items );
	}

	/**
	 * Pass the sections through the positional `gfpdf_system_status_report_items` filter
	 *
	 * Deliberately not registered on `Deprecation`: that registry is the v3 layer 7.0 removes, and this filter has
	 * no removal scheduled.
	 *
	 * @param array $items Sections keyed by name
	 *
	 * @return array
	 *
	 * @since 6.17.0
	 */
	protected function apply_deprecated_report_items_filter( array $items ): array {
		$positional = [];

		/* Only the four a pre-6.17.0 listener knew: numbering the sections added since renumbers these */
		foreach ( self::POSITIONAL_SECTIONS as $index => $section ) {
			$positional[ $index ] = $items[ $section ] ?? [];
		}

		$positional = apply_filters_deprecated(
			'gfpdf_system_status_report_items',
			[ $positional ],
			'6.17.0',
			'gfpdf_system_status_report_sections',
			esc_html__( 'The report sections are keyed by name rather than by position.', 'gravity-pdf' )
		);

		/* Read back off the same indexes, so an index the listener invents has no section to land in */
		foreach ( self::POSITIONAL_SECTIONS as $index => $section ) {
			$items[ $section ] = $positional[ $index ] ?? [];
		}

		return $items;
	}

	/**
	 * Build the rows for one of the deprecation sections
	 *
	 * Each row is only included when that feature is actually in use on this site, and the section itself is
	 * discarded by build_gravitypdf_report() when nothing is detected.
	 *
	 * @param array $signals The signals belonging to that section
	 *
	 * @since 6.17.0
	 */
	protected function get_deprecated_feature_items( array $signals ): array {
		$view = $this->getController()->view;

		$items = [];
		foreach ( $signals as $key => $signal ) {
			$items[ $key ] = $view->get_deprecated_feature_report_item( $key, $signal );
		}

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
			'value_export' => $memory === -1 ? 'Unlimited' : $memory . 'MB',
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
		$path          = $tmp_dir . $tmp_test_file;

		/* create our file */
		file_put_contents( $path, 'failed-if-read' );

		/* verify text file exists */
		if ( ! is_file( $path ) ) {
			$this->log->error(
				'Could not write to PDF temporary directory to test for public access',
				[
					'path' => $path,
				]
			);

			return true;
		}

		$site_url = $this->misc->convert_path_to_url( $tmp_dir );
		if ( $site_url === false ) {
			@unlink( $path ); /* phpcs:ignore */

			$this->log->error(
				'Could not convert path to URL to test for public access',
				[
					'path' => $path,
				]
			);

			return true;
		}

		$response = wp_remote_get( $site_url . $tmp_test_file );
		if ( is_wp_error( $response ) ) {
			@unlink( $path ); /* phpcs:ignore */

			return true;
		}

		@unlink( $path ); /* phpcs:ignore */

		/* if we read the contents of the file over HTTP the directory is publicly accessible */
		if ( trim( wp_remote_retrieve_body( $response ) ) !== 'failed-if-read' ) {
			return true;
		}

		return false;
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
				$relative_template_path = $this->misc->relative_path( $template['path'], '/' );
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
