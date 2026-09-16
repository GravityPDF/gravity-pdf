<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

use GFPDF_Vendor\Mpdf\Language\ScriptToLanguage;
use GFPDF_Vendor\Mpdf\Ucdn;

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
 * Which of the scripts a site still needs fonts for appear in a document
 *
 * The caller says what is worth looking for and this says which of it is there, so a site with nothing left to
 * install never reaches this class at all. Past that the gate is one PCRE pass per string against a class
 * generated from the bundled faces' own cmaps, which on Latin text matches nothing.
 *
 * The vocabulary is mPDF's own — `Ucdn` for the script of a code point, `ScriptToLanguage` for the tag — so the
 * tags returned here are the same strings the catalogue's `scripts` column carries and the same ones the language
 * map resolves at render time. No plugin-side script table exists, and none should: a second one would drift.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Script_Detector {

	/**
	 * @var ScriptToLanguage
	 * @since 7.0
	 */
	protected $languages;

	public function __construct() {
		$this->languages = new ScriptToLanguage();
	}

	/**
	 * Which of the wanted tags this text uses
	 *
	 * @param array<string, true> $wanted The tags worth looking for, from `Coverage_Resolver::uninstalled_scripts()`
	 * @param string[]            $text   Any strings the document draws — entry values, labels, headers, footers
	 *
	 * @return string[] Lower-cased mPDF language tags, e.g. `ja`, `und-hans`
	 *
	 * @since 7.0
	 */
	public function detect( array $wanted, array $text ): array {
		if ( $wanted === [] ) {
			return [];
		}

		$detected = [];

		foreach ( $this->uncovered_characters( $text ) as $character ) {
			$tag = $this->tag_for( $character );

			if ( ! isset( $wanted[ $tag ] ) ) {
				continue;
			}

			$detected[ $tag ] = true;

			/* Everything asked about is accounted for, and the rest of the document cannot change the answer */
			if ( count( $detected ) === count( $wanted ) ) {
				break;
			}
		}

		return array_keys( $detected );
	}

	/**
	 * The distinct characters none of the bundled faces can draw
	 *
	 * @param string[] $text
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	protected function uncovered_characters( array $text ): array {
		$found = [];

		foreach ( $text as $string ) {
			/* A subject that is not valid UTF-8 makes `preg_match_all` fail rather than match, and that is the answer */
			if ( ! preg_match_all( Bundled_Coverage::UNCOVERED, $string, $matches ) ) {
				continue;
			}

			$found += array_flip( $matches[0] );
		}

		return array_keys( $found );
	}

	/**
	 * One character's mPDF language tag, or `''` where mPDF has no opinion
	 *
	 * Not every script maps to one, and several map to a plain language code rather than a `und-` tag: kana both
	 * give `ja`, Hangul `ko`. Han gives `und-Hans` whatever the characters are, which is why a kanji-only document
	 * resolves to Simplified Chinese — the only one of the three CJK packs that covers all of Han.
	 *
	 * @since 7.0
	 */
	protected function tag_for( string $character ): string {
		$language = $this->languages->getLanguageByScript( Ucdn::get_script( mb_ord( $character, 'UTF-8' ) ) );

		return strtolower( (string) $language );
	}
}
