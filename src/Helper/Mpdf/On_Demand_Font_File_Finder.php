<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Mpdf;

use GFPDF\Helper\Helper_Render_Health;
use GFPDF\Model\Model_Font_Downloader;
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
 * Downloads a missing font, or queues a large one for the background sync, else falls back to a font on disk (outside
 * Debug Mode)
 *
 * @since 6.19.0
 */
class On_Demand_Font_File_Finder extends FontFileFinder {

	/**
	 * @var Model_Font_Downloader
	 * @since 6.19.0
	 */
	protected $downloader;

	/**
	 * Find a large core font file without downloading it: its hashed copy if there, else the file at its name unless that
	 * is another font's, else queue the manifest's for the sync, mark the render degraded and use the stand-in
	 *
	 * @param string             $name  The font file name
	 * @param string             $found The file at its name, or '' if there is none
	 * @param MpdfException|null $error Why there is none
	 *
	 * @throws MpdfException
	 *
	 * @since 6.19.0
	 */
	protected function find_large_file( string $name, string $found, ?MpdfException $error ): string {
		if ( $this->downloader->has_hashed_copy( $name ) ) {
			return $this->downloader->get_font_path( $name );
		}

		if ( $error === null && ! $this->downloader->is_size_conflict( $name ) ) {
			return $found;
		}

		$queued = $this->downloader->queue( [ $name ] );
		if ( $queued ) {
			Helper_Render_Health::mark();
		}

		/* The file at the name stays in use until the sync saves the manifest's beside it */
		if ( $error === null ) {
			return $found;
		}

		/* Debug Mode falls back too for a queued file, as the font is on its way */
		$fallback = $this->fallback || $queued ? $this->get_fallback_file() : '';
		if ( $fallback === '' ) {
			throw $error;
		}

		return $fallback;
	}

	/**
	 * The default font's Regular file, the first fallback
	 *
	 * @var string
	 * @since 6.19.0
	 */
	protected $default_font_file;

	/**
	 * Whether a font that can't download falls back rather than throwing, as it does outside Debug Mode
	 *
	 * @var bool
	 * @since 6.19.0
	 */
	protected $fallback;

	/**
	 * Search the font directories, downloading a missing font through the downloader
	 *
	 * @param string|string[] $directories
	 * @param bool            $fallback    Fall back to a font on disk rather than throw (off in Debug Mode)
	 *
	 * @since 6.19.0
	 */
	public function __construct( $directories, Model_Font_Downloader $downloader, string $default_font_file, bool $fallback = true ) {
		parent::__construct( $directories );

		$this->downloader        = $downloader;
		$this->default_font_file = $default_font_file;
		$this->fallback          = $fallback;
	}

	/**
	 * Find a font file, downloading a missing font, else falling back outside Debug Mode
	 *
	 * @param mixed $name The font file name
	 *
	 * @return string
	 * @throws MpdfException
	 *
	 * @since 6.19.0
	 */
	public function findFontFile( $name ) {
		/* An empty name would match a font directory itself */
		if ( ! is_string( $name ) || $name === '' ) {
			throw new MpdfException( esc_html( sprintf( 'Cannot find TTF TrueType font file "%s" in configured font directories.', is_scalar( $name ) ? (string) $name : gettype( $name ) ) ) );
		}

		$found = '';
		$error = null;

		try {
			$found = parent::findFontFile( $name );
		} catch ( MpdfException $e ) {
			$error = $e;
		}

		/* Bundled fonts and those in other directories aren't on the manifest */
		if ( $error === null && ! $this->downloader->is_in_fonts_folder( $found ) ) {
			return $found;
		}

		if ( ! $this->downloader->is_core_font_file( $name ) ) {
			if ( $error !== null ) {
				throw $error;
			}

			return $found;
		}

		/* A Font Manager record's file belongs to the record. The core font of that name has a hashed name. */
		if ( $error === null && $this->downloader->is_record_file( $name ) ) {
			return $found;
		}

		/* A large file is left to the background sync, so a render never waits on it */
		if ( $this->downloader->is_large_file( $name ) ) {
			return $this->find_large_file( $name, $found, $error );
		}

		/* The downloader's fonts folder, as mPDF's AddFontDirectory() replaces the search directories. Stat calls only. */
		if ( $this->downloader->download( $name ) ) {
			return $this->downloader->get_font_path( $name );
		}

		/* The file at the name stays in use if its replacement can't be saved */
		if ( $error === null ) {
			return $found;
		}

		$fallback = $this->fallback ? $this->get_fallback_file() : '';
		if ( $fallback === '' ) {
			throw $error;
		}

		return $fallback;
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
