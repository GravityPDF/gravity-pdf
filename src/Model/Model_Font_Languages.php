<?php

declare( strict_types=1 );

namespace GFPDF\Model;

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
 * What the core fonts can write, worked out from the characters in each font file
 *
 * @since 6.19.0
 */
class Model_Font_Languages {

	/**
	 * The languages or scripts of each core font, a sample for those without Latin letters worth showing
	 *
	 * @return array<string, array{languages: string[], sample?: string}> By font key
	 *
	 * @since 6.19.0
	 */
	public function get_font_languages(): array {
		$latin    = __( 'Latin', 'gravity-pdf' );
		$greek    = __( 'Greek', 'gravity-pdf' );
		$cyrillic = __( 'Cyrillic', 'gravity-pdf' );
		$armenian = __( 'Armenian', 'gravity-pdf' );
		$georgian = __( 'Georgian', 'gravity-pdf' );
		$hebrew   = __( 'Hebrew', 'gravity-pdf' );
		$arabic   = __( 'Arabic', 'gravity-pdf' );
		$persian  = __( 'Persian', 'gravity-pdf' );
		$urdu     = __( 'Urdu', 'gravity-pdf' );
		$syriac   = __( 'Syriac', 'gravity-pdf' );
		$thaana   = __( 'Thaana', 'gravity-pdf' );
		$thai     = __( 'Thai', 'gravity-pdf' );
		$lao      = __( 'Lao', 'gravity-pdf' );
		$cherokee = __( 'Cherokee', 'gravity-pdf' );
		$ucas     = __( 'Canadian Aboriginal', 'gravity-pdf' );
		$tifinagh = __( 'Tifinagh', 'gravity-pdf' );
		$burmese  = __( 'Burmese', 'gravity-pdf' );
		$tibetan  = __( 'Tibetan', 'gravity-pdf' );
		$sinhala  = __( 'Sinhala', 'gravity-pdf' );
		$korean   = __( 'Korean', 'gravity-pdf' );
		$ethiopic = __( 'Ethiopic', 'gravity-pdf' );
		$gothic   = __( 'Gothic', 'gravity-pdf' );
		$coptic   = __( 'Coptic', 'gravity-pdf' );
		$italic   = __( 'Old Italic', 'gravity-pdf' );
		$ugaritic = __( 'Ugaritic', 'gravity-pdf' );
		$phoenic  = __( 'Phoenician', 'gravity-pdf' );
		$linear_b = __( 'Linear B', 'gravity-pdf' );
		$cypriot  = __( 'Cypriot', 'gravity-pdf' );
		$tai_le   = __( 'Tai Le', 'gravity-pdf' );
		$bugis    = __( 'Buginese', 'gravity-pdf' );
		$vai      = __( 'Vai', 'gravity-pdf' );
		$runic    = __( 'Runic', 'gravity-pdf' );
		$devan    = __( 'Devanagari', 'gravity-pdf' );
		$bengali  = __( 'Bengali', 'gravity-pdf' );
		$gurmukhi = __( 'Gurmukhi', 'gravity-pdf' );
		$gujarati = __( 'Gujarati', 'gravity-pdf' );
		$oriya    = __( 'Oriya', 'gravity-pdf' );
		$tamil    = __( 'Tamil', 'gravity-pdf' );
		$telugu   = __( 'Telugu', 'gravity-pdf' );
		$kannada  = __( 'Kannada', 'gravity-pdf' );
		$malayalm = __( 'Malayalam', 'gravity-pdf' );
		$khmer    = __( 'Khmer', 'gravity-pdf' );

		$dejavu_sans  = [ $latin, $greek, $cyrillic, $armenian, $georgian, $hebrew, $arabic, $lao, __( "N'Ko", 'gravity-pdf' ), $tifinagh, $ucas ];
		$dejavu_serif = [ $latin, $greek, $cyrillic, $armenian, $georgian ];
		$burmese_font = [
			'languages' => [ $burmese ],
			'sample'    => 'မင်္ဂလာပါ',
		];

		return [
			'dejavusanscondensed'   => [ 'languages' => $dejavu_sans ],
			'dejavusans'            => [ 'languages' => $dejavu_sans ],
			'dejavuserifcondensed'  => [ 'languages' => $dejavu_serif ],
			'dejavuserif'           => [ 'languages' => $dejavu_serif ],
			'dejavusansmono'        => [ 'languages' => [ $latin, $greek, $cyrillic, $armenian, $georgian, $arabic, $lao ] ],
			'freesans'              => [ 'languages' => [ $latin, $greek, $cyrillic, $armenian, $georgian, $hebrew, $syriac, $devan, $bengali, $gurmukhi, $malayalm, $cherokee, $ucas, $vai, $tifinagh, $ugaritic, $phoenic ] ],
			'freeserif'             => [ 'languages' => [ $latin, $greek, $cyrillic, $armenian, $georgian, $hebrew, $arabic, $thaana, $devan, $bengali, $gurmukhi, $gujarati, $oriya, $tamil, $malayalm, $sinhala, $thai, $ethiopic, $cherokee, $tai_le, $bugis, $gothic, $coptic ] ],
			'freemono'              => [ 'languages' => [ $latin, $greek, $cyrillic, $armenian, $georgian, $hebrew, $arabic, $cherokee, $runic, $italic ] ],
			'mph2bdamase'           => [ 'languages' => [ $latin, $greek, $cyrillic, $armenian, $georgian, $hebrew, $arabic, $thaana, $cherokee, __( 'Limbu', 'gravity-pdf' ), $tai_le, $bugis, $linear_b, $italic, $gothic, $ugaritic, $phoenic, $cypriot, $coptic, $tifinagh ] ],
			'quivira'               => [ 'languages' => [ $latin, $greek, $cyrillic, $armenian, $georgian, $hebrew, $thai, $cherokee, $ucas, __( 'Ogham', 'gravity-pdf' ), $runic, __( 'Tagalog', 'gravity-pdf' ), $vai, $italic, $gothic, $phoenic, $coptic, $tifinagh ] ],
			'sun-exta'              => [
				'languages' => [ __( 'Chinese', 'gravity-pdf' ), __( 'Japanese', 'gravity-pdf' ), $korean, $latin, $greek, $cyrillic, $armenian, $georgian, $hebrew, $arabic, $syriac, $thaana, $devan, $bengali, $gurmukhi, $gujarati, $oriya, $tamil, $telugu, $kannada, $malayalm, $thai, $lao, $tibetan, $burmese, $ethiopic, $cherokee, $ucas, $khmer, __( 'Mongolian', 'gravity-pdf' ) ],
				'sample'    => '你好,世界 こんにちは 안녕하세요',
			],
			'unbatang'              => [
				'languages' => [ $korean ],
				'sample'    => '안녕하세요',
			],
			'lohitkannada'          => [
				'languages' => [ $kannada ],
				'sample'    => 'ನಮಸ್ಕಾರ',
			],
			'pothana2000'           => [
				'languages' => [ $telugu ],
				'sample'    => 'నమస్కారం',
			],
			'xbriyaz'               => [
				'languages' => [ $arabic, $persian, $urdu ],
				'sample'    => 'مرحبا بالعالم',
			],
			'lateef'                => [
				'languages' => [ $arabic, $persian, $urdu, __( 'Sindhi', 'gravity-pdf' ) ],
				'sample'    => 'مرحبا بالعالم',
			],
			'kfgqpcuthmantahanaskh' => [
				'languages' => [ __( 'Quranic Arabic', 'gravity-pdf' ) ],
				'sample'    => 'بسم الله الرحمن الرحيم',
			],
			'estrangeloedessa'      => [
				'languages' => [ $syriac ],
				'sample'    => 'ܫܠܡܐ',
			],
			'kaputaunicode'         => [
				'languages' => [ $sinhala ],
				'sample'    => 'ආයුබෝවන්',
			],
			'abyssinicasil'         => [
				'languages' => [ __( 'Amharic', 'gravity-pdf' ), __( 'Tigrinya', 'gravity-pdf' ), __( "Ge'ez", 'gravity-pdf' ) ],
				'sample'    => 'ሰላም',
			],
			'aboriginalsans'        => [
				'languages' => [ $cherokee, __( 'Cree', 'gravity-pdf' ), __( 'Inuktitut', 'gravity-pdf' ), __( 'Ojibwe', 'gravity-pdf' ) ],
				'sample'    => 'ᎣᏏᏲ ᐊᐃ',
			],
			'jomolhari'             => [
				'languages' => [ $tibetan, __( 'Dzongkha', 'gravity-pdf' ) ],
				'sample'    => 'བཀྲ་ཤིས་བདེ་ལེགས།',
			],
			'sundaneseunicode'      => [
				'languages' => [ __( 'Sundanese', 'gravity-pdf' ) ],
				'sample'    => 'ᮃ ᮄ ᮅ ᮊ ᮋ',
			],
			'taiheritagepro'        => [
				'languages' => [ __( 'Tai Dam', 'gravity-pdf' ) ],
				'sample'    => 'ꪀ ꪁ ꪂ ꪃ ꪄ',
			],
			'aegyptus'              => [
				'languages' => [ __( 'Ancient Egyptian', 'gravity-pdf' ) ],
				'sample'    => '𓀀 𓁐 𓃀 𓆣 𓋹',
			],
			'akkadian'              => [
				'languages' => [ __( 'Akkadian', 'gravity-pdf' ), __( 'Sumerian', 'gravity-pdf' ) ],
				'sample'    => '𒀀 𒀭 𒁲 𒂗 𒆠',
			],
			'aegean'                => [
				'languages' => [ $greek, $linear_b, $cypriot, $phoenic, $ugaritic, $italic ],
				'sample'    => '𐀀 𐀁 𐀂 𐀃 𐀄',
			],
			'lannaalif'             => [
				'languages' => [ __( 'Northern Thai', 'gravity-pdf' ) ],
				'sample'    => 'ᨠ ᨡ ᨢ ᨣ ᨤ',
			],
			'daibannasilbook'       => [
				'languages' => [ __( 'Tai Lü', 'gravity-pdf' ) ],
				'sample'    => 'ᦀ ᦁ ᦂ ᦃ ᦄ',
			],
			'garuda'                => [
				'languages' => [ $thai ],
				'sample'    => 'สวัสดี',
			],
			'khmeros'               => [
				'languages' => [ $khmer ],
				'sample'    => 'សួស្តី',
			],
			'dhyana'                => [
				'languages' => [ $lao ],
				'sample'    => 'ສະບາຍດີ',
			],
			'tharlon'               => $burmese_font,
			'padaukbook'            => $burmese_font,
			'ayar'                  => $burmese_font,
			/* Zawgyi orders vowels differently to Unicode, so a word would come out wrong */
			'zawgyi-one'            => [
				'languages' => [ $burmese ],
				'sample'    => 'က ခ ဂ ဃ င',
			],
			'taameydavidclm'        => [
				'languages' => [ $hebrew ],
				'sample'    => 'שלום עולם',
			],
		];
	}
}
