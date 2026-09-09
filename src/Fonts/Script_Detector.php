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
 * Which scripts a document needs that the site cannot draw yet
 *
 * Two cheap refusals before any real work, because this runs on every render: text the bundled faces can draw
 * entirely never reaches the walk, and neither does anything at all once every coverage entry is installed. A
 * fully provisioned Latin site therefore pays one failed PCRE pass.
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
	 * @var Coverage_Resolver
	 * @since 7.0
	 */
	protected $resolver;

	/**
	 * @var ScriptToLanguage
	 * @since 7.0
	 */
	protected $languages;

	public function __construct( Coverage_Resolver $resolver ) {
		$this->resolver  = $resolver;
		$this->languages = new ScriptToLanguage();
	}

	/**
	 * The language tags this text needs and the site has no font for
	 *
	 * @param string ...$text Any strings the document draws — entry values, labels, header and footer settings
	 *
	 * @return string[] Lower-cased mPDF language tags, e.g. `ja`, `und-hans`
	 *
	 * @since 7.0
	 */
	public function detect( string ...$text ): array {
		$characters = $this->uncovered_characters( $text );

		if ( $characters === [] ) {
			return [];
		}

		$wanted = $this->resolver->uninstalled_scripts();

		if ( $wanted === [] ) {
			return [];
		}

		$detected = [];

		foreach ( $characters as $character ) {
			$tag = $this->tag_for( $character );

			if ( isset( $wanted[ $tag ] ) ) {
				$detected[ $tag ] = true;
			}
		}

		return array_keys( $detected );
	}

	/**
	 * The distinct characters none of the bundled faces can draw
	 *
	 * The gate, and the reason this class is affordable on the render path: one PCRE pass per string against a
	 * class generated from those faces' own cmaps, which on Latin/Greek/Cyrillic text matches nothing.
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
