<?php

declare( strict_types=1 );

namespace GFPDF\Model;

use GFFormsModel;
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
 * Which downloadable font files a site needs: its default font's, its languages', every required source's and those
 * of every font its PDFs name
 *
 * @since 6.19.0
 */
class Model_Required_Fonts extends Helper_Abstract_Model {

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
	 * @var string[]|null
	 * @since 6.19.0
	 */
	protected $selected_font_keys;

	/**
	 * @since 6.19.0
	 */
	public function __construct( Model_Font_Sources $font_sources, Helper_Abstract_Options $options ) {
		$this->font_sources = $font_sources;
		$this->options      = $options;
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
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	public function get_font_files( string $key ): array {
		$family = $this->font_sources->get_font_data()[ $this->normalise_font( $key ) ] ?? null;

		$files = [];
		foreach ( [ 'R', 'B', 'I', 'BI' ] as $style ) {
			$file = is_array( $family ) ? ( $family[ $style ] ?? '' ) : '';
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

		foreach ( $this->font_sources->get_sources() as $source ) {
			if ( ! empty( $source['required'] ) ) {
				$files = array_merge( $files, array_keys( $this->font_sources->read_manifest( (string) ( $source['manifest'] ?? '' ) ) ) );
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
		foreach ( $this->get_font_files( $this->normalise_font( $font ) ) as $file ) {
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

			$manifest = $this->font_sources->read_manifest( (string) $source['manifest'] );
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
	 * @param mixed $font
	 *
	 * @since 6.19.0
	 */
	protected function normalise_font( $font ): string {
		return is_string( $font ) ? strtolower( trim( $font ) ) : '';
	}

	/**
	 * The fonts selected in every form's PDFs
	 *
	 * @return string[]
	 *
	 * @since 6.19.0
	 */
	protected function get_selected_font_keys(): array {
		if ( $this->selected_font_keys === null ) {
			$keys     = [];
			$form_ids = GFFormsModel::get_form_ids( true, false );

			/* One query for every active form */
			foreach ( count( $form_ids ) > 0 ? GFFormsModel::get_form_meta_by_id( $form_ids ) : [] as $form ) {
				$pdfs = is_array( $form ) && is_array( $form['gfpdf_form_settings'] ?? null ) ? $form['gfpdf_form_settings'] : [];
				foreach ( $pdfs as $pdf ) {
					if ( is_array( $pdf ) ) {
						$keys = array_merge( $keys, $this->get_pdf_fonts( $pdf ) );
					}
				}
			}

			$this->selected_font_keys = array_values( array_unique( $keys ) );
		}

		return $this->selected_font_keys;
	}
}
