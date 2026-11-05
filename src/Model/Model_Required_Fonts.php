<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFFormsModel;
use GFPDF\Helper\Helper_Abstract_Model;
use GFPDF\Helper\Helper_Abstract_Options;
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
 * Which downloadable font files a site needs: its default font's, its languages', every required source's and those
 * of every font its PDFs name
 *
 * @since 6.19.0
 */
class Model_Required_Fonts extends Helper_Abstract_Model {

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
	 * @var array<string, array<int, array{form: string, name: string, url: string}>>|null
	 * @since 6.19.0
	 */
	protected $font_pdfs;

	/**
	 * Work out the required fonts from the font sources and the saved settings, logging forms it can't read
	 *
	 * @since 6.19.0
	 */
	public function __construct( Model_Font_Sources $font_sources, Helper_Abstract_Options $options, LoggerInterface $log ) {
		$this->font_sources = $font_sources;
		$this->options      = $options;
		$this->log          = $log;
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
			$file = $family[ $style ] ?? '';
			if ( is_string( $file ) && $file !== '' && $this->font_sources->is_core_font_file( $file ) ) {
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
	 * required source and, with $network, used by that many other sites
	 *
	 * @return array<string, array{default: bool, language: bool, pdfs: int, source: bool, sites: int}>
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
		];
		$reasons = $this->get_settings_font_reasons();

		foreach ( array_map( 'count', $this->get_font_pdfs() ) as $key => $count ) {
			$reasons[ $key ]['pdfs'] = $count;
		}

		foreach ( $network ? $this->get_other_site_font_counts() : [] as $key => $count ) {
			$reasons[ $key ]['sites'] = $count;
		}

		return array_map(
			static function ( array $reason ) use ( $none ): array {
				return array_merge( $none, $reason );
			},
			$reasons
		);
	}

	/**
	 * Whether anything requires the font key, so deleting it would break or re-download it
	 *
	 * @since 6.19.0
	 */
	public function is_font_in_use( string $key, bool $network = false ): bool {
		if ( isset( $this->get_settings_font_reasons()[ $key ] ) || isset( $this->font_pdfs[ $key ] ) ) {
			return true;
		}

		if ( $this->font_pdfs === null && $this->yields_font( $this->each_pdf_font(), $key ) ) {
			return true;
		}

		foreach ( $network ? $this->get_other_site_ids() : [] as $site_id ) {
			if ( $this->yields_font( $this->each_site_font_key( $site_id ), $key ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a generator keyed by font key reaches the font, reading no further than it
	 *
	 * @since 6.19.0
	 */
	protected function yields_font( \Generator $fonts, string $key ): bool {
		foreach ( $fonts as $font => $unused ) {
			if ( $font === $key ) {
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
			if ( ! $this->font_sources->is_installed( $file ) ) {
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
				'installed' => count( array_filter( $files, [ $this->font_sources, 'is_installed' ] ) ),
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
					return ! $this->font_sources->is_installed( $file );
				}
			)
		);
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
	 * The active forms' PDFs that select each font, read once per request
	 *
	 * @return array<string, array<int, array{form: string, name: string, url: string}>> PDFs by font key
	 *
	 * @since 6.19.0
	 */
	public function get_font_pdfs(): array {
		if ( $this->font_pdfs !== null ) {
			return $this->font_pdfs;
		}

		$pdfs = [];

		foreach ( $this->each_pdf_font() as $font => list( $row, $pdf_id, $pdf ) ) {
			$pdfs[ $font ][] = [
				'form' => (string) ( $row['title'] ?? '' ),
				'name' => is_string( $pdf['name'] ?? null ) ? $pdf['name'] : '',
				'url'  => admin_url( sprintf( 'admin.php?page=gf_edit_forms&view=settings&subview=PDF&id=%d&pid=%s', (int) $row['form_id'], rawurlencode( $pdf_id ) ) ),
			];
		}

		$this->font_pdfs = $pdfs;

		return $pdfs;
	}

	/**
	 * Each font selected by each PDF of a site's active forms, read lazily so a caller can stop at the one it wants
	 *
	 * @param string $prefix Another site's table prefix, or '' for this site
	 *
	 * @return \Generator<string, array{0: array, 1: string, 2: array}> Font key => form row, PDF ID and PDF settings
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
	 * How many other sites of the network use each font. Read without switch_to_blog(), so no other site's plugins or
	 * filters run.
	 *
	 * @return array<string, int>
	 *
	 * @since 6.19.0
	 */
	protected function get_other_site_font_counts(): array {
		$counts = [];

		foreach ( $this->get_other_site_ids() as $site_id ) {
			foreach ( $this->get_site_font_keys( $site_id ) as $key ) {
				$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + 1;
			}
		}

		return $counts;
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
	 * @return string[] Font keys
	 *
	 * @since 6.19.0
	 */
	public function get_site_font_keys( int $site_id, string $unset_default = '' ): array {
		return array_map( 'strval', array_keys( iterator_to_array( $this->each_site_font_key( $site_id, $unset_default ) ) ) );
	}

	/**
	 * Each font key a site uses, the settings' before its forms' so a caller can stop without reading them
	 *
	 * @param string $unset_default The default font of a site with none saved
	 *
	 * @return \Generator<string, mixed> Keyed by font key, which may repeat
	 *
	 * @since 6.19.0
	 */
	protected function each_site_font_key( int $site_id, string $unset_default = '' ): \Generator {
		global $wpdb;

		$prefix  = $wpdb->get_blog_prefix( $site_id );
		$options = $wpdb->get_results( "SELECT option_name, option_value FROM {$prefix}options WHERE option_name IN ( 'gfpdf_settings', 'gf_db_version', 'WPLANG' )", OBJECT_K ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$options = wp_list_pluck( (array) $options, 'option_value' );

		$settings = maybe_unserialize( $options['gfpdf_settings'] ?? '' );
		$default  = $this->normalise_font( is_array( $settings ) ? ( $settings['default_font'] ?? '' ) : '' );
		$locales  = $this->font_sources->map_language_font_locales( [ $options['WPLANG'] ?? '' ] );

		foreach ( array_merge( array_map( 'strval', array_keys( $locales ) ), [ $default !== '' ? $default : $unset_default ] ) as $key ) {
			if ( $key !== '' ) {
				yield $key => true;
			}
		}

		/* Gravity Forms records its version as it creates a site's tables, so a site without one has no forms */
		if ( isset( $options['gf_db_version'] ) ) {
			yield from $this->each_pdf_font( $prefix );
		}
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
			$this->log->error( 'Forms could not be read for their PDF fonts', [ 'error' => $wpdb->last_error ] );
		}

		return (array) $rows;
	}
}
