<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF\Helper\Helper_Data;
use GFPDF\Helper\Helper_Misc;
use GFPDF\Helper\Helper_Templates;
use GFPDF\Statics\Cache;
use GFPDF\Statics\Deprecation;
use GFPDF\View\View_Actions;
use GFPDF\View\View_System_Report;
use GFPDF_Major_Compatibility_Checks;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use GPDFAPI;

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
	 * The ID of the Core Fonts check row
	 *
	 * @since 6.19.0
	 */
	const FONTS_FAILED_ANCHOR = 'gfpdf-font-download-failed';

	/**
	 * The ID of the Font Installer row
	 *
	 * @since 6.19.0
	 */
	const FONT_DOWNLOADS_ANCHOR = 'gfpdf-core-font-downloads';

	/**
	 * The section and row of each check after the deprecation groups', in the order they're listed
	 *
	 * @since 6.19.0
	 */
	private const CHECK_ROWS = [
		[ 'php', 'memory' ],
		[ 'php', 'allow_url_fopen' ],
		[ 'directories', 'folder_permissions' ],
		[ 'directories', 'temp_folder_protected' ],
		[ 'directories', 'core_fonts' ],
		[ 'directories', 'outdated_templates' ],
		[ 'cache', 'cache_cleanup' ],
	];

	/**
	 * The section and row of each Configuration row, in the order they're listed
	 *
	 * @since 6.19.0
	 */
	private const CONFIGURATION_ROWS = [
		[ 'directories', 'pdf_working_directory' ],
		[ 'directories', 'folders' ],
		[ 'directories', 'core_font_downloads' ],
		[ 'global', 'pdf_entry_list_action' ],
		[ 'global', 'background_processing_enabled' ],
		[ 'global', 'debug_mode_enabled' ],
		[ 'security', 'user_restrictions' ],
		[ 'security', 'logged_out_timeout' ],
		[ 'cache', 'pdf_cache' ],
		[ 'php', 'charset' ],
		[ 'directories', 'substitution_fonts' ],
	];

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

	/**
	 * @var Model_Pdf_Cache
	 *
	 * @since 7.0
	 */
	protected $pdf_cache;

	/**
	 * @var Model_Required_Fonts|null
	 *
	 * @since 6.19.0
	 */
	protected $required_fonts;

	/**
	 * @var Model_Font_Sources|null
	 *
	 * @since 6.19.0
	 */
	protected $font_sources;

	/**
	 * @var Model_Font_Downloader|null
	 *
	 * @since 6.19.0
	 */
	protected $font_downloader;

	/**
	 * Build the System Report from the plugin settings, environment checks, templates, PDF cache and fonts
	 *
	 * @since 6.0
	 * @since 6.19.0 Added `$required_fonts`, `$font_sources` and `$font_downloader`, which default to the registered models
	 * @since 7.0 Added `$pdf_cache`
	 */
	public function __construct( Helper_Abstract_Options $options, Helper_Data $data, LoggerInterface $log, Helper_Misc $misc, GFPDF_Major_Compatibility_Checks $status, Helper_Templates $templates, Model_Pdf_Cache $pdf_cache, ?Model_Required_Fonts $required_fonts = null, ?Model_Font_Sources $font_sources = null, ?Model_Font_Downloader $font_downloader = null ) {
		$this->options         = $options;
		$this->data            = $data;
		$this->log             = $log;
		$this->misc            = $misc;
		$this->status          = $status;
		$this->templates       = $templates;
		$this->pdf_cache       = $pdf_cache;
		$this->required_fonts  = $required_fonts;
		$this->font_sources    = $font_sources;
		$this->font_downloader = $font_downloader;
	}

	/**
	 * Build Gravity PDF System Report array.
	 *
	 * @since 6.0
	 * @since 6.19.0 Sorts the report sections into the Checks and Configuration tables
	 */
	public function build_gravitypdf_report(): array {
		$structure = $this->get_report_structure();
		$tables    = $this->sort_report_items( $this->get_report_items() );

		foreach ( $structure[0]['tables'] as $index => $table ) {
			$structure[0]['tables'][ $index ]['items'] = $tables[ $table['id'] ];
		}

		return $structure;
	}

	/**
	 * Set up array structure of Gravity PDF System Report
	 *
	 * @since 6.0
	 * @since 6.19.0 Two tables, Checks and Configuration
	 */
	public function get_report_structure(): array {
		return [
			[
				/* Gravity Forms echoes the title unescaped into an `h3`, which is the only way to anchor our section */
				'title'        => sprintf(
					'<span id="%s" style="scroll-margin-top: 50px">%s</span>',
					self::SECTION_ANCHOR,
					esc_html__( 'Gravity PDF Environment', 'gravity-pdf' )
				),
				'title_export' => 'Gravity PDF Environment',
				'tables'       => [
					[
						'id'           => 'checks',
						'title'        => esc_html__( 'Checks', 'gravity-pdf' ),
						'title_export' => 'Gravity PDF - Checks',
					],

					[
						'id'           => 'configuration',
						'title'        => esc_html__( 'Configuration', 'gravity-pdf' ),
						'title_export' => 'Gravity PDF - Configuration',
					],
				],
			],
		];
	}

	/**
	 * Sort the filtered sections into the Checks and Configuration tables. Failing checks come first, and the checks
	 * that passed share one row on screen.
	 *
	 * @param array $sections Report rows, keyed by section and then row
	 *
	 * @return array{checks: array, configuration: array}
	 *
	 * @since 6.19.0
	 */
	protected function sort_report_items( array $sections ): array {
		$checks        = [];
		$configuration = [];

		/* A deprecation group's check is every row in its section */
		foreach ( Deprecation::get_groups() as $group ) {
			if ( ! empty( $sections[ $group ] ) ) {
				$checks[] = array_values( (array) $sections[ $group ] );
			}

			unset( $sections[ $group ] );
		}

		foreach ( self::CHECK_ROWS as [ $section, $key ] ) {
			if ( isset( $sections[ $section ][ $key ] ) ) {
				$checks[] = [ $sections[ $section ][ $key ] ];
				unset( $sections[ $section ][ $key ] );
			}
		}

		foreach ( self::CONFIGURATION_ROWS as [ $section, $key ] ) {
			if ( isset( $sections[ $section ][ $key ] ) ) {
				$configuration[] = $sections[ $section ][ $key ];
				unset( $sections[ $section ][ $key ] );
			}
		}

		/* The rows left are from add-ons: outdated templates and failures are checks, anything else is configuration */
		foreach ( $sections as $rows ) {
			foreach ( (array) $rows as $key => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				if ( ( $row['is_valid'] ?? null ) === false || preg_match( '/outdated_templates$/', (string) $key ) ) {
					$checks[] = [ $row ];
				} else {
					$configuration[] = $row;
				}
			}
		}

		$failed = [];
		$passed = [];
		foreach ( $checks as $rows ) {
			if ( count( $rows ) === 1 && ( $rows[0]['is_valid'] ?? null ) === true ) {
				$passed[] = $rows[0];
			} else {
				$failed = array_merge( $failed, $rows );
			}
		}

		return [
			'checks'        => array_merge( $failed, $this->getController()->view->get_passed_rows( $passed, count( $checks ) ) ),
			'configuration' => $configuration,
		];
	}

	/**
	 * The Gravity Forms system report, scrolled to an anchor: by default the Gravity PDF section
	 *
	 * @since 6.17.0
	 */
	public static function get_report_url( string $anchor = self::SECTION_ANCHOR ): string {
		return admin_url( 'admin.php?page=gf_system_status#' . $anchor );
	}

	/**
	 * A row label that can be linked to, as Gravity Forms echoes labels unescaped and gives rows no ID
	 *
	 * @since 6.19.0
	 */
	protected function get_anchored_label( string $anchor, string $label ): string {
		return sprintf( '<span id="%s" style="scroll-margin-top: 50px">%s</span>', $anchor, $label );
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
	 * The Core Fonts check: fails for each file the failed fonts notice lists, dismissed or not, and for the required
	 * files missing while the Font Installer is off
	 *
	 * @since 6.19.0
	 */
	protected function get_core_fonts(): array {
		$view    = $this->getController()->view;
		$health  = $this->get_core_font_health();
		$failed  = $this->get_font_downloader()->get_failed_files();
		$missing = [];
		$value   = [];
		$export  = [];
		$fix     = '';

		if ( $health['blocker'] !== null ) {
			$missing = array_values( array_diff( $health['missing'], array_keys( $failed ) ) );
		}

		if ( $failed !== [] ) {
			$gave_up = [];
			foreach ( $failed as $file => $time ) {
				$gave_up[] = sprintf( '%s (gave up %s)', $file, gmdate( 'Y-m-d', $time ) );
			}

			/* translators: %d: How many font files */
			$value[]  = esc_html( sprintf( __( '%d failed:', 'gravity-pdf' ), count( $failed ) ) ) . '<br>' . $view->get_code_lines( array_keys( $failed ) );
			$export[] = count( $failed ) . ' failed: ' . implode( ', ', $gave_up );
			$fix      = __( 'Some characters may not display in PDFs.', 'gravity-pdf' );
		}

		if ( $missing !== [] ) {
			/* translators: %d: How many font files */
			$value[]  = esc_html( sprintf( __( '%d missing:', 'gravity-pdf' ), count( $missing ) ) ) . '<br>' . $view->get_code_lines( $missing );
			$export[] = count( $missing ) . ' missing: ' . implode( ', ', $missing );
		}

		if ( $health['blocker'] !== null ) {
			/* translators: %s: The GPDF_AUTO_INSTALL_FONTS constant or the gfpdf_auto_install_fonts filter */
			$fix = sprintf( __( 'The Font Installer is turned off by the %s.', 'gravity-pdf' ), $health['blocker'] );
		}

		if ( $fix !== '' ) {
			$fix = $view->get_fix( $fix, View_Actions::CORE_FONTS_DOCS_URL, __( 'Install the fonts by hand', 'gravity-pdf' ) );
		}

		$row = [
			'label'        => $this->get_anchored_label( self::FONTS_FAILED_ANCHOR, esc_html__( 'Core Fonts', 'gravity-pdf' ) ),
			'label_export' => 'Core Fonts',
			'value'        => $value !== [] ? implode( '<br>', $value ) : esc_html__( 'None failed', 'gravity-pdf' ),
			'value_export' => $export !== [] ? implode( '; ', $export ) : 'None failed',
		];

		return $view->get_check( $row, __( 'no core fonts failed', 'gravity-pdf' ), $fix );
	}

	/**
	 * Whether core fonts may be downloaded, and what turned them off
	 *
	 * @since 6.19.0
	 */
	protected function get_core_font_downloads(): array {
		$status = $this->get_font_downloader()->get_auto_install_status();

		$row = [
			'label'        => $this->get_anchored_label( self::FONT_DOWNLOADS_ANCHOR, esc_html__( 'Font Installer', 'gravity-pdf' ) ),
			'label_export' => 'Font Installer',
			'value'        => esc_html( $status ),
			'value_export' => $status,
		];

		return $this->mark_default( $row, $status === 'On' );
	}

	/**
	 * The missing required files, those that gave up (with when) and what turned downloads off, for Site Health
	 *
	 * @return array{missing: string[], failed: array<string, int>, blocker: string|null}
	 *
	 * @since 6.19.0
	 */
	public function get_core_font_health(): array {
		$downloader = $this->get_font_downloader();
		$failed     = $downloader->get_failed_files();

		/* Finding the missing files scans every form, and Site Health waits on it, so only do so when it can matter */
		$missing = $failed !== [] || $downloader->is_auto_install_overridden() ? $this->get_required_fonts()->get_missing_files() : [];

		return [
			'missing' => $missing,
			'failed'  => array_intersect_key( $failed, array_flip( $missing ) ),
			'blocker' => $downloader->get_auto_install_blocker( $missing ),
		];
	}

	/**
	 * The injected downloader, else the registered model
	 *
	 * @since 6.19.0
	 */
	protected function get_font_downloader(): Model_Font_Downloader {
		return $this->font_downloader ?? GPDFAPI::get_mvc_class( 'Model_Font_Downloader' );
	}

	/**
	 * The injected required fonts, else the registered model
	 *
	 * @since 6.19.0
	 */
	protected function get_required_fonts(): Model_Required_Fonts {
		return $this->required_fonts ?? GPDFAPI::get_mvc_class( 'Model_Required_Fonts' );
	}

	/**
	 * The injected font sources, else the registered model
	 *
	 * @since 6.19.0
	 */
	protected function get_font_sources(): Model_Font_Sources {
		return $this->font_sources ?? GPDFAPI::get_mvc_class( 'Model_Font_Sources' );
	}

	/**
	 * Fonts mPDF falls back to, in order, for characters the PDF's font lacks. Only in the copied report.
	 *
	 * @return array{label: string, label_export: string, value: string, export_only: bool}
	 *
	 * @since 6.19.0
	 */
	protected function get_substitution_fonts(): array {
		$fonts = $this->get_font_sources()->get_substitution_fonts();

		return [
			'label'        => esc_html__( 'Substitution Fonts', 'gravity-pdf' ),
			'label_export' => 'Substitution Fonts',
			'value'        => esc_html( implode( ', ', $fonts ) ),
			'export_only'  => true,
		];
	}

	/**
	 * Get array report structure of Gravity PDF System Report
	 *
	 * @return array
	 * @since 6.0
	 * @since 6.17.0 Keyed by section name, filtered by `gfpdf_system_status_report_sections`
	 * @since 6.19.0 Each check row says whether it passed, and each setting row whether it's unchanged
	 */
	protected function get_report_items(): array {
		$defaults = $this->get_setting_defaults();
		$detected = Deprecation::group_signals( Deprecation::refresh_signals() );
		$items    = [];

		/* Keyed by group, which is what the matching report sections are named after */
		foreach ( View_System_Report::get_deprecated_groups() as $group => $names ) {
			if ( isset( $detected[ $group ] ) ) {
				$items[ $group ] = $this->get_deprecated_feature_items( $detected[ $group ] );
			} else {
				$items[ $group ] = [ 'none' => $this->get_no_deprecated_features_item( $names ) ];
			}
		}

		$items['php'] = [
			'memory'          => $this->get_memory_limit(),
			'allow_url_fopen' => $this->get_allow_url_fopen(),
			'charset'         => $this->get_charset(),
		];

		$items['directories'] = $this->get_directory_items();
		$items['global']      = $this->get_global_items( $defaults );
		$items['security']    = $this->get_security_items( $defaults );
		$items['cache']       = $this->get_cache_items( $defaults );

		$items = $this->apply_deprecated_report_items_filter( $items );
		$items = apply_filters( 'gfpdf_system_status_report_sections', $items );

		/* Added after the filters, so an add-on looking for this row only finds it when a core template is outdated */
		if ( ! isset( $items['directories']['outdated_templates'] ) ) {
			$items['directories']['outdated_templates'] = $this->get_outdated_templates( [] );
		}

		/* Core's outdated templates end in a line break for an add-on's own; drop it when none were added */
		if ( is_string( $items['directories']['outdated_templates']['value'] ?? null ) ) {
			$items['directories']['outdated_templates']['value'] = preg_replace( '/<br>$/', '', $items['directories']['outdated_templates']['value'] );
		}

		return $items;
	}

	/**
	 * The working directory, its folders and the checks on them
	 *
	 * @since 6.19.0
	 */
	protected function get_directory_items(): array {
		$install    = GPDFAPI::get_mvc_class( 'Model_Install' );
		$unwritable = $install instanceof Model_Install ? $install->create_folder_structures() : [];
		$path       = $this->templates->get_template_path();
		$url        = $this->templates->get_template_url();

		$items = [
			'pdf_working_directory' => [
				'label'        => esc_html__( 'PDF Working Directory', 'gravity-pdf' ),
				'label_export' => 'PDF Working Directory',
				'value'        => esc_html( $path ) . '<br>' . esc_html( $url ),
				'value_export' => $path . ' (' . $url . ')',
			],

			'folders'               => $this->get_folders(),
			'folder_permissions'    => $this->get_folder_permissions( $unwritable ),
			'temp_folder_protected' => $this->check_temp_folder_permission(),
			'core_fonts'            => $this->get_core_fonts(),
			'core_font_downloads'   => $this->get_core_font_downloads(),
			'substitution_fonts'    => $this->get_substitution_fonts(),
		];

		$outdated = $this->check_core_template_override_versions();
		if ( $outdated !== [] ) {
			$items['outdated_templates'] = $this->get_outdated_templates( $outdated );
		}

		return $items;
	}

	/**
	 * The General settings rows
	 *
	 * @param array $defaults Each setting's default, by ID
	 *
	 * @since 6.19.0
	 */
	protected function get_global_items( array $defaults ): array {
		$view       = $this->getController()->view;
		$action     = $this->options->get_option( 'default_action', 'View' ) === 'Download' ? 'Download' : 'View';
		$background = $this->is_on( $this->options->get_option( 'background_processing', 'No' ) );
		$debug      = $this->is_on( $this->options->get_option( 'debug_mode', 'No' ) );

		$entry_view = [
			'label'        => esc_html__( 'Entry View', 'gravity-pdf' ),
			'label_export' => 'Entry View',
			'value'        => $action === 'View' ? esc_html__( 'View', 'gravity-pdf' ) : esc_html__( 'Download', 'gravity-pdf' ),
			'value_export' => $action,
		];

		$background_processing = [
			'label'        => esc_html__( 'Background Processing', 'gravity-pdf' ),
			'label_export' => 'Background Processing',
			'value'        => $view->get_on_off( $background ),
			'value_export' => $background ? 'On' : 'Off',
		];

		$debug_mode = [
			'label'        => esc_html__( 'Debug Mode', 'gravity-pdf' ),
			'label_export' => 'Debug Mode',
			'value'        => $view->get_on_off( $debug ),
			'value_export' => $debug ? 'On' : 'Off',
		];

		return [
			'pdf_entry_list_action'         => $this->mark_default( $entry_view, $action === ( $defaults['default_action'] ?? null ) ),
			'background_processing_enabled' => $this->mark_default( $background_processing, $background === $this->is_on( $defaults['background_processing'] ?? null ) ),
			'debug_mode_enabled'            => $this->mark_default( $debug_mode, $debug === $this->is_on( $defaults['debug_mode'] ?? null ) ),
		];
	}

	/**
	 * The Security settings rows
	 *
	 * @param array $defaults Each setting's default, by ID
	 *
	 * @since 6.19.0
	 */
	protected function get_security_items( array $defaults ): array {
		$capabilities = array_values( (array) $this->options->get_option( 'admin_capabilities', [ 'gravityforms_view_entries' ] ) );
		$timeout      = $this->options->get_logged_out_timeout();

		$user_restriction = [
			'label'        => esc_html__( 'User Restriction', 'gravity-pdf' ),
			'label_export' => 'User Restriction',
			'value'        => implode( '<br>', array_map( 'esc_html', $capabilities ) ),
			'value_export' => implode( ', ', $capabilities ),
		];

		$logged_out_timeout = [
			'label'        => esc_html__( 'Logged Out Timeout', 'gravity-pdf' ),
			'label_export' => 'Logged Out Timeout',
			/* translators: %d: Number of minutes */
			'value'        => esc_html( sprintf( _n( '%d minute', '%d minutes', $timeout, 'gravity-pdf' ), $timeout ) ),
			'value_export' => $this->get_count( $timeout, 'minute' ),
		];

		return [
			'user_restrictions'  => $this->mark_default( $user_restriction, $this->is_same_list( $capabilities, (array) ( $defaults['admin_capabilities'] ?? [] ) ) ),
			'logged_out_timeout' => $this->mark_default( $logged_out_timeout, $timeout === (int) ( $defaults['logged_out_timeout'] ?? 0 ) ),
		];
	}

	/**
	 * The PDF Cache rows. They read the sweep's saved state, so the report never walks the cache.
	 *
	 * @param array $defaults Each setting's default, by ID
	 *
	 * @since 7.0
	 * @since 6.19.0 Takes the settings' defaults, and the cleanup is a check
	 */
	protected function get_cache_items( array $defaults ): array {
		$enabled = Cache::is_enabled_by_setting();
		$ttl     = Cache::get_ttl();
		$hours   = round( $ttl / HOUR_IN_SECONDS, 2 );

		$pdf_cache = [
			'label'        => esc_html__( 'PDF Cache', 'gravity-pdf' ),
			'label_export' => 'PDF Cache',
			'value'        => esc_html__( 'Off', 'gravity-pdf' ),
			'value_export' => 'Off',
		];

		if ( $enabled ) {
			/* translators: %s: How long a PDF is cached for, e.g. "12 hours" */
			$pdf_cache['value']        = esc_html( sprintf( __( 'On, %s', 'gravity-pdf' ), human_time_diff( 0, $ttl ) ) );
			$pdf_cache['value_export'] = 'On, ' . $this->get_count( $hours, 'hour' );
		}

		/* Unchanged while the cache keeps its default state and, when on, its default duration */
		$is_default = $enabled === $this->is_on( $defaults['pdf_cache'] ?? null ) && ( ! $enabled || $hours === (float) ( $defaults['cache_duration'] ?? 0 ) );

		return [
			'pdf_cache'     => $this->mark_default( $pdf_cache, $is_default ),
			'cache_cleanup' => $this->get_cache_cleanup(),
		];
	}

	/**
	 * The PDF Cache Cleanup check: when the last cleanup ran, and whether WP-Cron is keeping up
	 *
	 * @since 6.19.0
	 */
	protected function get_cache_cleanup(): array {
		$view  = $this->getController()->view;
		$state = $this->pdf_cache->get_sweep_state();
		$pass  = $state['last_pass'];
		$fix   = '';

		$row = [
			'label'        => esc_html__( 'PDF Cache Cleanup', 'gravity-pdf' ),
			'label_export' => 'PDF Cache Cleanup',
			'value'        => esc_html__( 'Never run', 'gravity-pdf' ),
			'value_export' => 'Never run',
		];

		$summary = __( 'cache not cleaned yet', 'gravity-pdf' );

		if ( $state['last_complete_at'] > 0 && $pass !== [] ) {
			$ago = human_time_diff( $state['last_complete_at'] );

			/* translators: %s: How long ago, e.g. "2 hours" */
			$row['value'] = esc_html( sprintf( __( 'Last ran %s ago', 'gravity-pdf' ), $ago ) );

			$row['value_export'] = sprintf(
				'%1$s UTC. Removed %2$s (%3$s), kept %4$s (%5$s).',
				gmdate( 'Y-m-d H:i:s', $state['last_complete_at'] ),
				$this->get_count( $pass['files_reaped'], 'file' ),
				$this->get_megabytes( $pass['bytes_reaped'] ),
				$pass['files_left'],
				$this->get_megabytes( $pass['bytes_left'] )
			);

			/* translators: %s: How long ago, e.g. "2 hours" */
			$summary = sprintf( __( 'cache cleaned %s ago', 'gravity-pdf' ), $ago );
		}

		if ( $this->is_cache_cleanup_stalled() ) {
			$fix = $view->get_fix( __( "WP-Cron isn't removing expired PDFs. Ask your web host to check that it runs.", 'gravity-pdf' ) );
		}

		return $view->get_check( $row, $summary, $fix );
	}

	/**
	 * Whether the cache holds PDFs but WP-Cron isn't removing the expired ones
	 *
	 * The last *complete* cleanup isn't checked, because a quiet site can go days without needing one
	 *
	 * @since 7.0
	 */
	public function is_cache_cleanup_stalled(): bool {
		$last_slice_at = $this->pdf_cache->get_sweep_state()['last_slice_at'];

		/* A missed sweep, or an hourly cleanup more than an hour late */
		$next    = wp_next_scheduled( 'gfpdf_cleanup_tmp_dir' );
		$overdue = ( $last_slice_at > 0 && $last_slice_at < time() - DAY_IN_SECONDS ) || ( $next !== false && $next < time() - HOUR_IN_SECONDS );

		return $overdue && $this->pdf_cache->has_cached_pdfs();
	}

	/**
	 * Pass the sections through the positional `gfpdf_system_status_report_items` filter
	 *
	 * Fired directly rather than through `Deprecation`, which only detects and reports: this filter still works and
	 * has no removal scheduled, so there is nothing for the registry to warn a site about.
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
	 * Build the rows for one of the deprecation sections, one per feature in use on this site
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
	 * The passing row of a deprecation group with nothing detected
	 *
	 * @param array $names The group's names, from View_System_Report::get_deprecated_groups()
	 *
	 * @since 6.19.0
	 */
	protected function get_no_deprecated_features_item( array $names ): array {
		/* translators: %s: The group name, as get_deprecated_groups() gives it */
		$label = sprintf( __( '%s Features', 'gravity-pdf' ), $names['label'] );

		$row = [
			'label'        => esc_html( $label ),
			'label_export' => $label,
			'value'        => esc_html__( 'None', 'gravity-pdf' ),
			'value_export' => 'None',
		];

		return $this->getController()->view->get_check( $row, $names['none'] );
	}

	/**
	 * The memory limit PDFs get. It's read as Gravity PDF loads, before WordPress raises it for admin screens.
	 *
	 * @since 6.0
	 * @since 6.19.0 Returns the PDF Memory Limit check
	 */
	protected function get_memory_limit(): array {
		$view   = $this->getController()->view;
		$memory = $this->status->get_ram( $this->data->memory_limit );
		$fix    = '';

		$row = [
			'label'        => esc_html__( 'PDF Memory Limit', 'gravity-pdf' ),
			'label_export' => 'PDF Memory Limit',
			'value'        => esc_html__( 'Unlimited', 'gravity-pdf' ),
			'value_export' => 'Unlimited',
		];

		if ( $memory !== -1 ) {
			$row['value']        = $memory . 'MB';
			$row['value_export'] = $memory . 'MB';
		}

		if ( $memory !== -1 && $memory < 128 ) {
			$fix = $view->get_fix( __( 'Raise it to at least 128MB.', 'gravity-pdf' ), 'https://docs.gravitypdf.com/users/increasing-memory-limit', __( 'How to increase it', 'gravity-pdf' ) );
		}

		/* translators: %s: The memory limit, e.g. "512MB" */
		return $view->get_check( $row, sprintf( __( 'PDF memory %s', 'gravity-pdf' ), $row['value'] ), $fix );
	}

	/**
	 * @since 6.0
	 * @since 6.19.0 Returns the allow_url_fopen check
	 */
	protected function get_allow_url_fopen(): array {
		$view    = $this->getController()->view;
		$enabled = $this->data->allow_url_fopen;
		$fix     = '';

		$row = [
			'label'        => 'allow_url_fopen',
			'label_export' => 'allow_url_fopen',
			'value'        => $view->get_on_off( $enabled ),
			'value_export' => $enabled ? 'On' : 'Off',
		];

		if ( ! $enabled ) {
			$fix = $view->get_fix( __( 'Images may be missing from PDFs. Ask your web host to turn it on.', 'gravity-pdf' ) );
		}

		return $view->get_check( $row, __( 'allow_url_fopen on', 'gravity-pdf' ), $fix );
	}

	/**
	 * PHP's charset and internal encoding, only shown on screen when one of them isn't UTF-8
	 *
	 * @since 6.19.0
	 */
	protected function get_charset(): array {
		$charset  = (string) ini_get( 'default_charset' );
		$encoding = (string) ini_get( 'internal_encoding' );
		$value    = $charset . ' / ' . ( $encoding !== '' ? $encoding : $charset );

		return [
			'label'        => esc_html__( 'PHP Charset / Encoding', 'gravity-pdf' ),
			'label_export' => 'PHP Charset / Encoding',
			'value'        => esc_html( $value ),
			'value_export' => $value,
			'export_only'  => strtoupper( $value ) === 'UTF-8 / UTF-8',
		];
	}

	/**
	 * The fonts, temporary and mPDF folders, relative to the PDF Working Directory while they're all inside it
	 *
	 * @since 6.19.0
	 */
	protected function get_folders(): array {
		$base    = trailingslashit( wp_normalize_path( $this->templates->get_template_path() ) );
		$root    = trailingslashit( wp_normalize_path( $this->data->template_location ) );
		$folders = [
			'Fonts'     => $this->data->template_font_location,
			'Temporary' => $this->data->template_tmp_location,
			'mPDF'      => $this->misc->get_mpdf_cache_location(),
		];
		$names   = [
			'Fonts'     => __( 'Fonts', 'gravity-pdf' ),
			'Temporary' => __( 'Temporary', 'gravity-pdf' ),
			'mPDF'      => 'mPDF',
		];

		$relative = true;
		foreach ( $folders as $name => $folder ) {
			$folders[ $name ] = trailingslashit( wp_normalize_path( $folder ) );
			$relative         = $relative && strpos( $folders[ $name ], $base ) === 0;
		}

		$value  = [];
		$export = [];
		if ( $relative ) {
			$value[] = esc_html__( 'Relative to the PDF Working Directory:', 'gravity-pdf' );
		}

		foreach ( $folders as $name => $folder ) {
			$shown    = $relative ? substr( $folder, strlen( $base ) ) : $folder;
			$value[]  = esc_html( $names[ $name ] ) . ': <code>' . esc_html( $shown ) . '</code>';
			$export[] = $name . ': ' . $shown;
		}

		$row = [
			'label'        => esc_html__( 'Folders', 'gravity-pdf' ),
			'label_export' => 'Folders',
			'value'        => implode( '<br>', $value ),
			'value_export' => implode( ', ', $export ),
		];

		/* Unchanged while none of the folder filters moved them */
		$is_default = $folders === [
			'Fonts'     => $root . 'fonts/',
			'Temporary' => $root . 'tmp/',
			'mPDF'      => $root . 'tmp/mpdf/',
		];

		return $this->mark_default( $row, $is_default );
	}

	/**
	 * Whether the temporary folder can't be reached from the web
	 *
	 * @since 6.0
	 * @since 6.19.0 Returns the Temporary Folder Protected check
	 */
	protected function check_temp_folder_permission(): array {
		$view      = $this->getController()->view;
		$protected = $this->test_public_tmp_directory_access();
		$fix       = '';

		$row = [
			'label'        => esc_html__( 'Temporary Folder Protected', 'gravity-pdf' ),
			'label_export' => 'Temporary Folder Protected',
			'value'        => $protected ? esc_html__( 'Yes', 'gravity-pdf' ) : esc_html__( 'No', 'gravity-pdf' ),
			'value_export' => $protected ? 'Yes' : 'No',
		];

		if ( ! $protected ) {
			$fix = $view->get_fix( __( "The temporary folder can be reached from the web. Move it outside the site's public folder.", 'gravity-pdf' ), 'https://docs.gravitypdf.com/developers/filters/gfpdf_tmp_location/', __( 'How to move it', 'gravity-pdf' ) );
		}

		return $view->get_check( $row, __( 'temporary folder protected', 'gravity-pdf' ), $fix );
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
	 * The folders the check made for this report couldn't write to, in full. Running it here also clears a fixed folder
	 * from the admin notice
	 *
	 * @param string[] $folders
	 *
	 * @since 6.18.0
	 * @since 6.19.0 Returns the Folder Permissions check, which covers the temporary folder too
	 */
	protected function get_folder_permissions( array $folders ): array {
		$view = $this->getController()->view;
		$fix  = '';

		$row = [
			'label'        => esc_html__( 'Folder Permissions', 'gravity-pdf' ),
			'label_export' => 'Folder Permissions',
			'value'        => esc_html__( 'Writable', 'gravity-pdf' ),
			'value_export' => 'Writable',
		];

		if ( $folders !== [] ) {
			$row['value']        = esc_html__( 'Not writable:', 'gravity-pdf' ) . '<br>' . $view->get_code_lines( $folders );
			$row['value_export'] = 'Not writable: ' . implode( ', ', $folders );

			$fix = $view->get_fix( __( 'Ask your web host to make these folders writable.', 'gravity-pdf' ) );
		}

		return $view->get_check( $row, __( 'folders writable', 'gravity-pdf' ), $fix );
	}

	/**
	 * The core template overrides older than the core template, by file name
	 *
	 * @return array<string, string[]> The override's version, then the core template's
	 *
	 * @since 6.0
	 * @since 6.19.0 Returns the outdated overrides rather than the row
	 */
	protected function check_core_template_override_versions(): array {
		$outdated = [];

		foreach ( $this->get_template_versions( $this->templates->get_core_pdf_templates() ) as $path => $core_version ) {
			$template = $this->templates->get_template_info_by_id( basename( $path, '.php' ) );
			if ( version_compare( $core_version, $template['version'], '>' ) ) {
				$outdated[ basename( $template['path'] ) ] = [ $template['version'], $core_version ];
			}
		}

		return $outdated;
	}

	/**
	 * The Outdated Templates check. A failing value ends in a line break, so templates an add-on appends start on a line
	 * of their own.
	 *
	 * @param array<string, string[]> $outdated See self::check_core_template_override_versions()
	 *
	 * @since 6.19.0
	 */
	protected function get_outdated_templates( array $outdated ): array {
		$view = $this->getController()->view;
		$fix  = '';

		$row = [
			'label'        => esc_html__( 'Outdated Templates', 'gravity-pdf' ),
			'label_export' => 'Outdated Templates',
			'value'        => esc_html__( 'None', 'gravity-pdf' ),
			'value_export' => 'None',
		];

		if ( $outdated !== [] ) {
			$value  = '';
			$export = [];

			foreach ( $outdated as $file => $versions ) {
				/* translators: 1: The override's version, 2: The core template's version */
				$value   .= '<code>' . esc_html( $file ) . '</code> ' . esc_html( sprintf( __( '%1$s, current %2$s', 'gravity-pdf' ), $versions[0], $versions[1] ) ) . '<br>';
				$export[] = sprintf( '%1$s %2$s (current %3$s)', $file, $versions[0], $versions[1] );
			}

			$row['value']        = $value;
			$row['value_export'] = implode( ', ', $export ) . "\n";

			$fix = $view->get_fix( __( 'Update these template overrides.', 'gravity-pdf' ), 'https://docs.gravitypdf.com/developers/template-hierarchy', __( 'How to update', 'gravity-pdf' ) );
		}

		return $view->get_check( $row, __( 'templates up to date', 'gravity-pdf' ), $fix );
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
	 * Mark an unchanged setting "(default)" in the copied report
	 *
	 * @since 6.19.0
	 */
	protected function mark_default( array $row, bool $is_default ): array {
		if ( $is_default ) {
			$row['value_export'] .= ' (default)';
		}

		return $row;
	}

	/**
	 * Each setting's default, from the settings registry
	 *
	 * @return array<string, mixed> By setting ID
	 *
	 * @since 6.19.0
	 */
	protected function get_setting_defaults(): array {
		$defaults = [];

		foreach ( $this->options->get_registered_fields() as $fields ) {
			foreach ( (array) $fields as $field ) {
				if ( isset( $field['id'] ) && array_key_exists( 'std', $field ) ) {
					$defaults[ $field['id'] ] = $field['std'];
				}
			}
		}

		return $defaults;
	}

	/**
	 * Whether a toggle is on. Toggles store Yes or No, but declare '0' as their default.
	 *
	 * @param mixed $value
	 *
	 * @since 6.19.0
	 */
	protected function is_on( $value ): bool {
		return in_array( $value, [ 'Yes', '1', 1, true ], true );
	}

	/**
	 * Whether two lists hold the same values, in any order
	 *
	 * @since 6.19.0
	 */
	protected function is_same_list( array $a, array $b ): bool {
		$a = array_values( $a );
		$b = array_values( $b );
		sort( $a );
		sort( $b );

		return $a === $b;
	}

	/**
	 * A count and its unit, in English for the copied report
	 *
	 * @param int|float $count
	 *
	 * @since 6.19.0
	 */
	protected function get_count( $count, string $unit ): string {
		return $count . ' ' . $unit . ( (float) $count === 1.0 ? '' : 's' );
	}

	/**
	 * A size in MB, to two decimal places
	 *
	 * @since 6.19.0
	 */
	protected function get_megabytes( int $bytes ): string {
		return number_format( $bytes / MB_IN_BYTES, 2, '.', '' ) . ' MB';
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
