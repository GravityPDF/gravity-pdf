<?php

declare( strict_types=1 );

namespace GFPDF\Statics;

use GFPDF\Helper\Helper_Interface_Deprecated_Features;

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
 * The v3 backwards compatibility layer, which Gravity PDF 7.0 removes
 *
 * @since 6.17.0
 */
class Deprecation_V3 implements Helper_Interface_Deprecated_Features {

	/**
	 * The release the v3 backwards compatibility layer is removed in
	 *
	 * @var string
	 * @since 6.17.0
	 */
	const REMOVED_IN = '7.0';

	/**
	 * The release the v3 backwards compatibility layer was deprecated in
	 *
	 * @var string
	 * @since 6.17.0
	 */
	const DEPRECATED_IN = '4.0';

	/**
	 * The feature ID legacy download URLs are detected and reported under
	 *
	 * @var string
	 * @since 6.17.0
	 */
	const FEATURE_LEGACY_ENDPOINT = 'legacy_endpoint';

	/**
	 * The query argument every legacy `?gf_pdf=1` download URL carries
	 *
	 * @var string
	 * @since 6.17.0
	 */
	const LEGACY_URL_MARKER = 'gf_pdf=1';

	/**
	 * The option the forms that have served a legacy download URL are recorded in
	 *
	 * Kept out of `gfpdf_settings` and out of the autoloaded set: it is written from the front end, and only the
	 * report, Site Health and uninstall paths ever read it.
	 *
	 * @var string
	 * @since 6.17.0
	 */
	const LEGACY_ENDPOINT_OPTION = 'gfpdf_legacy_endpoint_usage';

	/**
	 * How long a form stays recorded after the last legacy download URL was served for it
	 *
	 * @var int
	 * @since 6.17.0
	 */
	const LEGACY_ENDPOINT_TTL = 30 * DAY_IN_SECONDS;

	/**
	 * How often a form already on the record is written again
	 *
	 * @var int
	 * @since 6.17.0
	 */
	const LEGACY_ENDPOINT_REFRESH = DAY_IN_SECONDS;

	/**
	 * The prefix every v3 hook carries, including the dynamic ones the map below can't name
	 *
	 * @var string
	 * @since 6.17.0
	 */
	const HOOK_PREFIX = 'gfpdfe_';

	/**
	 * The call every Business Plus (Tier 2) template opens with, and no plain v3 template makes
	 *
	 * This is the first line of the add-on's own template boilerplate, so every template built from it carries the
	 * call verbatim. `initilise` is misspelt in the add-on itself — matching anything else finds nothing.
	 *
	 * @var string
	 * @since 6.17.0
	 */
	const BUSINESS_PLUS_MARKER = 'gfpdfe_business_plus::initilise';

	/**
	 * The class the v3 "Advanced Templating" (Tier 2) add-on declares
	 *
	 * @var string
	 * @since 6.17.0
	 */
	const TIER_2_ADDON_CLASS = 'gfpdfe_business_plus';

	/**
	 * The `gfpdfe_pre_load_template` callback core registers itself, which the listener scan must ignore
	 *
	 * @var array
	 * @since 6.17.0
	 */
	const INTERNAL_FILTER_CALLBACK = [ 'PDFRender', 'prepare_ids' ];

	/**
	 * What the legacy template scan found this request, keyed on the installed template list it read
	 *
	 * @var array<string, array<string, array<string, int[]>>>
	 * @since 6.17.0
	 */
	protected static $legacy_templates = [];

