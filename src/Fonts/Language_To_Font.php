<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

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
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Language_To_Font implements LanguageToFontInterface {

	/**
	 * `und-<script>` code => script name
	 *
	 * ISO 15924 by way of mPDF, which asks with the tag `Mpdf::markScriptToLang()` built from the run's Unicode
	 * block. Every script a published pack claims is here, every script a 6.x site's adopted fonts still answer
	 * for, and every tag the catalogue's coverage chips name (`ScriptChips.js`), so a pack cannot draw a name on
	 * its card and a bare code in the language table.
	 *
	 * Its own constant rather than part of `LANGUAGE_LABELS` for that reason: this half has a counterpart to stay
	 * level with. The names deliberately differ from the chips' — "Arabic script" here because the row sits next
	 * to `ar`, "Arabic" there because nothing sits next to it.
	 *
	 * @since 7.0
	 */
	public const SCRIPT_LABELS = [
		'und-arab' => 'Arabic script',
		'und-armn' => 'Armenian script',
		'und-beng' => 'Bengali script',
		'und-bopo' => 'Bopomofo',
		'und-brai' => 'Braille',
		'und-cans' => 'Canadian Aboriginal syllabics',
		'und-copt' => 'Coptic script',
		'und-cprt' => 'Cypriot syllabary',
		'und-cyrl' => 'Cyrillic script',
		'und-deva' => 'Devanagari script',
		'und-dsrt' => 'Deseret',
		'und-egyp' => 'Egyptian hieroglyphs',
		'und-ethi' => 'Ethiopic script',
		'und-geor' => 'Georgian script',
		'und-glag' => 'Glagolitic',
		'und-goth' => 'Gothic script',
		'und-grek' => 'Greek script',
		'und-gujr' => 'Gujarati script',
		'und-guru' => 'Gurmukhi script',
		'und-hang' => 'Hangul',
		'und-hani' => 'Han',
		'und-hans' => 'Han (Simplified)',
		'und-hant' => 'Han (Traditional)',
		'und-hebr' => 'Hebrew script',
		'und-hira' => 'Hiragana',
		'und-ital' => 'Old Italic',
		'und-jpan' => 'Japanese',
		'und-kali' => 'Kayah Li',
		'und-kana' => 'Katakana',
		'und-khar' => 'Kharoshthi',
		'und-khmr' => 'Khmer script',
		'und-knda' => 'Kannada script',
		'und-kore' => 'Korean',
		'und-lana' => 'Tai Tham',
		'und-laoo' => 'Lao script',
		'und-latn' => 'Latin script',
		'und-linb' => 'Linear B',
		'und-mlym' => 'Malayalam script',
		'und-mong' => 'Mongolian script',
		'und-mtei' => 'Meetei Mayek',
		'und-mymr' => 'Myanmar script',
		'und-ogam' => 'Ogham',
		'und-orya' => 'Odia script',
		'und-osma' => 'Osmanya',
		'und-runr' => 'Runic',
		'und-shaw' => 'Shavian',
		'und-sinh' => 'Sinhala script',
		'und-sund' => 'Sundanese script',
		'und-syrc' => 'Syriac script',
		'und-talu' => 'New Tai Lue',
		'und-taml' => 'Tamil script',
		'und-tavt' => 'Tai Viet',
		'und-telu' => 'Telugu script',
		'und-tfng' => 'Tifinagh',
		'und-thai' => 'Thai script',
		'und-tibt' => 'Tibetan script',
		'und-xsux' => 'Cuneiform',
		'und-yiii' => 'Yi script',
		'und-zsye' => 'Emoji',
	];

	/**
	 * Language code => language name
	 *
	 * Both the two- and three-letter forms, because both are map keys: mPDF's own language list carries the pairs
	 * and the catalogue's `languages` column mirrors it, so a pack claiming `bn` and `ben` produces two rows an
	 * admin can set independently. The Latin-script languages at the end are not claimed by any pack — they are
	 * what the "Default document language" select needs, since a site's answer there is usually the one language
	 * no pack has to carry.
	 *
	 * @since 7.0
	 */
	public const LANGUAGE_LABELS = [
		'ab'    => 'Abkhazian',
		'abk'   => 'Abkhazian',
		'am'    => 'Amharic',
		'amh'   => 'Amharic',
		'ar'    => 'Arabic',
		'ara'   => 'Arabic',
		'as'    => 'Assamese',
		'asm'   => 'Assamese',
		'av'    => 'Avaric',
		'ava'   => 'Avaric',
		'ba'    => 'Bashkir',
		'bak'   => 'Bashkir',
		'ban'   => 'Balinese',
		'be'    => 'Belarusian',
		'bel'   => 'Belarusian',
		'ben'   => 'Bengali',
		'bg'    => 'Bulgarian',
		'bh'    => 'Bihari',
		'bih'   => 'Bihari',
		'bku'   => 'Buhid',
		'blt'   => 'Tai Dam',
		'bn'    => 'Bengali',
		'bo'    => 'Tibetan',
		'bod'   => 'Tibetan',
		'bug'   => 'Buginese',
		'bul'   => 'Bulgarian',
		'ce'    => 'Chechen',
		'che'   => 'Chechen',
		'chr'   => 'Cherokee',
		'chu'   => 'Church Slavonic',
		'chv'   => 'Chuvash',
		'cop'   => 'Coptic',
		'cr'    => 'Cree',
		'cre'   => 'Cree',
		'cu'    => 'Church Slavonic',
		'cv'    => 'Chuvash',
		'div'   => 'Dhivehi',
		'dv'    => 'Dhivehi',
		'dz'    => 'Dzongkha',
		'dzo'   => 'Dzongkha',
		'el'    => 'Greek',
		'ell'   => 'Greek',
		'fa'    => 'Persian',
		'fas'   => 'Persian',
		'got'   => 'Gothic',
		'gu'    => 'Gujarati',
		'guj'   => 'Gujarati',
		'he'    => 'Hebrew',
		'heb'   => 'Hebrew',
		'hi'    => 'Hindi',
		'hin'   => 'Hindi',
		'hnn'   => 'Hanunoo',
		'hy'    => 'Armenian',
		'hye'   => 'Armenian',
		'ii'    => 'Sichuan Yi',
		'iii'   => 'Sichuan Yi',
		'iku'   => 'Inuktitut',
		'iu'    => 'Inuktitut',
		'ja'    => 'Japanese',
		'jpn'   => 'Japanese',
		'jv'    => 'Javanese',
		'ka'    => 'Georgian',
		'kan'   => 'Kannada',
		'kas'   => 'Kashmiri',
		'kat'   => 'Georgian',
		'kaz'   => 'Kazakh',
		'khm'   => 'Khmer',
		'kir'   => 'Kyrgyz',
		'kk'    => 'Kazakh',
		'km'    => 'Khmer',
		'kn'    => 'Kannada',
		'ko'    => 'Korean',
		'kom'   => 'Komi',
		'kor'   => 'Korean',
		'ks'    => 'Kashmiri',
		'ku'    => 'Kurdish',
		'kur'   => 'Kurdish',
		'kv'    => 'Komi',
		'ky'    => 'Kyrgyz',
		'lao'   => 'Lao',
		'lif'   => 'Limbu',
		'lis'   => 'Lisu',
		'lo'    => 'Lao',
		'mal'   => 'Malayalam',
		'mar'   => 'Marathi',
		'mk'    => 'Macedonian',
		'mkd'   => 'Macedonian',
		'ml'    => 'Malayalam',
		'mn'    => 'Mongolian',
		'mr'    => 'Marathi',
		'my'    => 'Burmese',
		'mya'   => 'Burmese',
		'ne'    => 'Nepali',
		'nep'   => 'Nepali',
		'nqo'   => "N'Ko",
		'oj'    => 'Ojibwe',
		'oji'   => 'Ojibwe',
		'or'    => 'Odia',
		'ori'   => 'Odia',
		'os'    => 'Ossetian',
		'oss'   => 'Ossetian',
		'pa'    => 'Punjabi',
		'pan'   => 'Punjabi',
		'phn'   => 'Phoenician',
		'ps'    => 'Pashto',
		'pus'   => 'Pashto',
		'ru'    => 'Russian',
		'rus'   => 'Russian',
		'sa'    => 'Sanskrit',
		'san'   => 'Sanskrit',
		'sat'   => 'Santali',
		'sd'    => 'Sindhi',
		'si'    => 'Sinhala',
		'sin'   => 'Sinhala',
		'snd'   => 'Sindhi',
		'sr'    => 'Serbian',
		'srp'   => 'Serbian',
		'su'    => 'Sundanese',
		'syl'   => 'Sylheti',
		'syr'   => 'Syriac',
		'ta'    => 'Tamil',
		'tam'   => 'Tamil',
		'tat'   => 'Tatar',
		'tbw'   => 'Tagbanwa',
		'tdd'   => 'Tai Nuea',
		'te'    => 'Telugu',
		'tel'   => 'Telugu',
		'tg'    => 'Tajik',
		'tgk'   => 'Tajik',
		'th'    => 'Thai',
		'tha'   => 'Thai',
		'ti'    => 'Tigrinya',
		'tir'   => 'Tigrinya',
		'tk'    => 'Turkmen',
		'tl'    => 'Tagalog',
		'tt'    => 'Tatar',
		'tuk'   => 'Turkmen',
		'ug'    => 'Uyghur',
		'uga'   => 'Ugaritic',
		'uig'   => 'Uyghur',
		'uk'    => 'Ukrainian',
		'ukr'   => 'Ukrainian',
		'ur'    => 'Urdu',
		'urd'   => 'Urdu',
		'vai'   => 'Vai',
		'vi'    => 'Vietnamese',
		'vie'   => 'Vietnamese',
		'xcr'   => 'Carian',
		'xlc'   => 'Lycian',
		'xld'   => 'Lydian',
		'yi'    => 'Yiddish',
		'yid'   => 'Yiddish',
		'zh'    => 'Chinese',
		'zh-hk' => 'Chinese (Hong Kong)',
		'zh-tw' => 'Chinese (Taiwan)',
		'zho'   => 'Chinese',

		/* Latin-script languages no pack claims, for the "Default document language" select */
		'af'    => 'Afrikaans',
		'az'    => 'Azerbaijani',
		'ca'    => 'Catalan',
		'cs'    => 'Czech',
		'cy'    => 'Welsh',
		'da'    => 'Danish',
		'de'    => 'German',
		'en'    => 'English',
		'es'    => 'Spanish',
		'et'    => 'Estonian',
		'eu'    => 'Basque',
		'fi'    => 'Finnish',
		'fil'   => 'Filipino',
		'fr'    => 'French',
		'ga'    => 'Irish',
		'gl'    => 'Galician',
		'hr'    => 'Croatian',
		'hu'    => 'Hungarian',
		'id'    => 'Indonesian',
		'is'    => 'Icelandic',
		'it'    => 'Italian',
		'lt'    => 'Lithuanian',
		'lv'    => 'Latvian',
		'ms'    => 'Malay',
		'nb'    => 'Norwegian (Bokmål)',
		'nl'    => 'Dutch',
		'nn'    => 'Norwegian (Nynorsk)',
		'pl'    => 'Polish',
		'pt'    => 'Portuguese',
		'ro'    => 'Romanian',
		'sk'    => 'Slovak',
		'sl'    => 'Slovenian',
		'sq'    => 'Albanian',
		'sv'    => 'Swedish',
		'sw'    => 'Swahili',
		'tr'    => 'Turkish',
		'uz'    => 'Uzbek',
	];

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
		foreach ( static::candidates( (string) $mode ) as $candidate ) {
			if ( isset( $this->map[ $candidate ] ) && $this->map[ $candidate ] !== '' ) {
				return $this->map[ $candidate ];
			}
		}

		return '';
	}

	/**
	 * The lookups to try, most specific first
	 *
	 * Public and static because the install side walks the same ladder: `Coverage_Resolver` matches a locale
	 * against the catalogue's `languages` column, and a rung the render resolves but the trigger never asked for
	 * is a font that renders and was never installed.
	 *
	 * @return string[]
	 *
	 * @since 7.0
	 */
	public static function candidates( string $mode ): array {
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

	/**
	 * Every code the settings screen can show, as `code => display name`
	 *
	 * Returned once beside the map by `GET /fonts/settings` and used three ways — the default-language select, the
	 * rows of the language table and the "add a language" list — so a name and a code can only disagree in one
	 * place. Translated here rather than in the constants because a `const` cannot call `__()`, and never on the
	 * render path: nothing under `getLanguageOptions()` reads this.
	 *
	 * @return array<string, string>
	 *
	 * @since 7.0
	 */
	public static function labels(): array {
		/*
		 * ~250 `__()` lookups and a sort, memoised because `Helper_Options_Fields::get_registered_fields()` is one
		 * caller and that runs on every REST request the site serves — `GET /download/{entry}/{pdf}` included.
		 * Keyed on the locale rather than a bare flag: `switch_to_locale()` is how a notification email renders a
		 * PDF in the recipient's language, and a table built before the switch would answer in the wrong one.
		 */
		static $cache = [];

		$locale = determine_locale();

		if ( isset( $cache[ $locale ] ) ) {
			return $cache[ $locale ];
		}

		$labels = [];

		foreach ( static::LANGUAGE_LABELS + static::SCRIPT_LABELS as $code => $label ) {
			/* phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- a constant table of literals, one per language */
			$labels[ $code ] = __( $label, 'gravity-pdf' );
		}

		asort( $labels );

		$cache[ $locale ] = $labels;

		return $labels;
	}
}
