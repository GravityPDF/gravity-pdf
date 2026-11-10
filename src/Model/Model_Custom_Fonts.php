<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFPDF\Exceptions\GravityPdfIdException;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;

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
 * Class Model_Custom_Fonts
 *
 * @package GFPDF\Model
 *
 * @since   6.0
 */
class Model_Custom_Fonts extends Helper_Abstract_Model {

	/**
	 * @var string[] The keys a font record stores its files under
	 * @since 6.19.0
	 */
	public const FONT_STYLES = [ 'regular', 'italics', 'bold', 'bolditalics' ];

	/**
	 * @var Helper_Abstract_Options
	 * @since 6.0
	 */
	protected $options;

	/**
	 * @var string Each Multisite site's font files are kept in a network option named this plus the site ID
	 * @since 6.19.0
	 */
	public const SITE_FONTS_PREFIX = 'gfpdf_site_custom_fonts_';

	/**
	 * @var string The network-wide object cache group the records are read into
	 * @since 6.19.0
	 */
	public const SITE_FONTS_CACHE = 'gfpdf_site_custom_fonts';

	public function __construct( Helper_Abstract_Options $options ) {
		$this->options = $options;
	}

	/**
	 * A validation callback for the REST API
	 *
	 * @since 6.0
	 */
	public function check_font_name_valid( string $name ): bool {
		return (bool) preg_match( '/^[A-Za-z0-9 ]+$/', $name );
	}

	/**
	 * A validation callback for the REST API
	 *
	 * @since 6.0
	 */
	public function check_font_id_valid( string $name ): bool {
		return (bool) preg_match( '/^[a-z0-9\-]+$/', $name );
	}

	/**
	 * Get a list of the custom fonts installed, indexed by the `id`
	 *
	 * @since 6.0
	 */
	public function get_custom_fonts(): array {
		$fonts = $this->options->get_option( 'custom_fonts' );

		if ( ! is_array( $fonts ) ) {
			return [];
		}

		$font_list = [];
		foreach ( $fonts as $font ) {
			/* A corrupt entry would otherwise fatal the font list and the 6.19 font source adoption */
			if ( ! is_array( $font ) ) {
				continue;
			}

			/* Set defaults for all the non-required fields */
			$font['italics']     = $font['italics'] ?? '';
			$font['bold']        = $font['bold'] ?? '';
			$font['bolditalics'] = $font['bolditalics'] ?? '';
			$font['useOTL']      = $font['useOTL'] ?? 0x00;
			$font['useKashida']  = $font['useKashida'] ?? 0;

			$font_list[ $font['id'] ] = $font;
		}

		return $font_list;
	}

	/**
	 * @param string $id
	 *
	 * @return array matches font
	 * @throws GravityPdfIdException If no matching found can be found
	 *
	 * @since 6.0
	 */
	public function get_font_by_id( string $id ): array {
		$fonts = $this->get_custom_fonts();

		if ( ! isset( $fonts[ $id ] ) ) {
			throw new GravityPdfIdException();
		}

		return $fonts[ $id ];
	}

	/**
	 * @param array $font An individual font setting like you'd find from the self::get_custom_fonts() method
	 *
	 * @return bool
	 * @throws GravityPdfIdException Triggered if `id` already exists
	 *
	 * @since 6.0
	 */
	public function add_font( array $font ): bool {
		$fonts = $this->get_custom_fonts();

		if ( isset( $fonts[ $font['id'] ] ) ) {
			throw new GravityPdfIdException();
		}

		$fonts[ $font['id'] ] = $font;

		return $this->options->update_option( 'custom_fonts', $fonts );
	}

	/**
	 * @param array $font An individual font setting like you'd find from the self::get_custom_fonts() method
	 *
	 * @return bool
	 *
	 * @since 6.0
	 */
	public function update_font( array $font ): bool {
		$fonts                = $this->get_custom_fonts();
		$fonts[ $font['id'] ] = $font;

		return $this->options->update_option( 'custom_fonts', $fonts );
	}

	/**
	 * @param string $id The unique ID of the font to delete
	 *
	 * @return bool
	 * @throws GravityPdfIdException Triggered if `id` already exists
	 */
	public function delete_font( string $id ): bool {
		$fonts = $this->get_custom_fonts();

		if ( ! isset( $fonts[ $id ] ) ) {
			throw new GravityPdfIdException();
		}

		unset( $fonts[ $id ] );

		return $this->options->update_option( 'custom_fonts', $fonts );
	}

	/**
	 * The filenames the other sites' fonts use. A site with no record yet is read from its options.
	 *
	 * @param int $site_id The site to leave out, the current one by default
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_files_used_by_other_sites( int $site_id = 0 ): array {
		$sites = $this->get_sites_fonts();
		unset( $sites[ $site_id ?: get_current_blog_id() ] );

		return array_map( 'strval', array_keys( $this->get_file_set( $sites ) ) );
	}

	/**
	 * The files the sites' recorded fonts use. A site with no record is left for the sync to record, so it's cheap
	 * enough for every PDF.
	 *
	 * @return array<string, true> Filename => true
	 *
	 * @since 6.19.0
	 */
	public function get_recorded_font_files(): array {
		return $this->get_file_set( $this->get_site_records() );
	}

