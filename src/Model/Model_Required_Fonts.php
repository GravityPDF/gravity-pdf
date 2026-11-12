<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFFormsModel;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use WP_Site;

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
 * Which downloadable font files a site needs: its default font's, its languages', every required source's and those
 * of every font its PDFs name
 *
 * @since 6.19.0
 */
class Model_Required_Fonts extends Helper_Abstract_Model {

	/**
	 * The option holding which of a site's PDFs use each font
	 *
	 * @since 6.19.0
	 */
	public const FONT_PDFS = 'gfpdf_font_pdfs';

	/**
	 * The start of the sitemeta row holding the font keys a site uses, followed by its ID
	 *
	 * @since 6.19.0
	 */
	public const SITE_FONTS_PREFIX = 'gfpdf_site_fonts_';

	/**
	 * The options a site's fonts are read from. `gfpdf_settings` also holds its custom fonts.
	 *
	 * @since 6.19.0
	 */
	protected const SITE_OPTIONS = [ 'gfpdf_settings', 'gf_db_version', 'WPLANG' ];

	/**
	 * How many forms' meta the PDF font scan reads at a time
	 *
	 * @var int
	 * @since 6.19.0
	 */
	protected $form_page = 100;

	/**
	 * @var Model_Font_Sources
	 * @since 6.19.0
	 */
	protected $font_sources;

	/**
	 * @var Helper_Abstract_Options
	 * @since 6.19.0
	 */
	protected $options;

	/**
	 * @var LoggerInterface
	 * @since 6.19.0
	 */
	protected $log;

	/**
	 * @var array<string, array<int, array{form: string, name: string, form_id: int, pdf_id: string}>>|null
	 * @since 6.19.0
	 */
	protected $font_pdfs;

	/**
	 * @var bool Whether reading the forms failed, so what was read shouldn't be stored
	 * @since 6.19.0
	 */
	protected $form_read_failed = false;

	/**
	 * @var array<int, bool> The sites to record the fonts of when the request ends, keyed by ID
	 * @since 6.19.0
	 */
	protected $queued_sites = [];

	/**
	 * @var Model_Site_Options
	 * @since 6.19.0
	 */
	protected $site_options;

	/**
	 * Work out the required fonts from the font sources and the saved settings, logging forms it can't read
	 *
	 * @since 6.19.0
	 */
	public function __construct( Model_Font_Sources $font_sources, Helper_Abstract_Options $options, LoggerInterface $log, ?Model_Site_Options $site_options = null ) {
		$this->font_sources = $font_sources;
		$this->options      = $options;
		$this->log          = $log;
		$this->site_options = $site_options ?? new Model_Site_Options( $log );
	}

	/**
	 * Forget the font selections read this request
	 *
	 * @since 6.19.0
	 */
	public function flush(): void {
		$this->font_pdfs = null;
	}

