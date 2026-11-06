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
	 * @var array<string, int>|null
	 * @since 6.19.0
	 */
	protected $selected_font_counts;

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
		$this->selected_font_counts = null;
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
		return $this->get_reason_files( $this->get_font_reasons() );
	}

	/**
	 * The files of the keys get_font_reasons() returned, plus every required source's
	 *
	 * @param array<string, array> $reasons
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_reason_files( array $reasons ): array {
		$files = [];
		foreach ( array_keys( $reasons ) as $key ) {
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
		$reasons = [];

		$default = $this->normalise_font( $this->options->get_option( 'default_font', '' ) );
		if ( $default !== '' ) {
			$reasons[ $default ]            = $none;
			$reasons[ $default ]['default'] = true;
		}

		foreach ( $this->font_sources->get_language_font_keys() as $key ) {
			$reasons[ $key ]             = $reasons[ $key ] ?? $none;
			$reasons[ $key ]['language'] = true;
		}

		foreach ( $this->get_selected_font_counts() as $key => $count ) {
			$reasons[ $key ]         = $reasons[ $key ] ?? $none;
			$reasons[ $key ]['pdfs'] = $count;
		}

		foreach ( $this->font_sources->get_sources() as $source ) {
			foreach ( empty( $source['required'] ) ? [] : array_keys( $source['families'] ?? [] ) as $key ) {
				$reasons[ $key ]           = $reasons[ $key ] ?? $none;
				$reasons[ $key ]['source'] = true;
			}
		}

		foreach ( $network ? $this->get_other_site_font_counts() : [] as $key => $count ) {
			$reasons[ $key ]          = $reasons[ $key ] ?? $none;
			$reasons[ $key ]['sites'] = $count;
		}

		return $reasons;
	}

	/**
	 * Whether anything requires the font key, so deleting it would break or re-download it
	 *
	 * @since 6.19.0
	 */
	public function is_font_in_use( string $key, bool $network = false ): bool {
		return isset( $this->get_font_reasons( $network )[ $key ] );
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
			if ( ! $this->font_sources->is_downloaded( $file ) ) {
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
				'installed' => count( array_filter( $files, [ $this->font_sources, 'is_downloaded' ] ) ),
				'total'     => count( $files ),
				'manifest'  => count( $manifest ) > 0,
			];
		}

		return $counts;
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
	 * How many of the active forms' PDFs select each font, read once per request
	 *
	 * @return array<string, int>
	 *
	 * @since 6.19.0
	 */
	protected function get_selected_font_counts(): array {
		if ( $this->selected_font_counts === null ) {
			$this->selected_font_counts = $this->count_pdf_fonts();
		}

		return $this->selected_font_counts;
	}

	/**
	 * How many of a site's active form PDFs select each font
	 *
	 * @param string $prefix Another site's table prefix, or '' for this site
	 *
	 * @return array<string, int>
	 *
	 * @since 6.19.0
	 */
	protected function count_pdf_fonts( string $prefix = '' ): array {
		$counts = [];

		/* A page at a time, so a site with many forms doesn't hold every form's meta at once */
		$after = 0;
		do {
			$page = $this->get_pdf_form_meta( $after, $this->form_page, $prefix );
			foreach ( $page as $row ) {
				$after = (int) $row['form_id'];
				foreach ( $this->get_form_pdfs( $row['display_meta'], (int) $row['form_id'] ) as $pdf ) {
					foreach ( $this->get_pdf_fonts( $pdf ) as $font ) {
						$counts[ $font ] = ( $counts[ $font ] ?? 0 ) + 1;
					}
				}
			}

			$full = count( $page ) === $this->form_page;
		} while ( $full );

		return $counts;
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
		if ( ! is_multisite() ) {
			return [];
		}

		$counts = [];
		$sites  = get_sites(
			[
				'fields'       => 'ids',
				'number'       => 0,
				'site__not_in' => [ get_current_blog_id() ],
			]
		);

		foreach ( $sites as $site_id ) {
			foreach ( $this->get_site_font_keys( (int) $site_id ) as $key ) {
				$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + 1;
			}
		}

		return $counts;
	}

	/**
	 * The fonts a site uses (its default font, locale and PDF fonts), read from its tables by prefix rather than by
	 * switching to it
	 *
	 * @param int    $site_id
	 * @param string $unset_default The default font of a site with none saved
	 *
	 * @return string[] Font keys
	 *
	 * @since 6.19.0
	 */
	public function get_site_font_keys( int $site_id, string $unset_default = '' ): array {
		global $wpdb;

		$prefix  = $wpdb->get_blog_prefix( $site_id );
		$options = $wpdb->get_results( "SELECT option_name, option_value FROM {$prefix}options WHERE option_name IN ( 'gfpdf_settings', 'gf_db_version', 'WPLANG' )", OBJECT_K ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$options = wp_list_pluck( (array) $options, 'option_value' );

		$settings = maybe_unserialize( $options['gfpdf_settings'] ?? '' );
		$default  = $this->normalise_font( is_array( $settings ) ? ( $settings['default_font'] ?? '' ) : '' );
		$locales  = $this->font_sources->map_language_font_locales( [ $options['WPLANG'] ?? '' ] );
		$keys     = array_merge( array_map( 'strval', array_keys( $locales ) ), [ $default !== '' ? $default : $unset_default ] );

		/* Gravity Forms records its version as it creates a site's tables, so a site without one has no forms */
		if ( isset( $options['gf_db_version'] ) ) {
			$keys = array_merge( $keys, array_keys( $this->count_pdf_fonts( $prefix ) ) );
		}

		return array_values( array_unique( array_filter( array_map( [ $this, 'normalise_font' ], $keys ) ) ) );
	}

	/**
	 * A page of the stored meta of the active forms with PDFs, read raw so Gravity Forms doesn't build their fields.
	 * Pages by ID, as OFFSET would run the LIKE over every form it skips.
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
				"SELECT meta.form_id, meta.display_meta FROM {$meta_table} meta
				INNER JOIN {$form_table} form ON form.id = meta.form_id
				WHERE form.is_active = 1 AND form.is_trash = 0 AND meta.form_id > %d AND meta.display_meta LIKE %s
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