	/**
	 * The files this site's fonts and the sites' recorded fonts use, cheap enough for every PDF
	 *
	 * @return array<string, true> Filename => true
	 *
	 * @since 6.19.0
	 */
	public function get_network_font_files(): array {
		return $this->get_recorded_font_files() + $this->get_file_set( [ $this->get_font_files( $this->get_custom_fonts() ) ] );
	}

	/**
	 * A site's font files by font ID, from its record or else its settings
	 *
	 * @return array<string, string[]>
	 *
	 * @since 6.19.0
	 */
	public function get_site_font_files( int $site_id ): array {
		return $this->get_site_records()[ $site_id ] ?? $this->get_font_files( $this->read_site_fonts( $site_id ) );
	}

	/**
	 * Record a site's font files where every site can read them in one query, in a row of its own so sites saving at
	 * once can't overwrite each other
	 *
	 * @param mixed $fonts The site's `custom_fonts` setting
	 *
	 * @since 6.19.0
	 */
	public function record_site_fonts( int $site_id, $fonts ): void {
		if ( is_multisite() ) {
			update_network_option( get_main_network_id(), self::SITE_FONTS_PREFIX . $site_id, $this->get_font_files( $fonts ) );
		}
	}

	/**
	 * Record a site's font files from its options, unless the site has recorded them since
	 *
	 * @param mixed $site_id
	 *
	 * @since 6.19.0
	 */
	public function record_missing_site_fonts( $site_id ): void {
		$fonts = $this->read_site_fonts( (int) $site_id );

		/* An empty record would give the site's files to other sites' PDFs and deletes, so the sync reads it again */
		if ( $fonts === null ) {
			$downloader = \GPDFAPI::get_mvc_class( 'Model_Font_Downloader' );
			if ( $downloader instanceof Model_Font_Downloader ) {
				$downloader->schedule_site_fonts_retry();
			}

			return;
		}

		add_network_option( get_main_network_id(), self::SITE_FONTS_PREFIX . (int) $site_id, $this->get_font_files( $fonts ) );
	}

	/**
	 * @since 6.19.0
	 */
	public function delete_site_record( int $site_id ): void {
		delete_network_option( get_main_network_id(), self::SITE_FONTS_PREFIX . $site_id );
	}

	/**
	 * @return int[] The sites with no record of their fonts
	 *
	 * @since 6.19.0
	 */
	public function get_sites_without_records(): array {
		return is_multisite() ? array_values( array_diff( $this->get_site_ids(), array_keys( $this->get_site_records() ) ) ) : [];
	}

	/**
	 * Forget the records read, when one changes
	 *
	 * @param mixed $option The network option written
	 *
	 * @since 6.19.0
	 */
	public function maybe_flush_site_records( $option ): void {
		if ( is_string( $option ) && strpos( $option, self::SITE_FONTS_PREFIX ) === 0 ) {
			wp_cache_set_last_changed( self::SITE_FONTS_CACHE );
		}
	}

	/**
	 * Every site's font files by font ID. A site with no record yet is read from its options.
	 *
	 * @return array<int, array<string, string[]>> By site ID
	 *
	 * @since 6.19.0
	 */
	protected function get_sites_fonts(): array {
		if ( ! is_multisite() ) {
			return [];
		}

		$records = $this->get_site_records();
		$sites   = [];
		foreach ( $this->get_site_ids() as $id ) {
			$sites[ $id ] = $records[ $id ] ?? $this->get_font_files( $this->read_site_fonts( $id ) );
		}

		return $sites;
	}

