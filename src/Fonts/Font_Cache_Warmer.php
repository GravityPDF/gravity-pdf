<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF\Helper\Helper_Data;
use GFPDF_Vendor\Mpdf\Language\LanguageToFontRegistry;
use GFPDF_Vendor\Mpdf\Mpdf;
use GFPDF_Vendor\Psr\Log\LoggerInterface;
use Throwable;
use WP_Error;

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
 * Parses newly installed faces so no render is the first to do it
 *
 * mPDF builds a font's metrics cache the first time it draws with it, reading the whole sfnt to do it — seconds on a
 * 17 MB CJK face, in whichever request happens to be first. That request is normally a form submission waiting on a
 * PDF, which is the wrong place to spend it.
 *
 * The second reason is the more important one: a font mPDF cannot parse throws `MpdfException`, and if the first
 * parse happens mid-render the whole PDF dies. Doing it here turns that into a `failed` catalog row while the site
 * keeps rendering with the fonts it already has.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Font_Cache_Warmer {

	/**
	 * @var Registry
	 * @since 7.0
	 */
	protected $registry;

	/**
	 * @var Helper_Data
	 * @since 7.0
	 */
	protected $data;

	/**
	 * @var LoggerInterface
	 * @since 7.0
	 */
	protected $log;

	public function __construct( Registry $registry, Helper_Data $data, LoggerInterface $log ) {
		$this->registry = $registry;
		$this->data     = $data;
		$this->log      = $log;
	}

	/**
	 * Parse every face given, writing its `.mtx.json` and `.cw.dat`
	 *
	 * The mPDF built here is font-only: `mpdf_font_config()` plus somewhere to put the cache. Everything else
	 * `Helper_PDF` passes is document-shaped — paper size, margins, watermarks — and none of it changes how a face
	 * parses, so a warm needs no form, entry or settings and can run from a background task.
	 *
	 * @param array<string, string[]> $faces `{ font_key: role[] }`
	 *
	 * @return WP_Error|null The first face that would not parse, or null when they all did
	 *
	 * @since 7.0
	 */
	public function warm( array $faces ): ?WP_Error {
		$faces = $this->parseable( $faces );

		if ( $faces === [] ) {
			return null;
		}

		try {
			$mpdf = new Mpdf(
				$this->registry->mpdf_font_config( new LanguageToFontRegistry() ) + [ 'tempDir' => $this->data->mpdf_tmp_location ]
			);

			foreach ( $faces as $font_key => $roles ) {
				foreach ( $roles as $role ) {
					/* mPDF names the regular face with no suffix, which is also how its cache files are named */
					$mpdf->AddFont( $font_key, $role === 'R' ? '' : $role );
				}
			}
		} catch ( Throwable $e ) {
			$this->log->error(
				'A newly installed font could not be parsed',
				[
					'fonts' => array_keys( $faces ),
					'error' => $e->getMessage(),
				]
			);

			return new WP_Error( 'font_unparseable', sprintf( 'mPDF could not read the installed font: %s', $e->getMessage() ) );
		}

		return null;
	}

	/**
	 * Drop everything that is not one of the four faces mPDF can be asked for
	 *
	 * A row's files also carry the `dict_*` line-break dictionaries, which are data the shaper reads rather than
	 * fonts — `AddFont()` would throw on them.
	 *
	 * @param array<string, string[]> $faces
	 *
	 * @return array<string, string[]>
	 *
	 * @since 7.0
	 */
	protected function parseable( array $faces ): array {
		$parseable = [];

		foreach ( $faces as $font_key => $roles ) {
			$roles = array_values( array_intersect( (array) $roles, Font_Repository::FACE_ROLES ) );

			if ( $roles === [] ) {
				continue;
			}

			$parseable[ (string) $font_key ] = $roles;
		}

		return $parseable;
	}
}
