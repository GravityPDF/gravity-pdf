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
	 * The ID of the "Core Fonts failed" report row
	 *
	 * @since 6.19.0
	 */
	const FONTS_FAILED_ANCHOR = 'gfpdf-font-download-failed';

	/**
	 * The ID of the "Core Font downloads" report row
	 *
	 * @since 6.19.0
	 */
	const FONT_DOWNLOADS_ANCHOR = 'gfpdf-core-font-downloads';

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
	 * @since 7.0 Sorts the report sections into the Checks and Configuration tables
	 */
	public function build_gravitypdf_report(): array {
		$structure = $this->get_report_structure();
		$tables    = ( new Model_System_Report_Layout() )->get_tables( $this->get_report_items() );

		foreach ( $structure[0]['tables'] as $index => $table ) {
			$structure[0]['tables'][ $index ]['items'] = $tables[ $table['id'] ];
		}

		return $structure;
	}

	/**
	 * Set up array structure of Gravity PDF System Report
	 *
	 * @since 6.0
	 * @since 7.0 Two tables, Checks and Configuration
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
	 * @since 7.0
	 */
	protected function get_core_fonts(): array {
		$view      = $this->getController()->view;
		$health    = $this->get_core_font_health();
		$abandoned = $this->get_font_downloader()->get_failed_files();
		$failed    = array_keys( $abandoned );
		$missing   = $health['blocker'] !== null ? array_values( array_diff( $health['missing'], $failed ) ) : [];
		$value     = [];
		$export    = [];
		$fix       = '';

		if ( $failed !== [] ) {
			$gave_up = [];
			foreach ( $abandoned as $file => $time ) {
				$gave_up[] = sprintf( '%s (gave up %s)', $file, gmdate( 'Y-m-d', $time ) );
			}

			/* translators: %d: How many font files */
			$value[]  = esc_html( sprintf( __( '%d failed:', 'gravity-pdf' ), count( $failed ) ) ) . '<br>' . $view->get_code_lines( $failed );
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

		return $view->get_check(
			[
				'label'        => $this->get_anchored_label( self::FONTS_FAILED_ANCHOR, esc_html__( 'Core Fonts', 'gravity-pdf' ) ),
				'label_export' => 'Core Fonts',
				'value'        => $value !== [] ? implode( '<br>', $value ) : esc_html__( 'None failed', 'gravity-pdf' ),
				'value_export' => $export !== [] ? implode( '; ', $export ) : 'None failed',
			],
			__( 'no core fonts failed', 'gravity-pdf' ),
			$fix !== '' ? $view->get_fix( $fix, View_Actions::CORE_FONTS_DOCS_URL, __( 'Install the fonts by hand', 'gravity-pdf' ) ) : ''
		);
	}

	/**
	 * Whether core fonts may be downloaded, and what turned them off
	 *
	 * @since 6.19.0
	 * @since 7.0 Labelled Font Installer
	 */
	protected function get_core_font_downloads(): array {
		$status = $this->get_font_downloader()->get_auto_install_status();

		return $this->get_configuration_item(
			$this->get_anchored_label( self::FONT_DOWNLOADS_ANCHOR, esc_html__( 'Font Installer', 'gravity-pdf' ) ),
			'Font Installer',
			esc_html( $status ),
			$status,
			$status === 'On'
		);
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
	 * Fonts mPDF falls back to, in order, for characters the PDF's font lacks
	 *
	 * @since 6.19.0
	 * @since 7.0 Only in the copied report
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
	 * @since 7.0 Every check row says whether it passed, and the settings rows say whether they're unchanged
	 */
	protected function get_report_items(): array {
		$items    = [];
		$install  = GPDFAPI::get_mvc_class( 'Model_Install' );
		$detected = Deprecation::group_signals( Deprecation::refresh_signals() );
		$defaults = $this->get_setting_defaults();

		/* Keyed by group, which is what the matching report sections are named after */
		foreach ( View_System_Report::get_deprecated_groups() as $group => $names ) {
			$items[ $group ] = isset( $detected[ $group ] ) ? $this->get_deprecated_feature_items( $detected[ $group ] ) : [ 'none' => $this->get_no_deprecated_features_item( $names ) ];
		}

		$items['php'] = [
			'memory'          => $this->get_memory_limit(),
			'allow_url_fopen' => $this->get_allow_url_fopen(),
			'charset'         => $this->get_charset(),
		];

		$path = $this->templates->get_template_path();
		$url  = $this->templates->get_template_url();

		$items['directories'] = [
			'pdf_working_directory' => [
				'label'        => esc_html__( 'PDF Working Directory', 'gravity-pdf' ),
				'label_export' => 'PDF Working Directory',
				'value'        => esc_html( $path ) . '<br>' . esc_html( $url ),
				'value_export' => $path . ' (' . $url . ')',
			],

			'folders'               => $this->get_folders(),
			'folder_permissions'    => $this->get_folder_permissions( $install instanceof Model_Install ? $install->create_folder_structures() : [] ),
			'temp_folder_protected' => $this->check_temp_folder_permission(),
			'core_fonts'            => $this->get_core_fonts(),
			'core_font_downloads'   => $this->get_core_font_downloads(),
			'substitution_fonts'    => $this->get_substitution_fonts(),
		];

		$outdated = $this->check_core_template_override_versions();
		if ( $outdated !== [] ) {
			$items['directories']['outdated_templates'] = $this->get_outdated_templates( $outdated );
		}

		$action     = $this->options->get_option( 'default_action', 'View' ) === 'Download' ? 'Download' : 'View';
		$background = $this->is_on( $this->options->get_option( 'background_processing', 'No' ) );
		$debug      = $this->is_on( $this->options->get_option( 'debug_mode', 'No' ) );

		$items['global'] = [
			'pdf_entry_list_action'         => $this->get_configuration_item(
				esc_html__( 'Entry View', 'gravity-pdf' ),
				'Entry View',
				$action === 'View' ? esc_html__( 'View', 'gravity-pdf' ) : esc_html__( 'Download', 'gravity-pdf' ),
				$action,
				$action === ( $defaults['default_action'] ?? null )
			),

			'background_processing_enabled' => $this->get_configuration_item(
				esc_html__( 'Background Processing', 'gravity-pdf' ),
				'Background Processing',
				$background ? esc_html__( 'On', 'gravity-pdf' ) : esc_html__( 'Off', 'gravity-pdf' ),
				$background ? 'On' : 'Off',
				$background === $this->is_on( $defaults['background_processing'] ?? null )
			),

			'debug_mode_enabled'            => $this->get_configuration_item(
				esc_html__( 'Debug Mode', 'gravity-pdf' ),
				'Debug Mode',
				$debug ? esc_html__( 'On', 'gravity-pdf' ) : esc_html__( 'Off', 'gravity-pdf' ),
				$debug ? 'On' : 'Off',
				$debug === $this->is_on( $defaults['debug_mode'] ?? null )
			),
		];

		$capabilities = array_values( (array) $this->options->get_option( 'admin_capabilities', [ 'gravityforms_view_entries' ] ) );
		$timeout      = $this->options->get_logged_out_timeout();

		$items['security'] = [
			'user_restrictions'  => $this->get_configuration_item(
				esc_html__( 'User Restriction', 'gravity-pdf' ),
				'User Restriction',
				implode( '<br>', array_map( 'esc_html', $capabilities ) ),
				implode( ', ', $capabilities ),
				$this->is_same_list( $capabilities, (array) ( $defaults['admin_capabilities'] ?? [] ) )
			),

			'logged_out_timeout' => $this->get_configuration_item(
				esc_html__( 'Logged Out Timeout', 'gravity-pdf' ),
				'Logged Out Timeout',
				/* translators: %d: Number of minutes */
				esc_html( sprintf( _n( '%d minute', '%d minutes', $timeout, 'gravity-pdf' ), $timeout ) ),
				$this->get_count( $timeout, 'minute' ),
				$timeout === (int) ( $defaults['logged_out_timeout'] ?? 0 )
			),
		];

		$items['cache'] = $this->get_cache_items( $defaults );

		$items = $this->apply_deprecated_report_items_filter( $items );
		$items = apply_filters( 'gfpdf_system_status_report_sections', $items );

		/* Only added once the filters have run, as an add-on that finds the row appends its own outdated templates */
		if ( is_array( $items ) && ! isset( $items['directories']['outdated_templates'] ) ) {
			$items['directories']['outdated_templates'] = $this->get_outdated_templates( [] );
		}

		return $items;
	}

	/**
	 * The PDF Cache rows. They read the sweep's saved state, so the report never walks the cache.
	 *
	 * @param array $defaults Each setting's default, by ID
	 *
	 * @since 7.0
	 */
	protected function get_cache_items( array $defaults ): array {
		$view    = $this->getController()->view;
		$enabled = Cache::is_enabled_by_setting();
		$ttl     = Cache::get_ttl();
		$hours   = round( $ttl / HOUR_IN_SECONDS, 2 );
		$state   = $this->pdf_cache->get_sweep_state();
		$pass    = $state['last_pass'];
		$ran     = $state['last_complete_at'] > 0 && $pass !== [];
		$ago     = $ran ? human_time_diff( $state['last_complete_at'] ) : '';

		return [
			'pdf_cache'     => $this->get_configuration_item(
				esc_html__( 'PDF Cache', 'gravity-pdf' ),
				'PDF Cache',
				/* translators: %s: How long a PDF is cached for, e.g. "12 hours" */
				$enabled ? esc_html( sprintf( __( 'On, %s', 'gravity-pdf' ), human_time_diff( 0, $ttl ) ) ) : esc_html__( 'Off', 'gravity-pdf' ),
				$enabled ? 'On, ' . $this->get_count( $hours, 'hour' ) : 'Off',
				$enabled === $this->is_on( $defaults['pdf_cache'] ?? null ) && ( ! $enabled || $hours === (float) ( $defaults['cache_duration'] ?? 0 ) )
			),

			'cache_cleanup' => $view->get_check(
				[
					'label'        => esc_html__( 'PDF Cache Cleanup', 'gravity-pdf' ),
					'label_export' => 'PDF Cache Cleanup',
					/* translators: %s: How long ago, e.g. "2 hours" */
					'value'        => $ran ? esc_html( sprintf( __( 'Last ran %s ago', 'gravity-pdf' ), $ago ) ) : esc_html__( 'Never run', 'gravity-pdf' ),
					'value_export' => $ran ? sprintf(
						'%1$s UTC. Removed %2$s (%3$s), kept %4$s (%5$s).',
						gmdate( 'Y-m-d H:i:s', $state['last_complete_at'] ),
						$this->get_count( $pass['files_reaped'], 'file' ),
						$this->get_megabytes( $pass['bytes_reaped'] ),
						$pass['files_left'],
						$this->get_megabytes( $pass['bytes_left'] )
					) : 'Never run',
				],
				/* translators: %s: How long ago, e.g. "2 hours" */
				$ran ? sprintf( __( 'cache cleaned %s ago', 'gravity-pdf' ), $ago ) : __( 'cache not cleaned yet', 'gravity-pdf' ),
				$this->is_cache_cleanup_stalled() ? $view->get_fix( __( "WP-Cron isn't removing expired PDFs. Ask your web host to check that it runs.", 'gravity-pdf' ) ) : ''
			),
		];
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
	 * The passing row of a deprecation group with nothing detected
	 *
	 * @param array $names The group's names, from View_System_Report::get_deprecated_groups()
	 *
	 * @since 7.0
	 */
	protected function get_no_deprecated_features_item( array $names ): array {
		/* translators: %s: The group name, as get_deprecated_groups() gives it */
		$label = sprintf( __( '%s Features', 'gravity-pdf' ), $names['label'] );

		return $this->getController()->view->get_check(
			[
				'label'        => esc_html( $label ),
				'label_export' => $label,
				'value'        => esc_html__( 'None', 'gravity-pdf' ),
				'value_export' => 'None',
			],
			$names['none']
		);
	}

	/**
	 * The memory limit PDFs get. It's read as Gravity PDF loads, before WordPress raises it for admin screens.
	 *
	 * @since 6.0
	 * @since 7.0 Returns the PDF Memory Limit check
	 */
	protected function get_memory_limit(): array {
		$view      = $this->getController()->view;
		$memory    = $this->status->get_ram( $this->data->memory_limit );
		$unlimited = $memory === -1;
		$limit     = $unlimited ? 'Unlimited' : $memory . 'MB';

		return $view->get_check(
			[
				'label'        => esc_html__( 'PDF Memory Limit', 'gravity-pdf' ),
				'label_export' => 'PDF Memory Limit',
				'value'        => $unlimited ? esc_html__( 'Unlimited', 'gravity-pdf' ) : $limit,
				'value_export' => $limit,
			],
			/* translators: %s: The memory limit, e.g. "512MB" or "unlimited" */
			sprintf( __( 'PDF memory %s', 'gravity-pdf' ), $unlimited ? __( 'unlimited', 'gravity-pdf' ) : $limit ),
			$unlimited || $memory >= 128 ? '' : $view->get_fix( __( 'Raise it to at least 128MB.', 'gravity-pdf' ), 'https://docs.gravitypdf.com/users/increasing-memory-limit', __( 'How to increase it', 'gravity-pdf' ) )
		);
	}

	/**
	 * @since 6.0
	 * @since 7.0 Returns the allow_url_fopen check
	 */
	protected function get_allow_url_fopen(): array {
		$view    = $this->getController()->view;
		$enabled = $this->data->allow_url_fopen;

		return $view->get_check(
			[
				'label'        => 'allow_url_fopen',
				'label_export' => 'allow_url_fopen',
				'value'        => $enabled ? esc_html__( 'On', 'gravity-pdf' ) : esc_html__( 'Off', 'gravity-pdf' ),
				'value_export' => $enabled ? 'On' : 'Off',
			],
			__( 'allow_url_fopen on', 'gravity-pdf' ),
			$enabled ? '' : $view->get_fix( __( 'Images may be missing from PDFs. Ask your web host to turn it on.', 'gravity-pdf' ) )
		);
	}

	/**
	 * PHP's charset and internal encoding, only shown on screen when one of them isn't UTF-8
	 *
	 * @since 7.0
	 */
	protected function get_charset(): array {
		$charset  = (string) ini_get( 'default_charset' );
		$encoding = (string) ini_get( 'internal_encoding' ) ?: $charset; //phpcs:ignore Universal.Operators.DisallowShortTernary.Found
		$value    = $charset . ' / ' . $encoding;

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
	 * @since 7.0
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

		$folders = array_map(
			static function ( $folder ) {
				return trailingslashit( wp_normalize_path( $folder ) );
			},
			$folders
		);

		$relative = true;
		foreach ( $folders as $folder ) {
			$relative = $relative && strpos( $folder, $base ) === 0;
		}

		$value  = $relative ? [ esc_html__( 'Relative to the PDF Working Directory:', 'gravity-pdf' ) ] : [];
		$export = [];
		foreach ( $folders as $name => $folder ) {
			$shown    = $relative ? substr( $folder, strlen( $base ) ) : $folder;
			$value[]  = esc_html( $names[ $name ] ) . ': <code>' . esc_html( $shown ) . '</code>';
			$export[] = $name . ': ' . $shown;
		}

		return $this->get_configuration_item(
			esc_html__( 'Folders', 'gravity-pdf' ),
			'Folders',
			implode( '<br>', $value ),
			implode( ', ', $export ),
			/* None of the folder filters moved them */
			$folders === [
				'Fonts'     => $root . 'fonts/',
				'Temporary' => $root . 'tmp/',
				'mPDF'      => $root . 'tmp/mpdf/',
			]
		);
	}

	/**
	 * Whether the temporary folder can't be reached from the web
	 *
	 * @since 6.0
	 * @since 7.0 Returns the Temporary Folder Protected check
	 */
	protected function check_temp_folder_permission(): array {
		$view      = $this->getController()->view;
		$protected = $this->test_public_tmp_directory_access();

		return $view->get_check(
			[
				'label'        => esc_html__( 'Temporary Folder Protected', 'gravity-pdf' ),
				'label_export' => 'Temporary Folder Protected',
				'value'        => $protected ? esc_html__( 'Yes', 'gravity-pdf' ) : esc_html__( 'No', 'gravity-pdf' ),
				'value_export' => $protected ? 'Yes' : 'No',
			],
			__( 'temporary folder protected', 'gravity-pdf' ),
			$protected ? '' : $view->get_fix( __( "The temporary folder can be reached from the web. Move it outside the site's public folder.", 'gravity-pdf' ), 'https://docs.gravitypdf.com/developers/filters/gfpdf_tmp_location/', __( 'How to move it', 'gravity-pdf' ) )
		);
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
	 * @since 7.0 Returns the Folder Permissions check, which covers the temporary folder too
	 */
	protected function get_folder_permissions( array $folders ): array {
		$view     = $this->getController()->view;
		$writable = $folders === [];

		return $view->get_check(
			[
				'label'        => esc_html__( 'Folder Permissions', 'gravity-pdf' ),
				'label_export' => 'Folder Permissions',
				'value'        => $writable ? esc_html__( 'Writable', 'gravity-pdf' ) : esc_html__( 'Not writable:', 'gravity-pdf' ) . '<br>' . $view->get_code_lines( $folders ),
				'value_export' => $writable ? 'Writable' : 'Not writable: ' . implode( ', ', $folders ),
			],
			__( 'folders writable', 'gravity-pdf' ),
			$writable ? '' : $view->get_fix( __( 'Ask your web host to make these folders writable.', 'gravity-pdf' ) )
		);
	}

	/**
	 * The core template overrides older than the core template, by file name
	 *
	 * @return array<string, string[]> The override's version, then the core template's
	 *
	 * @since 6.0
	 * @since 7.0 Returns the outdated overrides rather than the row
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
	 * The Outdated Templates check. A failing value ends in a line break, so the templates an add-on appends start on
	 * a line of their own.
	 *
	 * @param array<string, string[]> $outdated See self::check_core_template_override_versions()
	 *
	 * @since 7.0
	 */
	protected function get_outdated_templates( array $outdated ): array {
		$view   = $this->getController()->view;
		$value  = [];
		$export = [];

		foreach ( $outdated as $file => $versions ) {
			/* translators: 1: The override's version, 2: The core template's version */
			$value[]  = '<code>' . esc_html( $file ) . '</code> ' . esc_html( sprintf( __( '%1$s, current %2$s', 'gravity-pdf' ), $versions[0], $versions[1] ) );
			$export[] = sprintf( '%1$s %2$s (current %3$s)', $file, $versions[0], $versions[1] );
		}

		return $view->get_check(
			[
				'label'        => esc_html__( 'Outdated Templates', 'gravity-pdf' ),
				'label_export' => 'Outdated Templates',
				'value'        => $value !== [] ? implode( '<br>', $value ) . '<br>' : esc_html__( 'None', 'gravity-pdf' ),
				'value_export' => $export !== [] ? implode( ', ', $export ) . "\n" : 'None',
			],
			__( 'templates up to date', 'gravity-pdf' ),
			$value !== [] ? $view->get_fix( __( 'Update these template overrides.', 'gravity-pdf' ), 'https://docs.gravitypdf.com/developers/template-hierarchy', __( 'How to update', 'gravity-pdf' ) ) : ''
		);
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
	 * A Configuration row, marked "(default)" in the copied report while it's unchanged
	 *
	 * @param string $label        Escaped, as the settings page names it
	 * @param string $label_export The label in the copied report
	 * @param string $value        Escaped, shown on screen
	 * @param string $value_export The value in the copied report
	 * @param bool   $is_default   Whether it's unchanged
	 *
	 * @since 7.0
	 */
	protected function get_configuration_item( string $label, string $label_export, string $value, string $value_export, bool $is_default ): array {
		return [
			'label'        => $label,
			'label_export' => $label_export,
			'value'        => $value,
			'value_export' => $value_export . ( $is_default ? ' (default)' : '' ),
		];
	}

	/**
	 * Each setting's default, from the settings registry
	 *
	 * @return array<string, mixed> By setting ID
	 *
	 * @since 7.0
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
	 * @since 7.0
	 */
	protected function is_on( $value ): bool {
		return in_array( $value, [ 'Yes', '1', 1, true ], true );
	}

	/**
	 * Whether two lists hold the same values, in any order
	 *
	 * @since 7.0
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
	 * @since 7.0
	 */
	protected function get_count( $count, string $unit ): string {
		return $count . ' ' . $unit . ( (float) $count === 1.0 ? '' : 's' );
	}

	/**
	 * A size in MB, to two decimal places
	 *
	 * @since 7.0
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
