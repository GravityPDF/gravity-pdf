<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use GFPDF\Model\Model_Core_Font_Downloader;
use GFPDF_Vendor\Mpdf\Fonts\FontFileFinder;
use GFPDF_Vendor\Mpdf\MpdfException;

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
 * Downloads a missing core font, else falls back to a font on disk (outside Debug Mode)
 *
 * @since 6.19.0
 */
class On_Demand_Font_File_Finder extends FontFileFinder {

	/**
	 * @var Model_Core_Font_Downloader
	 * @since 6.19.0
	 */
	protected $downloader;

	/**
	 * The default font's Regular file, the first fallback
	 *
	 * @var string
	 * @since 6.19.0
	 */
	protected $default_font_file;

	/**
	 * @param string|string[] $directories
	 *
	 * @since 6.19.0
	 */
	public function __construct( $directories, Model_Core_Font_Downloader $downloader, string $default_font_file ) {
		parent::__construct( $directories );

		$this->downloader        = $downloader;
		$this->default_font_file = $default_font_file;
	}

	/**
	 * @throws MpdfException
	 *
	 * @since 6.19.0
	 */
	public function findFontFile( $name ) {
		/* An empty name would match a font directory itself */
		if ( ! is_string( $name ) || $name === '' ) {
			throw new MpdfException( esc_html( sprintf( 'Cannot find TTF TrueType font file "%s" in configured font directories.', is_scalar( $name ) ? (string) $name : gettype( $name ) ) ) );
		}

		try {
			return parent::findFontFile( $name );
		} catch ( MpdfException $e ) {
			if ( ! $this->downloader->is_core_font_file( $name ) ) {
				throw $e;
			}

			/* The downloader's fonts folder, as mPDF's AddFontDirectory() replaces the search directories */
			if ( $this->downloader->download( $name ) ) {
				return $this->downloader->get_font_path( $name );
			}

			$fallback = $this->downloader->is_debug_mode() ? '' : $this->get_fallback_file();
			if ( $fallback === '' ) {
				throw $e;
			}

			return $fallback;
		}
	}

	/**
	 * The default font's Regular file if it's on disk, else the bundled Arimo Regular, else '' if that's missing too
	 *
	 * @since 6.19.0
	 */
	protected function get_fallback_file(): string {
		if ( $this->default_font_file !== '' ) {
			try {
				return parent::findFontFile( $this->default_font_file );
			} catch ( MpdfException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- not on disk, use Arimo
			}
		}

		return $this->downloader->get_fallback_file();
	}
}