	/**
	 * @inheritDoc
	 * @since 6.17.0
	 */
	public static function get_features(): array {
		return [
			'legacy_templates'              => [
				'label'      => __( 'Legacy Templates', 'gravity-pdf' ),
				'group'      => Deprecation::GROUP_DEPRECATED,
				'removed_in' => static::REMOVED_IN,
				'url'        => 'https://docs.gravitypdf.com/upgrade/legacy-templates/',
				'detect'     => [ static::class, 'get_legacy_templates' ],
			],

			'business_plus_templates'       => [
				'label'      => __( 'Business Plus Templates', 'gravity-pdf' ),
				'group'      => Deprecation::GROUP_DEPRECATED,
				'removed_in' => static::REMOVED_IN,
				'url'        => 'https://docs.gravitypdf.com/upgrade/legacy-templates/#business-plus--tier-2-template-upgrade-guide',
				'detect'     => [ static::class, 'get_business_plus_templates' ],
			],

			static::FEATURE_LEGACY_ENDPOINT => [
				'label'      => __( 'Legacy Download URLs', 'gravity-pdf' ),
				'group'      => Deprecation::GROUP_DEPRECATED,
				'removed_in' => static::REMOVED_IN,
				'url'        => 'https://docs.gravitypdf.com/upgrade/legacy-download-urls/',
				'detect'     => [ static::class, 'get_legacy_download_urls' ],
				/* translators: %s: The Gravity PDF version the feature is removed in */
				'notice'     => __( 'Support for legacy download URLs will be removed in Gravity PDF %s.', 'gravity-pdf' ),
			],

			'deprecated_filters'            => [
				'label'              => __( 'Actions and Filters', 'gravity-pdf' ),
				'group'              => Deprecation::GROUP_DEPRECATED,
				'removed_in'         => static::REMOVED_IN,
				'url'                => 'https://docs.gravitypdf.com/upgrade/deprecated-filters/',
				'detect'             => [ static::class, 'get_active_deprecated_filters' ],
				/* The label is a category, so out of its report row the fallback would read as the whole hook API */
				/* translators: %s: The Gravity PDF version the hooks are removed in */
				'notice'             => __( 'Code on this site uses Gravity PDF hooks that are removed in version %s.', 'gravity-pdf' ),
				'hooks'              => static::get_deprecated_filters(),
				'deprecated_in'      => static::DEPRECATED_IN,
				'hook_prefix'        => static::HOOK_PREFIX,
				/* Declared so the notice discounts them the same way the detector already does */
				'internal_callbacks' => [ static::INTERNAL_FILTER_CALLBACK ],
			],
		];
	}

	/**
	 * The deprecated v3 filters still fired by core, mapped to their v4+ replacement
	 *
	 * An empty replacement means no equivalent exists.
	 *
	 * @return array<string, string>
	 * @since 6.17.0
	 */
	protected static function get_deprecated_filters(): array {
		return [
			'gfpdfe_template_location'              => 'gfpdf_template_location',
			'gfpdfe_template_location_uri'          => 'gfpdf_template_location_uri',
			'gfpdfe_pdf_output_type'                => '',
			'gfpdfe_pdf_name'                       => 'gfpdf_pdf_config',
			'gfpdfe_template'                       => 'gfpdf_pdf_config',
			'gfpdfe_pdf_filename'                   => 'gfpdf_pdf_filename',
			'gfpdfe_pdf_template'                   => 'gfpdf_pdf_html_output',
			'gfpdfe_mpdf_class_pre_render'          => 'gfpdf_mpdf_class',
			'gfpdfe_pre_render_pdf'                 => 'gfpdf_mpdf_class',
			'gfpdfe_mpdf_class'                     => 'gfpdf_mpdf_class',
			'gfpdfe_lead_id'                        => 'gfpdf_template_args',
			'gfpdfe_signature_width'                => 'gfpdf_signature_width',
			'gfpdfe_pre_load_template'              => '',
			'gfpdf_orientation'                     => 'gfpdf_pdf_config',
			'gfpdf_security'                        => 'gfpdf_pdf_config',
			'gfpdf_privilages'                      => 'gfpdf_pdf_config',
			'gfpdf_password'                        => 'gfpdf_pdf_config',
			'gfpdf_master_password'                 => 'gfpdf_pdf_config',
			'gfpdf_rtl'                             => 'gfpdf_pdf_config',

			/* Fired by the v3 endpoint and template layers, and removed along with them */
			'gfpdf_legacy_save_path'                => '',
			'gfpdf_legacy_templates'                => '',
			'gfpdf_legacy_pre_view_or_download_pdf' => '',
		];
	}

	/**
	 * Get the deprecated v3 filters that currently have a third-party listener attached
	 *
	 * @return array<string, int> Hook name mapped to the number of listeners
	 * @since 6.17.0
	 */
	public static function get_active_deprecated_filters(): array {
		return Deprecation::get_hooks_with_listeners(
			static::get_deprecated_filters(),
			static::HOOK_PREFIX,
			[ static::INTERNAL_FILTER_CALLBACK ]
		);
	}

