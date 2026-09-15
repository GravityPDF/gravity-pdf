<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Helper\Mpdf\Cache;
use GFPDF_Vendor\Mpdf\Fonts\FontCache;
use GFPDF_Vendor\Mpdf\Fonts\TTFontFileAnalysis;

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
 * What a font file says about itself, for the two upload questions its bytes can answer
 *
 * `TtfFontValidation` asks whether a file is a font mPDF can read at all and refuses the upload when it is not.
 * This asks the questions where the answer is advice rather than a refusal: whether each face is the face its
 * slot claims, whether the four of them are one family, and whether any of them carries right-to-left text.
 *
 * Reads through mPDF's `TTFontFileAnalysis`, which exists for exactly this — it parses the name, head, OS/2 and
 * cmap tables and stops, leaving the layout tables it would need for rendering unread. Four uploads are four
 * parses on a request an administrator is already waiting on, so the cheap read is the right one.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class FontFaceAnalysis {

	/**
	 * The style each slot is promising, which is the whole of what a mismatch is measured against
	 *
	 * @since 7.0
	 */
	public const SLOT_STYLES = [
		'regular'     => [
			'bold'   => false,
			'italic' => false,
		],
		'italics'     => [
			'bold'   => false,
			'italic' => true,
		],
		'bold'        => [
			'bold'   => true,
			'italic' => false,
		],
		'bolditalics' => [
			'bold'   => true,
			'italic' => true,
		],
	];

	/**
	 * @var string
	 * @since 7.0
	 */
	protected $font_directory_path;

	public function __construct( string $font_directory_path ) {
		$this->font_directory_path = $font_directory_path;
	}

	/**
	 * What one font file says its family, style and direction are
	 *
	 * @param string $file The filename of a font file in the font directory
	 *
	 * @return array{family: string, bold: bool, italic: bool, rtl: bool}|null Null where the file will not parse,
	 *                                                                        which is `TtfFontValidation`'s to report
	 *
	 * @since 7.0
	 */
	public function analyse( string $file ): ?array {
		try {
			$data = \GPDFAPI::get_data_class();

			$analysis = new TTFontFileAnalysis(
				new FontCache( new Cache( $data->mpdf_tmp_location . '/mpdf' ) ),
				apply_filters( 'gpdf_mpdf_font_descriptor', 'win' )
			);

			[ $family, $bold, $italic, , , $rtl ] = $analysis->extractCoreInfo( $this->font_directory_path . $file );

			return [
				'family' => (string) $family,
				'bold'   => (bool) $bold,
				'italic' => (bool) $italic,
				'rtl'    => (bool) $rtl,
			];
		} catch ( \Throwable $e ) {
			return null;
		}
	}

	/**
	 * Whether any face carries right-to-left characters
	 *
	 * Gates Kashida, which is an Arabic justification feature and does nothing anywhere else. Coarse on purpose:
	 * the flag means the cmap covers an RTL block, so a Latin family that happens to ship Hebrew answers true.
	 * Erring that way costs a setting that will not fire; erring the other way would strip Kashida from the fonts
	 * it exists for.
	 *
	 * @param array $files `{ slot: { name: filename } }`, as the upload path holds them
	 *
	 * @since 7.0
	 */
	public function has_rtl( array $files ): bool {
		foreach ( $files as $file ) {
			if ( ! isset( $file['name'] ) ) {
				continue;
			}

			$face = $this->analyse( (string) $file['name'] );

			if ( $face !== null && $face['rtl'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * What is worth telling an administrator about the four files they just uploaded
	 *
	 * Advice, never a refusal. A face whose style bits are unset renders exactly as well as one that sets them —
	 * mPDF reads the file it is pointed at and does not consult them — so the mismatch is a sign the wrong file
	 * went into the slot, not a fault in the font. Refusing the upload over a metadata bit would block families
	 * whose faces are simply flagged loosely, which is a worse outcome than saying so and letting them decide.
	 *
	 * @param array $files `{ slot: { name: filename } }`, as the upload path holds them
	 *
	 * @return string[] Human-readable warnings, empty when the four files look like what they claim
	 *
	 * @since 7.0
	 */
	public function warnings( array $files ): array {
		$warnings = [];
		$families = [];

		foreach ( $files as $slot => $file ) {
			if ( ! isset( $file['name'], static::SLOT_STYLES[ $slot ] ) ) {
				continue;
			}

			$face = $this->analyse( (string) $file['name'] );

			if ( $face === null ) {
				continue;
			}

			if ( $face['family'] !== '' ) {
				$families[ $face['family'] ][] = $slot;
			}

			$expected = static::SLOT_STYLES[ $slot ];

			if ( $face['bold'] !== $expected['bold'] || $face['italic'] !== $expected['italic'] ) {
				$warnings[] = sprintf(
					/* translators: 1: filename, 2: the slot it was uploaded to, e.g. Bold Italic, 3: the style the file says it is */
					__( '%1$s was uploaded as %2$s but describes itself as %3$s.', 'gravity-pdf' ),
					(string) $file['name'],
					$this->style_name( $expected['bold'], $expected['italic'] ),
					$this->style_name( $face['bold'], $face['italic'] )
				);
			}
		}

		if ( count( $families ) > 1 ) {
			$warnings[] = sprintf(
				/* translators: %s: a comma-separated list of font family names */
				__( 'These files come from more than one font family: %s.', 'gravity-pdf' ),
				implode( ', ', array_keys( $families ) )
			);
		}

		return $warnings;
	}

	/**
	 * The name of a style, for a message an administrator reads
	 *
	 * @since 7.0
	 */
	protected function style_name( bool $bold, bool $italic ): string {
		if ( $bold && $italic ) {
			return __( 'Bold Italic', 'gravity-pdf' );
		}

		if ( $bold ) {
			return __( 'Bold', 'gravity-pdf' );
		}

		if ( $italic ) {
			return __( 'Italic', 'gravity-pdf' );
		}

		return __( 'Regular', 'gravity-pdf' );
	}
}
