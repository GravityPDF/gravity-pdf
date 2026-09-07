<?php

declare( strict_types=1 );

namespace GFPDF\Helper\Fonts;

use GFPDF_Vendor\Mpdf\Language\LanguageToFontInterface;

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
 * The plugin's only language-to-font map
 *
 * Map-backed and logic-free: `Registry` computes the effective map and hands it here. mPDF's own
 * `Language\LanguageToFont` is deliberately kept out of the registry — it maps to the 6.x core fonts by name, and
 * none of those keys exist in 7.0.
 *
 * @package GFPDF\Helper\Fonts
 *
 * @since 7.0
 */
class Language_To_Font implements LanguageToFontInterface {

	/**
	 * @var array<string, string> language or script code => font key
	 * @since 7.0
	 */
	protected $map;

	public function __construct( array $map = [] ) {
		$this->map = $map;
	}

	/**
	 * Resolve a language or script tag to a font key
	 *
	 * mPDF asks with the whole tag (`zh-hans`, `und-Arab`, `ja`), so this tries the tag as given, then the parsed
	 * script, then the primary subtag — matching how `Mpdf\Language\LanguageToFont` reads the same string. An empty
	 * answer means "no opinion": the registry moves on to the next member, and if nobody answers mPDF keeps the
	 * current font and lets `useSubstitutions` scavenge glyphs from `backupSubsFont`.
	 *
	 * @param string $mode     The language / script tag
	 * @param bool   $adobeCJK Whether mPDF is in Adobe CJK mode; unused — the plugin never registers those families
	 *
	 * @return string
	 *
	 * @since 7.0
	 */
	public function getLanguageOptions( $mode, $adobeCJK ) {
		foreach ( $this->candidates( (string) $mode ) as $candidate ) {
			if ( isset( $this->map[ $candidate ] ) && $this->map[ $candidate ] !== '' ) {
				return $this->map[ $candidate ];
			}
		}

		return '';
	}

	/**
	 * The lookups to try, most specific first
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function candidates( string $mode ): array {
		$mode = strtolower( $mode );
		if ( $mode === '' ) {
			return [];
		}

		$candidates = [ $mode ];
		$tags       = explode( '-', $mode );

		/* A four-letter second subtag is a script: `und-arab`, `zh-hans` */
		if ( isset( $tags[1] ) && strlen( $tags[1] ) === 4 ) {
			$candidates[] = 'und-' . $tags[1];
		}

		$candidates[] = $tags[0];

		return array_values( array_unique( $candidates ) );
	}

	/**
	 * Map a WordPress locale to an mPDF language tag
	 *
	 * Nothing cleverer than a case fold and a separator swap: `ja_JP` → `ja-jp`, `zh_CN` → `zh-cn`. mPDF's own
	 * parser is what splits `ll-script-cc`, so this is the shape it expects.
	 *
	 * @since 7.0
	 */
	public static function locale_to_language( string $wp_locale ): string {
		return strtolower( str_replace( '_', '-', $wp_locale ) );
	}
}