	/**
	 * A font key's files on the core manifest (none for bundled and custom fonts)
	 *
	 * @param mixed $key A font key
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_font_files( $key ): array {
		$family = $this->font_sources->get_font_data()[ $this->normalise_font( $key ) ] ?? null;
		if ( ! is_array( $family ) ) {
			return [];
		}

		$files = [];
		foreach ( [ 'R', 'B', 'I', 'BI' ] as $style ) {
			$file = is_string( $family[ $style ] ?? null ) ? $this->font_sources->get_manifest_file( $family[ $style ] ) : '';
			if ( $this->font_sources->is_core_font_file( $file ) ) {
				$files[] = $file;
			}
		}

		return array_values( array_unique( $files ) );
	}

	/**
	 * Files that should be on disk: default, language, required-source and PDF-selected fonts
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_required_font_files(): array {
		return $this->get_key_files( array_keys( $this->get_font_reasons() ) );
	}

	/**
	 * The files of the fonts sites need, plus the installed languages', as every site shares the fonts folder
	 *
	 * @param string[] $keys Font keys from get_site_font_keys()
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_install_font_files( array $keys ): array {
		return $this->get_key_files( array_merge( $this->font_sources->get_language_font_keys(), $keys ) );
	}

	/**
	 * The files of the font keys, plus every required source's
	 *
	 * @param array<int, string|int> $keys
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_key_files( array $keys ): array {
		$files = [];
		foreach ( $keys as $key ) {
			$files = array_merge( $files, $this->get_font_files( (string) $key ) );
		}

		foreach ( $this->font_sources->get_sources() as $id => $source ) {
			if ( ! empty( $source['required'] ) ) {
				$files = array_merge( $files, array_keys( $this->font_sources->get_source_manifest( (string) $id ) ) );
			}
		}

		return array_values( array_unique( $files ) );
	}

	/**
	 * Why each required font key is required: the default font, a site language's, selected by that many PDFs, in a
	 * required source and, with $network, used by that many other sites or a core font while any site is uncounted
	 *
	 * @return array<string, array{default: bool, language: bool, pdfs: int, source: bool, sites: int, pending: bool}>
	 *
	 * @since 6.19.0
	 */
	public function get_font_reasons( bool $network = false ): array {
		$none    = [
			'default'  => false,
			'language' => false,
			'pdfs'     => 0,
			'source'   => false,
			'sites'    => 0,
			'pending'  => false,
		];
		$reasons = $this->get_settings_font_reasons();

		foreach ( array_map( 'count', $this->get_font_pdfs() ) as $key => $count ) {
			$reasons[ $key ]['pdfs'] = $count;
		}

		if ( $network ) {
			$reasons = $this->add_other_site_reasons( $reasons );
		}

		return array_map(
			static function ( array $reason ) use ( $none ): array {
				return array_merge( $none, $reason );
			},
			$reasons
		);
	}