	/**
	 * Restore the `RGForms` class the v3 template boilerplate guards on
	 *
	 * Gravity Forms declared `RGForms` as an empty subclass of `GFForms`, deprecated it in 2.10.5 and removed it in
	 * 3.0. Every v3 template opens by checking for it and returning when it's missing, so the PDF renders with no
	 * content at all — no fatal, and nothing in the log to explain the blank page. Aliasing the class it extended
	 * puts the check back in the only place it still matters, until 7.0 removes these templates along with it.
	 *
	 * @since 6.17.0
	 */
	public static function restore_v3_form_class(): void {
		if ( class_exists( 'RGForms' ) || ! class_exists( 'GFForms' ) ) {
			return;
		}

		class_alias( 'GFForms', 'RGForms' );
	}

	/**
	 * @inheritDoc
	 * @since 6.17.0
	 */
	public static function get_stored_options(): array {
		return [ static::LEGACY_ENDPOINT_OPTION ];
	}

	/**
	 * Get the forms still tied to a legacy `?gf_pdf=1` download URL
	 *
	 * Two sources are combined: the forms whose stored settings, confirmations or notifications hand one of these
	 * URLs out, and the forms one has actually been served for — see self::record_legacy_endpoint_usage(). The scan
	 * finds a URL nobody has followed yet; the record finds one living somewhere the scan can't see, like a page or
	 * an email that has already gone out.
	 *
	 * @return array List of form IDs, ascending
	 * @since 6.17.0
	 */
	public static function get_legacy_download_urls(): array {
		global $wpdb;

		$marker = '%' . $wpdb->esc_like( static::LEGACY_URL_MARKER ) . '%';

		/* Recorded IDs are cast on the way in and out, so they're safe to interpolate */
		$recorded        = static::get_recorded_legacy_endpoint_usage();
		$recorded_clause = $recorded === [] ? '' : sprintf( 'meta.form_id IN ( %s ) OR', implode( ',', $recorded ) );

		$forms = static::scan_forms(
			'meta.form_id',
			"{$recorded_clause} meta.display_meta LIKE %s OR meta.confirmations LIKE %s OR meta.notifications LIKE %s",
			[ $marker, $marker, $marker ]
		);

		return array_map( 'intval', array_column( $forms, 'form_id' ) );
	}