	/**
	 * @return int[]
	 *
	 * @since 6.19.0
	 */
	protected function get_site_ids(): array {
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
	 * Every site's recorded font files, in one query, cached until a record changes
	 *
	 * @return array<int, array<string, string[]>> Each site's font files by font ID, by site ID
	 *
	 * @since 6.19.0
	 */
	protected function get_site_records(): array {
		global $wpdb;

		if ( ! is_multisite() ) {
			return [];
		}

		wp_cache_add_global_groups( self::SITE_FONTS_CACHE );
		$cache_key = 'records:' . wp_cache_get_last_changed( self::SITE_FONTS_CACHE );
		$sites     = wp_cache_get( $cache_key, self::SITE_FONTS_CACHE );
		if ( is_array( $sites ) ) {
			return $sites;
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
			$fonts = maybe_unserialize( $row->meta_value );

			/* A corrupt record counts as none, so the site is read again */
			if ( is_array( $fonts ) ) {
				$sites[ (int) substr( $row->meta_key, strlen( self::SITE_FONTS_PREFIX ) ) ] = array_filter( $fonts, 'is_array' );
			}
		}

		wp_cache_set( $cache_key, $sites, self::SITE_FONTS_CACHE, DAY_IN_SECONDS );

		return $sites;
	}

	/**
	 * A site's `custom_fonts` setting, read from its options table to avoid switch_to_blog()
	 *
	 * @return mixed Null when the options table couldn't be read
	 *
	 * @since 6.19.0
	 */
	protected function read_site_fonts( int $site_id ) {
		global $wpdb;

		$prefix   = $wpdb->get_blog_prefix( $site_id );
		$settings = maybe_unserialize( $wpdb->get_var( "SELECT option_value FROM {$prefix}options WHERE option_name = 'gfpdf_settings' LIMIT 1" ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( $wpdb->last_error !== '' ) {
			\GPDFAPI::get_log_class()->error(
				'A site\'s custom fonts could not be read',
				[
					'site_id' => $site_id,
					'error'   => $wpdb->last_error,
				]
			);

			return null;
		}

		return is_array( $settings ) ? ( $settings['custom_fonts'] ?? [] ) : [];
	}

	/**
	 * @param array<int, array<string, string[]>> $sites Each site's font files by font ID
	 *
	 * @return array<string, true> Filename => true
	 *
	 * @since 6.19.0
	 */
	protected function get_file_set( array $sites ): array {
		$set = [];
		foreach ( $sites as $fonts ) {
			foreach ( $fonts as $files ) {
				foreach ( (array) $files as $file ) {
					$set[ $file ] = true;
				}
			}
		}

		return $set;
	}

	/**
	 * Each font's filenames, by font ID
	 *
	 * @param mixed $fonts A `custom_fonts` setting
	 *
	 * @return array<string, string[]>
	 *
	 * @since 6.19.0
	 */
	protected function get_font_files( $fonts ): array {
		$files = [];

		foreach ( is_array( $fonts ) ? $fonts : [] as $key => $font ) {
			if ( ! is_array( $font ) ) {
				continue;
			}

			/* The setting is keyed by the font ID */
			$id           = (string) ( is_string( $font['id'] ?? null ) ? $font['id'] : $key );
			$files[ $id ] = [];
			foreach ( self::FONT_STYLES as $style ) {
				if ( is_string( $font[ $style ] ?? null ) && $font[ $style ] !== '' ) {
					$files[ $id ][] = basename( $font[ $style ] );
				}
			}
		}

		return $files;
	}

	/**
	 * Checks if the ID already exists and, if so, suffixes the ID with a string until unique
	 *
	 * @return string The unique ID
	 *
	 * @since 6.0
	 */
	public function get_unique_id( string $id ): string {
		$unique_id = false;

		/* mPDF caches a font's metrics by its ID in a folder every site shares, so two sites' fonts can't share one */
		$other_sites = $this->get_other_sites_font_ids();

		do {
			if ( $this->has_unique_font_id( $id ) && ! isset( $other_sites[ $id ] ) ) {
				$unique_id = true;
			} else {
				$id .= substr( (string) time(), -5 );
			}
		} while ( ! $unique_id );

		return $id;
	}

	/**
	 * @since 6.0
	 */
	public function has_unique_font_id( string $id ): bool {
		if (
			! $this->matches_reserved_font_id( $id ) &&
			! $this->matches_core_font_id( $id ) &&
			! $this->matches_custom_font_id( $id )
		) {
			return true;
		}

		return false;
	}

	/**
	 * @since 6.0
	 */
	public function matches_reserved_font_id( string $id ): bool {
		$core_fonts = [
			'arial',
			'helvetica',
			'helveticab',
			'chelvetica',
			'helveticai',
			'helveticabi',
			'chelveticab',
			'chelveticai',
			'chelveticabi',
			'timesnewroman',
			'times',
			'timesb',
			'timesi',
			'timesbi',
			'ctimes',
			'ctimesb',
			'ctimesi',
			'ctimesbi',
			'courier',
			'courierb',
			'courieri',
			'courierbi',
			'ccourier',
			'ccourierb',
			'ccourieri',
			'ccourierbi',
			'zapfdingbats',
			'czapfdingbats',
			'symbol',
			'csymbol',
		];

		return in_array( $id, $core_fonts, true );
	}

	/**
	 * @since 6.0
	 */
	public function matches_core_font_id( string $id ): bool {
		$default_fonts = $this->options->get_installed_fonts();

		unset( $default_fonts[ esc_html__( 'User-Defined Fonts', 'gravity-pdf' ) ] );

		/* check for exact match */
		foreach ( $default_fonts as $group ) {
			if ( isset( $group[ $id ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 6.0
	 */
	public function matches_custom_font_id( string $id ): bool {
		return in_array( $id, array_column( $this->get_custom_fonts(), 'id' ), true );
	}

	/**
	 * @return array<string, true> The IDs of the other sites' fonts => true
	 *
	 * @since 6.19.0
	 */
	protected function get_other_sites_font_ids(): array {
		$sites = $this->get_sites_fonts();
		unset( $sites[ get_current_blog_id() ] );

		$ids = [];
		foreach ( $sites as $fonts ) {
			$ids += array_fill_keys( array_keys( $fonts ), true );
		}

		return $ids;
	}

	/**
	 * @since 6.0
	 */
	public function get_font_short_name( string $name ): string {
		return mb_strtolower( str_replace( ' ', '', $name ), 'UTF-8' );
	}
}
