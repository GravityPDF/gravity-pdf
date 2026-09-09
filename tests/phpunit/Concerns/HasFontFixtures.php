<?php

declare( strict_types=1 );

namespace GFPDF\Tests\Concerns;

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Real font bytes for tests that install one
 *
 * An install ends by parsing what it downloaded (`Font_Cache_Warmer`), so a placeholder string is no longer a
 * stand-in for a font: it fails the entry, correctly. The bundled faces are the obvious fixtures — they are already
 * in the repository and `DejaVuSansSymbols` is the smallest of them.
 *
 * @since 7.0
 */
trait HasFontFixtures {

	/**
	 * @var array<string, string>
	 */
	private static $font_fixture_bytes = [];

	/**
	 * The bytes of one bundled face
	 *
	 * Read once per process: these are 150-350 KB and several tests want the same one.
	 */
	protected function font_bytes( string $face = 'DejaVuSansSymbols' ): string {
		if ( ! isset( self::$font_fixture_bytes[ $face ] ) ) {
			self::$font_fixture_bytes[ $face ] = (string) file_get_contents( PDF_PLUGIN_DIR . 'fonts/' . $face . '.ttf' );
		}

		return self::$font_fixture_bytes[ $face ];
	}

	/**
	 * The same bytes with one flipped, so the size check passes and only the hash can catch it
	 */
	protected function corrupt_font_bytes( string $face = 'DejaVuSansSymbols' ): string {
		$bytes = $this->font_bytes( $face );
		$at    = intdiv( strlen( $bytes ), 2 );

		$bytes[ $at ] = $bytes[ $at ] === 'A' ? 'B' : 'A';

		return $bytes;
	}
}
