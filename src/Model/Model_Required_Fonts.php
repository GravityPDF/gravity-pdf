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
	 * @var string[]|null
	 * @since 6.19.0
	 */
	protected $selected_font_keys;

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
		$this->selected_font_keys = null;
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
		$keys = array_merge(
			[ $this->normalise_font( $this->options->get_option( 'default_font', '' ) ) ],
			$this->font_sources->get_language_font_keys(),
			$this->get_selected_font_keys()
		);

		$files = [];
		foreach ( array_unique( $keys ) as $key ) {
			$files = array_merge( $files, $this->get_font_files( $key ) );
		}

		foreach ( $this->font_sources->get_sources() as $id => $source ) {
			if ( ! empty( $source['required'] ) ) {
				$files = array_merge( $files, array_keys( $this->font_sources->get_source_manifest( (string) $id ) ) );
			}
		}

		return array_values( array_unique( $files ) );
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
	 * The fonts selected in every active form's PDFs
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_selected_font_keys(): array {
		if ( $this->selected_font_keys === null ) {
			$keys = [];

			/* A page at a time, so a site with many forms doesn't hold every form's meta at once */
			$offset = 0;
			do {
				$page = $this->get_pdf_form_meta( $offset, $this->form_page );
				foreach ( $page as $row ) {
					foreach ( $this->get_form_pdfs( $row['display_meta'], (int) $row['form_id'] ) as $pdf ) {
						$keys = array_merge( $keys, $this->get_pdf_fonts( $pdf ) );
					}
				}

				$offset += $this->form_page;
				$full    = count( $page ) === $this->form_page;
			} while ( $full );

			$this->selected_font_keys = array_values( array_unique( $keys ) );
		}

		return $this->selected_font_keys;
	}

	/**
	 * A page of the stored meta of the active forms with PDFs, read raw so Gravity Forms doesn't build their fields
	 *
	 * @return array[] Rows of `form_id` and `display_meta`
	 *
	 * @since 6.19.0
	 */
	protected function get_pdf_form_meta( int $offset, int $limit ): array {
		global $wpdb;

		$meta_table = GFFormsModel::get_meta_table_name();
		$form_table = GFFormsModel::get_form_table_name();

		/* The table names come from Gravity Forms */
		//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT meta.form_id, meta.display_meta FROM {$meta_table} meta
				INNER JOIN {$form_table} form ON form.id = meta.form_id
				WHERE form.is_active = 1 AND form.is_trash = 0 AND meta.display_meta LIKE %s
				ORDER BY meta.form_id LIMIT %d OFFSET %d",
				'%' . $wpdb->esc_like( '"gfpdf_form_settings"' ) . '%',
				$limit,
				$offset
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