	/**
	 * Scan the stored forms for one of the signals this class detects
	 *
	 * Trashed forms are left out of every scan: the user can't see them in their form list, so there's nothing for
	 * them to act on.
	 *
	 * @param string $columns  The columns to select, from the `meta` and `form` tables the query names below
	 * @param string $criteria What a form has to match, carrying one `%s` placeholder per entry in $values
	 * @param array  $values   One value per placeholder in $criteria
	 *
	 * @return array<int, array<string, string>> One row per matching form, by form ID ascending
	 * @since 6.17.0
	 */
	protected static function scan_forms( string $columns, string $criteria, array $values ): array {
		global $wpdb;

		$meta_table = \GFFormsModel::get_meta_table_name();
		$form_table = \GFFormsModel::get_form_table_name();

		/* The table names come from Gravity Forms, and every caller value travels as a placeholder in $values */
		//phpcs:disable WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders
		return (array) $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT {$columns} FROM {$meta_table} meta
				INNER JOIN {$form_table} form ON form.id = meta.form_id
				WHERE form.is_trash = 0 AND ( {$criteria} )
				ORDER BY meta.form_id",
				$values
			),
			ARRAY_A
		);
		//phpcs:enable WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders
	}

	/**
	 * The forms a legacy `?gf_pdf=1` download URL has actually been served for
	 *
	 * A record older than self::LEGACY_ENDPOINT_TTL is dropped as it's read, so a form stops being reported once
	 * the links pointing at it have gone quiet. Gravity PDF can't tell whether a URL it served last year still
	 * exists, and without an expiry the form would be reported forever.
	 *
	 * @return array List of form IDs, ascending
	 * @since 6.17.0
	 */
	public static function get_recorded_legacy_endpoint_usage(): array {
		$recorded = array_map( 'intval', static::get_legacy_endpoint_records() );
		$cutoff   = time() - static::LEGACY_ENDPOINT_TTL;

		ksort( $recorded );

		$current = array_filter(
			$recorded,
			function ( $last_served ) use ( $cutoff ) {
				return $last_served >= $cutoff;
			}
		);

		if ( $current !== $recorded ) {
			update_option( static::LEGACY_ENDPOINT_OPTION, $current, false );
		}

		return array_map( 'intval', array_keys( $current ) );
	}

	/**
	 * The raw record, mapping each form to when a legacy download URL was last served for it
	 *
	 * Left in the order it was stored: the front end reads this on every legacy download to check one timestamp,
	 * and only self::get_recorded_legacy_endpoint_usage() promises an order.
	 *
	 * @return array<int, int> Form ID mapped to a Unix timestamp
	 * @since 6.17.0
	 */
	protected static function get_legacy_endpoint_records(): array {
		return (array) get_option( static::LEGACY_ENDPOINT_OPTION, [] );
	}

	/**
	 * Record that a legacy `?gf_pdf=1` download URL has been served for a form
	 *
	 * Called by the endpoint itself, once it has resolved the request to a real PDF, so a URL that lives outside the
	 * form's own settings is still reported. The timestamp is what self::get_recorded_legacy_endpoint_usage() expires
	 * the form on, so it's refreshed while the links are still being followed — at most once a day, since this runs
	 * on the front end.
	 *
	 * @param int $form_id The form the PDF was served from
	 *
	 * @since 6.17.0
	 */
	public static function record_legacy_endpoint_usage( int $form_id ): void {
		$recorded = static::get_legacy_endpoint_records();
		$now      = time();

		if ( ( $recorded[ $form_id ] ?? 0 ) > $now - static::LEGACY_ENDPOINT_REFRESH ) {
			return;
		}

		$recorded[ $form_id ] = $now;

		update_option( static::LEGACY_ENDPOINT_OPTION, $recorded, false );

		/* The notices read a record taken at install and on each version change, which a URL followed since then
		   won't be in yet */
		Deprecation::mark_feature_detected( static::FEATURE_LEGACY_ENDPOINT );

		/* This is what the endpoint detector reads, so anything detecting later in the request reads it fresh */
		Deprecation::flush_cache();
	}

	/**
	 * Check if the v3 "Advanced Templating" (Tier 2) add-on is installed
	 *
	 * @since 6.17.0
	 */
	public static function has_tier_2_addon(): bool {
		return class_exists( static::TIER_2_ADDON_CLASS );
	}

	/**
	 * Check if a PDF is configured to use the v3 Advanced Templating mode
	 *
	 * Compared case-insensitively, which is how the render path has always read the setting.
	 *
	 * @param array $settings The PDF settings
	 *
	 * @since 6.17.0
	 */
	public static function is_advanced_template_pdf( array $settings ): bool {
		return strtolower( (string) ( $settings['advanced_template'] ?? '' ) ) === 'yes';
	}

	/**
	 * Get the installed legacy (v3) PDF templates written as plain HTML and CSS
	 *
	 * @return array<string, int[]> Absolute template path mapped to the forms it is configured on
	 * @since 6.17.0
	 */
	public static function get_legacy_templates(): array {
		return static::get_legacy_templates_by_kind()['standard'];
	}

	/**
	 * Get the installed legacy (v3) PDF templates that drive the PDF engine themselves
	 *
	 * These were built by our own team and sold as Business Plus, or Tier 2, alongside the plugin now reported as
	 * the Advanced Templating add-on. They upgrade differently to a plain v3 template, so they're reported apart.
	 *
	 * @return array<string, int[]> Absolute template path mapped to the forms it is configured on
	 * @since 6.17.0
	 */
	public static function get_business_plus_templates(): array {
		return static::get_legacy_templates_by_kind()['business_plus'];
	}

	/**
	 * Get every installed legacy (v3) PDF template, split by the kind of template it is
	 *
	 * A template is classified as legacy when it has no `Group` header, which is how Helper_Templates groups
	 * v3-era templates that predate the v4 header format. What the file does with the PDF engine then says which
	 * of the two kinds it is — the PDF's own settings aren't consulted, so a template is reported for what it is
	 * rather than for how one PDF happens to be configured. Which forms select it is then carried alongside, so a
	 * report says what actually breaks when the file stops rendering.
	 *
	 * @return array<string, array<string, int[]>> The `standard` and `business_plus` templates, each mapped to the
	 *                                            forms it is configured on
	 * @since 6.17.0
	 */
	protected static function get_legacy_templates_by_kind(): array {
		$templates = \GPDFAPI::get_templates_class();

		/* Both detectors ask, so the walk, the file reads and the form scan happen once rather than once per
		   detector. Keyed on the installed template list, which a template being added or removed changes; the
		   form usage carried alongside it is held for the request, and dropped by self::flush_cache() */
		$key = implode( '|', $templates->get_all_templates() );

		if ( ! isset( static::$legacy_templates[ $key ] ) ) {
			$legacy = array_filter( $templates->get_all_template_info(), [ $templates, 'is_legacy_template' ] );
			$usage  = static::get_forms_using_templates( array_column( $legacy, 'id' ) );

			$kinds = [
				'standard'      => [],
				'business_plus' => [],
			];

			foreach ( $legacy as $template ) {
				$kind = static::is_business_plus_template( $template['path'] ) ? 'business_plus' : 'standard';

				$kinds[ $kind ][ $template['path'] ] = $usage[ $template['id'] ];
			}

			static::$legacy_templates[ $key ] = $kinds;
		}

		return static::$legacy_templates[ $key ];
	}

	/**
	 * Forget what the legacy template scan found this request
	 *
	 * The scan reads the template directory and the stored forms, and nothing changes either between the two
	 * detectors asking, so what it finds is held for the request. Anything that does change them while a request
	 * is still running calls Deprecation::flush_cache(), which is what reaches this.
	 *
	 * @since 6.17.0
	 */
	public static function flush_cache(): void {
		static::$legacy_templates = [];
	}

	/**
	 * Get the forms each of the given templates is selected on
	 *
	 * Trashed forms are skipped, as they are everywhere else here: the user can't see them in their form list, so
	 * there's nothing to act on. A PDF is counted whether or not it's active, since a disabled one still has to be
	 * moved off the template before it can be turned back on.
	 *
	 * @param string[] $template_ids Template IDs, as Helper_Templates writes them into a PDF's settings
	 *
	 * @return array<string, int[]> Template ID mapped to the forms configured with it, ascending
	 * @since 6.17.0
	 */
	protected static function get_forms_using_templates( array $template_ids ): array {
		global $wpdb;

		if ( $template_ids === [] ) {
			return [];
		}

		$usage = array_fill_keys( $template_ids, [] );

		/* A form configured with a template carries its ID somewhere in the stored form, so a LIKE per template
		   narrows the scan to the rows worth reading — the whole form travels back, and on a site of any size
		   that is most of what the query costs. Which template each PDF actually selects is a JSON value, so the
		   decode below is what settles it */
		$criteria = implode( ' OR ', array_fill( 0, count( $usage ), 'meta.display_meta LIKE %s' ) );
		$values   = array_map(
			static function ( $id ) use ( $wpdb ) {
				return '%' . $wpdb->esc_like( $id ) . '%';
			},
			array_keys( $usage )
		);

		foreach ( static::scan_forms( 'meta.form_id, meta.display_meta', $criteria, $values ) as $form ) {
			$meta    = json_decode( (string) $form['display_meta'], true );
			$form_id = (int) $form['form_id'];

			foreach ( (array) ( $meta['gfpdf_form_settings'] ?? [] ) as $pdf ) {
				$template = (string) ( $pdf['template'] ?? '' );

				/* A form with several PDFs on the one template is still the one form to fix */
				if ( isset( $usage[ $template ] ) && ! in_array( $form_id, $usage[ $template ], true ) ) {
					$usage[ $template ][] = $form_id;
				}
			}
		}

		return $usage;
	}

	/**
	 * Check whether a legacy template hands itself to the Advanced Templating add-on
	 *
	 * Calling into the add-on is the one thing a Business Plus template does and a plain v3 template never did, so
	 * the call is what tells the two apart. Matched case-insensitively, which is how PHP resolves the identifiers.
	 *
	 * @param string $path The absolute path to the template file
	 *
	 * @since 6.17.0
	 */
	protected static function is_business_plus_template( string $path ): bool {
		//phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$contents = is_readable( $path ) ? (string) file_get_contents( $path ) : '';

		return stripos( $contents, static::BUSINESS_PLUS_MARKER ) !== false;
	}
}