	/**
	 * Whether anything requires the font key, so deleting it would break or re-download it. Reads this site's forms
	 * rather than the stored usage, and treats forms it can't read, or a site with no record, as using the font.
	 *
	 * @since 6.19.0
	 */
	public function is_font_in_use( string $key, bool $network = false ): bool {
		if ( isset( $this->get_settings_font_reasons()[ $key ] ) ) {
			return true;
		}

		$this->form_read_failed = false;
		foreach ( $this->each_pdf_font() as $font => $unused ) {
			if ( $font === $key ) {
				return true;
			}
		}

		/* Forms that couldn't be read could be using it */
		if ( $this->form_read_failed ) {
			return true;
		}

		if ( ! $network ) {
			return false;
		}

		$sites = $this->get_site_font_records();
		foreach ( $this->get_other_site_ids() as $site_id ) {
			if ( ! isset( $sites[ $site_id ] ) || in_array( $key, $sites[ $site_id ], true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The reasons that need no form read: the default font, a site language's and a required source's
	 *
	 * @return array<string, array<string, bool>>
	 *
	 * @since 6.19.0
	 */
	protected function get_settings_font_reasons(): array {
		$reasons = [];

		$default = $this->normalise_font( $this->options->get_option( 'default_font', '' ) );
		if ( $default !== '' ) {
			$reasons[ $default ]['default'] = true;
		}

		foreach ( $this->font_sources->get_language_font_keys() as $key ) {
			$reasons[ $key ]['language'] = true;
		}

		foreach ( $this->font_sources->get_sources() as $source ) {
			foreach ( empty( $source['required'] ) ? [] : array_keys( $source['families'] ?? [] ) as $key ) {
				$reasons[ $key ]['source'] = true;
			}
		}

		return $reasons;
	}

	/**
	 * The registered fonts among a PDF's settings, so template font pickers count too
	 *
	 * @param array $pdf PDF settings
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_pdf_fonts( array $pdf ): array {
		$fontdata = $this->font_sources->get_font_data();
		$fonts    = [];

		foreach ( $pdf as $value ) {
			$font = $this->normalise_font( $value );
			if ( $font !== '' && isset( $fontdata[ $font ] ) ) {
				$fonts[] = $font;
			}
		}

		return array_values( array_unique( $fonts ) );
	}

	/**
	 * Whether any font a PDF's settings name has downloadable files missing
	 *
	 * @param array $pdf PDF settings
	 *
	 * @since 6.19.0
	 */
	public function pdf_needs_download( array $pdf ): bool {
		foreach ( $this->get_pdf_fonts( $pdf ) as $font ) {
			if ( $this->needs_download( $font ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether any of a font's downloadable files is missing from the fonts folder
	 *
	 * @param mixed $font A font key
	 *
	 * @since 6.19.0
	 */
	public function needs_download( $font ): bool {
		foreach ( $this->get_font_files( $font ) as $file ) {
			if ( ! $this->is_installed( $file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Installed required font files per downloadable source, and whether its manifest could be read
	 *
	 * @return array<string, array{installed: int, total: int, manifest: bool}>
	 *
	 * @since 6.19.0
	 */
	public function get_installed_counts(): array {
		$required = $this->get_required_font_files();
		$counts   = [];

		foreach ( $this->font_sources->get_sources() as $id => $source ) {
			if ( empty( $source['manifest'] ) ) {
				continue;
			}

			$manifest = $this->font_sources->get_source_manifest( (string) $id );
			$files    = array_intersect( $required, array_keys( $manifest ) );

			$counts[ (string) $id ] = [
				'installed' => count( array_filter( $files, [ $this, 'is_installed' ] ) ),
				'total'     => count( $files ),
				'manifest'  => count( $manifest ) > 0,
			];
		}

		return $counts;
	}

	/**
	 * The required files whose own copy isn't in the fonts folder, so one a different font holds the name of counts
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_missing_files(): array {
		return array_values(
			array_filter(
				$this->get_required_font_files(),
				function ( $file ) {
					return ! $this->is_installed( $file );
				}
			)
		);
	}

	/**
	 * Whether the file's own copy is in the fonts folder: Model_Font_Sources::get_own_copy(), less the other font's file a
	 * failed download found at its name
	 *
	 * @since 6.19.0
	 */
	protected function is_installed( string $file ): bool {
		$own_path = $this->font_sources->get_own_copy( $file );

		/* The registered downloader, as it's built after this model */
		$downloader = \GPDFAPI::get_mvc_class( 'Model_Font_Downloader' );

		return $own_path !== '' && ! ( $downloader instanceof Model_Font_Downloader && $downloader->is_failed_conflict( $file, $own_path ) );
	}

	/**
	 * A font key as mPDF stores it: trimmed and lower case, or empty for a non-string
	 *
	 * @param mixed $font
	 *
	 * @since 6.19.0
	 */
	protected function normalise_font( $font ): string {
		return is_string( $font ) ? strtolower( trim( $font ) ) : '';
	}

	/**
	 * The PDF settings in a form's stored display meta, logging meta that isn't a JSON form
	 *
	 * @param mixed $display_meta The form's meta as JSON
	 *
	 * @return array[] Keyed by PDF ID
	 *
	 * @since 6.19.0
	 */
	public function get_form_pdfs( $display_meta, int $form_id ): array {
		if ( is_string( $display_meta ) ) {
			$form  = json_decode( $display_meta, true );
			$error = json_last_error_msg();
		} else {
			$form  = null;
			$error = 'Not a string: ' . gettype( $display_meta );
		}

		if ( ! is_array( $form ) ) {
			$this->log->warning(
				'Form meta could not be read for its PDF fonts',
				[
					'form_id' => $form_id,
					'error'   => $error,
				]
			);

			return [];
		}

		$pdfs = $form['gfpdf_form_settings'] ?? [];

		return is_array( $pdfs ) ? array_filter( $pdfs, 'is_array' ) : [];
	}

	/**
	 * The active forms' PDFs that select each font, from the stored map, which is rebuilt once cleared or a day old
	 *
	 * @return array<string, array<int, array{form: string, name: string, form_id: int, pdf_id: string}>> PDFs by font key
	 *
	 * @since 6.19.0
	 */
	public function get_font_pdfs(): array {
		if ( $this->font_pdfs !== null ) {
			return $this->font_pdfs;
		}

		$stored = get_option( self::FONT_PDFS );
		if ( is_array( $stored['pdfs'] ?? null ) && (int) ( $stored['built'] ?? 0 ) > time() - DAY_IN_SECONDS ) {
			$this->font_pdfs = $stored['pdfs'];

			return $this->font_pdfs;
		}

		$pdfs = [];

		$this->form_read_failed = false;
		foreach ( $this->each_pdf_font() as $font => list( $row, $pdf_id, $pdf ) ) {
			$pdfs[ $font ][] = [
				'form'    => (string) ( $row['title'] ?? '' ),
				'name'    => is_string( $pdf['name'] ?? null ) ? $pdf['name'] : '',
				'form_id' => (int) $row['form_id'],
				'pdf_id'  => $pdf_id,
			];
		}

		$this->font_pdfs = $pdfs;
		$this->store_font_pdfs( $pdfs, $stored['cleared'] ?? null );

		return $pdfs;
	}

	/**
	 * Keep the map, unless the forms couldn't be read or the usage was cleared while they were
	 *
	 * @param array $pdfs
	 * @param mixed $cleared The marker of the clear the map was built after
	 *
	 * @since 6.19.0
	 */
	protected function store_font_pdfs( array $pdfs, $cleared ): void {
		if ( $this->form_read_failed ) {
			return;
		}

		/* A persistent object cache would otherwise hide a clear from another request */
		wp_cache_delete( self::FONT_PDFS, 'options' );
		$current = get_option( self::FONT_PDFS );

		if ( ( $current['cleared'] ?? null ) === $cleared ) {
			update_option(
				self::FONT_PDFS,
				[
					'built' => time(),
					'pdfs'  => $pdfs,
				],
				false
			);
		}
	}

	/**
	 * The PDFs of this site using a font, with links to their settings
	 *
	 * @return array<int, array{form: string, name: string, url: string}>
	 *
	 * @since 6.19.0
	 */
	public function get_pdfs_using_font( string $key ): array {
		return array_map(
			static function ( array $pdf ): array {
				return [
					'form' => $pdf['form'],
					'name' => $pdf['name'],
					'url'  => admin_url( sprintf( 'admin.php?page=gf_edit_forms&view=settings&subview=PDF&id=%d&pid=%s', $pdf['form_id'], rawurlencode( $pdf['pdf_id'] ) ) ),
				];
			},
			$this->get_font_pdfs()[ $key ] ?? []
		);
	}

	/**
	 * Forget the stored usage. Stored as a marker, so a rebuild already reading the forms can tell it's out of date.
	 *
	 * @since 6.19.0
	 */
	public function clear_font_usage(): void {
		$this->font_pdfs = null;

		update_option( self::FONT_PDFS, [ 'cleared' => uniqid( '', true ) ], false );

		$this->queue_site_fonts();
	}

	/**
	 * Record the current site's fonts when the request ends, so a bulk change only reads its forms once
	 *
	 * @since 6.19.0
	 */
	public function queue_site_fonts(): void {
		if ( ! is_multisite() ) {
			return;
		}

		add_action( 'shutdown', [ $this, 'write_queued_site_fonts' ] );

		$this->queued_sites[ get_current_blog_id() ] = true;
	}

	/**
	 * @since 6.19.0
	 */
	public function write_queued_site_fonts(): void {
		$sites              = array_keys( $this->queued_sites );
		$this->queued_sites = [];

		/* A site deleted in this request has no tables left to read */
		$sites = array_filter(
			$sites,
			static function ( $site_id ): bool {
				return get_site( $site_id ) instanceof WP_Site;
			}
		);

		array_map( [ $this, 'write_site_fonts' ], $sites );
	}

	/**
	 * Read the sites' settings ahead in one query, for the per-site reads that follow; [] ends it. A site deleted since
	 * is left out, as its missing table would fail the query.
	 *
	 * @param int[] $site_ids
	 *
	 * @since 6.19.0
	 */
	public function prime_site_options( array $site_ids ): void {
		if ( is_multisite() && $site_ids !== [] ) {
			/* Whole sites, which also fills the site cache that get_site() reads */
			$sites = get_sites(
				[
					'site__in' => array_map( 'intval', $site_ids ),
					'number'   => 0,
				]
			);

			$site_ids = array_map( 'intval', wp_list_pluck( $sites, 'blog_id' ) );
		}

		$this->site_options->prime( $site_ids, self::SITE_OPTIONS );
	}

	/**
	 * Record the font keys a site uses, in a row of its own so sites saving at once can't overwrite each other
	 *
	 * @since 6.19.0
	 */
	public function write_site_fonts( int $site_id ): void {
		$name = self::SITE_FONTS_PREFIX . $site_id;
		$keys = $this->get_site_font_keys( $site_id );

		if ( $keys !== null ) {
			update_network_option( get_main_network_id(), $name, $keys );

			return;
		}

		/* Without a record the site's fonts stay locked, where a stale one could let a font it now uses be deleted */
		delete_network_option( get_main_network_id(), $name );

		$downloader = \GPDFAPI::get_mvc_class( 'Model_Font_Downloader' );
		if ( $downloader instanceof Model_Font_Downloader ) {
			$downloader->schedule_site_fonts_retry();
		}
	}

	/**
	 * @param mixed $site The site just created
	 *
	 * @since 6.19.0
	 */
	public function write_new_site_fonts( $site ): void {
		if ( $site instanceof WP_Site ) {
			$this->write_site_fonts( (int) $site->id );
		}
	}

	/**
	 * @param mixed $site The site being deleted
	 *
	 * @since 6.19.0
	 */
	public function delete_site_fonts( $site ): void {
		if ( $site instanceof WP_Site ) {
			delete_network_option( get_main_network_id(), self::SITE_FONTS_PREFIX . (int) $site->id );
		}
	}

	/**
	 * Every site that hasn't recorded its fonts
	 *
	 * @return int[]
	 *
	 * @since 6.19.0
	 */
	public function get_sites_without_fonts(): array {
		if ( ! is_multisite() ) {
			return [];
		}

		return array_values( array_diff( $this->get_site_ids(), array_keys( $this->get_site_font_records() ) ) );
	}

	/**
	 * Every site's recorded font keys, in one query
	 *
	 * @return array<int, string[]> By site ID
	 *
	 * @since 6.19.0
	 */
	protected function get_site_font_records(): array {
		global $wpdb;

		if ( ! is_multisite() ) {
			return [];
		}

		$rows = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$wpdb->sitemeta} WHERE site_id = %d AND meta_key LIKE %s",
				get_main_network_id(),
				$wpdb->esc_like( self::SITE_FONTS_PREFIX ) . '%'
			)
		);

		$sites = [];
		foreach ( (array) $rows as $row ) {
			$keys = maybe_unserialize( $row->meta_value );
			if ( is_array( $keys ) ) {
				$sites[ (int) substr( $row->meta_key, strlen( self::SITE_FONTS_PREFIX ) ) ] = $keys;
			}
		}

		return $sites;
	}

	/**
	 * Each font selected by each PDF of a site's active forms, read lazily so a caller can stop at the one it wants
	 *
	 * @param string $prefix Another site's table prefix, or '' for this site
	 *
	 * @return \Generator<string, array{0: array, 1: string, 2: array}> Font key => form row, PDF ID and PDF settings
	 *
	 * @phpstan-impure Reading flags a failed query
	 *
	 * @since 6.19.0
	 */
	protected function each_pdf_font( string $prefix = '' ): \Generator {
		/* A page at a time, so a site with many forms doesn't hold every form's meta at once */
		$after = 0;
		do {
			$page = $this->get_pdf_form_meta( $after, $this->form_page, $prefix );
			foreach ( $page as $row ) {
				$after = (int) $row['form_id'];
				foreach ( $this->get_form_pdfs( $row['display_meta'], (int) $row['form_id'] ) as $pdf_id => $pdf ) {
					foreach ( $this->get_pdf_fonts( $pdf ) as $font ) {
						yield $font => [ $row, (string) $pdf_id, $pdf ];
					}
				}
			}

			$full = count( $page ) === $this->form_page;
		} while ( $full );
	}

	/**
	 * Count the other sites using each font from their records. A site without one could be using any core font.
	 *
	 * @param array<string, array> $reasons
	 *
	 * @return array<string, array>
	 *
	 * @since 6.19.0
	 */
	protected function add_other_site_reasons( array $reasons ): array {
		$other_sites = array_fill_keys( $this->get_other_site_ids(), true );
		$records     = array_intersect_key( $this->get_site_font_records(), $other_sites );

		foreach ( $records as $keys ) {
			foreach ( $keys as $key ) {
				$reasons[ $key ]['sites'] = ( $reasons[ $key ]['sites'] ?? 0 ) + 1;
			}
		}

		if ( count( $records ) < count( $other_sites ) ) {
			foreach ( array_keys( $this->font_sources->get_font_data() ) as $key ) {
				if ( $this->get_font_files( (string) $key ) !== [] ) {
					$reasons[ $key ]['pending'] = true;
				}
			}
		}

		return $reasons;
	}

	/**
	 * @return int[] Every other site, across all networks
	 *
	 * @since 6.19.0
	 */
	protected function get_other_site_ids(): array {
		return array_values( array_diff( $this->get_site_ids(), [ get_current_blog_id() ] ) );
	}

	/**
	 * The fonts a site uses (its default font, locale and PDF fonts), read from its tables by prefix rather than by
	 * switching to it
	 *
	 * @param string $unset_default The default font of a site with none saved
	 *
	 * @return string[]|null Font keys, or null when the site's settings or forms couldn't be read
	 *
	 * @since 6.19.0
	 */
	public function get_site_font_keys( int $site_id, string $unset_default = '' ): ?array {
		global $wpdb;

		$prefix  = $wpdb->get_blog_prefix( $site_id );
		$options = $this->site_options->get_options( [ $site_id ], self::SITE_OPTIONS )[ $site_id ] ?? null;

		/* Read as unset, the site's default font would be left out */
		if ( $options === null ) {
			$this->log->error( 'A site\'s settings could not be read for its fonts', [ 'site_id' => $site_id ] );

			return null;
		}

		$settings = maybe_unserialize( $options['gfpdf_settings'] ?? '' );
		$default  = $this->normalise_font( is_array( $settings ) ? ( $settings['default_font'] ?? '' ) : '' );
		$locales  = $this->font_sources->map_language_font_locales( [ $options['WPLANG'] ?? '' ] );

		$keys = array_merge( array_map( 'strval', array_keys( $locales ) ), [ $default !== '' ? $default : $unset_default ] );
		$keys = array_fill_keys(
			array_filter(
				$keys,
				static function ( string $key ): bool {
					return $key !== '';
				}
			),
			true
		);

		/* Gravity Forms records its version as it creates a site's tables, so a site without one has no forms */
		$this->form_read_failed = false;
		foreach ( isset( $options['gf_db_version'] ) ? $this->each_pdf_font( $prefix ) : [] as $font => $unused ) {
			$keys[ $font ] = true;
		}

		return $this->form_read_failed ? null : array_map( 'strval', array_keys( $keys ) );
	}

	/**
	 * Every site's ID, across all networks
	 *
	 * @return int[]
	 *
	 * @since 6.19.0
	 */
	public function get_site_ids(): array {
		if ( ! is_multisite() ) {
			return [ get_current_blog_id() ];
		}

		return array_map(
			'intval',
			get_sites(
				[
					'fields' => 'ids',
					'number' => 0,
				]
			)
		);
	}

	/**
	 * A page of the stored meta of the active forms with PDFs, read raw so Gravity Forms doesn't build their fields.
	 * Pages by ID, as OFFSET would run the LIKE over every form it skips. The LIKE compares bytes, about 6x faster than
	 * the column's collation.
	 *
	 * @param string $prefix Another site's table prefix, or '' for this site
	 *
	 * @return array[] Rows of `form_id` and `display_meta`
	 *
	 * @since 6.19.0
	 */
	protected function get_pdf_form_meta( int $after_id, int $limit, string $prefix = '' ): array {
		global $wpdb;

		$meta_table = $prefix === '' ? GFFormsModel::get_meta_table_name() : $prefix . 'gf_form_meta';
		$form_table = $prefix === '' ? GFFormsModel::get_form_table_name() : $prefix . 'gf_form';

		/* The table names come from Gravity Forms */
		//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT meta.form_id, meta.display_meta, form.title FROM {$meta_table} meta
				INNER JOIN {$form_table} form ON form.id = meta.form_id
				WHERE form.is_active = 1 AND form.is_trash = 0 AND meta.form_id > %d AND meta.display_meta LIKE CAST( %s AS BINARY )
				ORDER BY meta.form_id LIMIT %d",
				$after_id,
				'%' . $wpdb->esc_like( '"gfpdf_form_settings"' ) . '%',
				$limit
			),
			ARRAY_A
		);
		//phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		/* A failed query reads as no forms, which would leave every PDF's fonts out of the required set */
		if ( $wpdb->last_error !== '' ) {
			$this->form_read_failed = true;
			$this->log->error( 'Forms could not be read for their PDF fonts', [ 'error' => $wpdb->last_error ] );
		}

		return (array) $rows;
	}
}
