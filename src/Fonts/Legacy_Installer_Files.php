<?php

declare( strict_types=1 );

namespace GFPDF\Fonts;

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
 * What the 6.x core font installer could put on disk, frozen at 7.0
 *
 * The installer downloaded 70 font files from `GravityPDF/mpdf-core-fonts` against a manifest generated from
 * the GitHub Contents API, and mPDF registered them from its own built-in `fontdata` map. 7.0 deletes the
 * installer, the manifest and that map, so an upgraded site would lose every font it had installed unless
 * something remembers them. This is that record — the whole of the plugin's 6.x font knowledge, and the only
 * reason an air-gapped upgrade can keep rendering what it rendered before.
 *
 * Grouped by the internal mPDF font-family name rather than by file, because that is the shape the fonts were
 * registered in: `dejavusanscondensed` is four files, and adopting them as four rows would change the key a
 * template resolves. Adoption reuses each key verbatim, so a template naming one keeps resolving to the same bytes
 * after the upgrade, even where 7.0's language packs ship a different face for that script. `use_otl` and
 * `use_kashida` are carried across for the same reason: they are what shaped the text in 6.x.
 *
 * The second half of each entry is the language data 6.x never stored, because it never had to: `autoLangToFont`
 * read `Mpdf\Language\LanguageToFont` and `useSubstitutions` read the `FontVariables` defaults, both out of the
 * library on every render. 7.0 keeps that class out of the registry — it names keys a fresh install does not have —
 * so the data is frozen here instead, from the fork at `70b90cc~1`, the revision the file names came from. Without
 * it the adopted files would be registered and unreachable: `ko` would resolve to nothing and a site that rendered
 * Korean through UnBatang would render boxes with the file still on its disk.
 *
 * `languages` are the codes that selected the family, `und-<script>` for the ones `fontByScript()` answered.
 * The source map has 173 rows over 31 keys; `eeyekunicode` is the one key with no family here, which corroborates
 * the manifest from the other side, so 172 rows over 30 keys are carried. `backup_subs` is 6.x's `backupSubsFont`
 * (three fonts, not one), `bmp` its `BMPonly`, `sip_ext` the one `sip-ext` entry, and `aliases` the two `fonttrans`
 * entries naming a font 6.x could actually install — the other six name fonts no site ever had.
 *
 * Verify before adopting. `blob` is Git's object hash for the file — `sha1( "blob <size>\0" . contents )` — the
 * hash the manifest already carried, kept in preference to a fresh sha256, which could only be derived by
 * re-downloading the files today and would attest to that download rather than to what the installer shipped.
 *
 * Two things are deliberately absent. The 12 `.txt` licence files the installer also wrote: not fonts, nothing
 * registers them, harmless where they sit. And `Eeyek-Regular.ttf`: upstream added it to `eeyekunicode` after this
 * manifest was generated, so the installer never shipped it and no site can have it.
 *
 * @package GFPDF\Fonts
 *
 * @since 7.0
 */
class Legacy_Installer_Files {

	/**
	 * @var array<string, array{use_otl: int, use_kashida: int, faces: array<string, array{name: string, blob: string, size: int}>, languages?: string[], backup_subs?: bool, bmp?: bool, sip_ext?: string, aliases?: string[]}>
	 * @since 7.0
	 */
	public const FAMILIES = [
		'aboriginalsans'        => [
			'use_otl'     => 0,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'AboriginalSansREGULAR.ttf',
					'blob' => '3f88df0bd636816c78f361dec7b3566f953401b7',
					'size' => 800480,
				],
			],
			'languages'   => [
				'chr',
				'cr',
				'cre',
				'iku',
				'iu',
				'oj',
				'oji',
			],
		],
		'abyssinicasil'         => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Abyssinica_SIL.ttf',
					'blob' => 'f2215e9362fc21d2315f9057025e49bee743d84b',
					'size' => 619012,
				],
			],
			'languages'   => [
				'am',
				'amh',
				'ti',
				'tir',
				'und-ethi',
			],
		],
		'aegean'                => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Aegean.otf',
					'blob' => 'a799f5de9ab068090eed0985c469723ab283a515',
					'size' => 1747560,
				],
			],
			'languages'   => [
				'phn',
				'uga',
				'und-cprt',
				'und-ital',
				'und-linb',
				'xcr',
				'xlc',
				'xld',
			],
		],
		'aegyptus'              => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Aegyptus.otf',
					'blob' => '193a88ab20eecb6914af8b1154d23eb9e88bc1d3',
					'size' => 5841368,
				],
			],
			'languages'   => [
				'und-egyp',
			],
		],
		'akkadian'              => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Akkadian.otf',
					'blob' => '5dfb7adc44e97938883857dae84dc02b94c6e2c4',
					'size' => 1501384,
				],
			],
			'languages'   => [
				'und-xsux',
			],
		],
		'ayar'                  => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'ayar.ttf',
					'blob' => 'e74c612047afaff876066184220a0353e98ff4f4',
					'size' => 282664,
				],
			],
		],
		'daibannasilbook'       => [
			'use_otl'     => 0,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'DBSILBR.ttf',
					'blob' => '658fc362fd9e4d7b454e243ff82aa51e451f4f4e',
					'size' => 83524,
				],
			],
			'languages'   => [
				'und-talu',
			],
		],
		'dejavusans'            => [
			'use_otl'     => 0xFF,
			'use_kashida' => 75,
			'faces'       => [
				'R'  => [
					'name' => 'DejaVuSans.ttf',
					'blob' => '9d40c325694b3bfb8cdd4c0146ad44379d5c4ade',
					'size' => 741536,
				],
				'B'  => [
					'name' => 'DejaVuSans-Bold.ttf',
					'blob' => '08695f23a96350b49a69e270bcf3b9c5f37bd88b',
					'size' => 693876,
				],
				'I'  => [
					'name' => 'DejaVuSans-Oblique.ttf',
					'blob' => 'e33ab144d7a2380b8f8db2d964ad16c243edc30f',
					'size' => 632416,
				],
				'BI' => [
					'name' => 'DejaVuSans-BoldOblique.ttf',
					'blob' => '7e3bcc10571bd0d7444238543726835f31e8da87',
					'size' => 632168,
				],
			],
			'languages'   => [
				'hy',
				'hye',
				'ka',
				'kat',
				'nqo',
				'und-brai',
				'und-ogam',
				'und-tfng',
			],
			'bmp'         => true,
		],
		'dejavusanscondensed'   => [
			'use_otl'     => 0xFF,
			'use_kashida' => 75,
			'faces'       => [
				'R'  => [
					'name' => 'DejaVuSansCondensed.ttf',
					'blob' => '6065234560f179d8ff547d75b67959822891dd7c',
					'size' => 690388,
				],
				'B'  => [
					'name' => 'DejaVuSansCondensed-Bold.ttf',
					'blob' => '753432b5b6edf35b1d9e1847cfd31a75690c08f9',
					'size' => 676456,
				],
				'I'  => [
					'name' => 'DejaVuSansCondensed-Oblique.ttf',
					'blob' => '619b9df55315c36857b2a63865c107437465eaa3',
					'size' => 609268,
				],
				'BI' => [
					'name' => 'DejaVuSansCondensed-BoldOblique.ttf',
					'blob' => 'e39efeb24b572b905b86fdfdbea3b803dc474df8',
					'size' => 621680,
				],
			],
			'languages'   => [
				'ab',
				'abk',
				'av',
				'ava',
				'ba',
				'bak',
				'be',
				'bel',
				'bg',
				'bul',
				'ce',
				'che',
				'chu',
				'chv',
				'cu',
				'cv',
				'el',
				'ell',
				'kaz',
				'kir',
				'kk',
				'kom',
				'kv',
				'ky',
				'mk',
				'mkd',
				'os',
				'oss',
				'ru',
				'rus',
				'sr',
				'srp',
				'tat',
				'tg',
				'tgk',
				'tk',
				'tt',
				'tuk',
				'uk',
				'ukr',
				'und-cyrl',
				'und-latn',
				'vi',
				'vie',
			],
			'backup_subs' => true,
			'bmp'         => true,
		],
		'dejavusansmono'        => [
			'use_otl'     => 0xFF,
			'use_kashida' => 75,
			'faces'       => [
				'R'  => [
					'name' => 'DejaVuSansMono.ttf',
					'blob' => '8b7bb2a4e1b2c27786398c320e9020bcc24c3af3',
					'size' => 335068,
				],
				'B'  => [
					'name' => 'DejaVuSansMono-Bold.ttf',
					'blob' => '9c716794f70b10900beded00f5422deb832f01b1',
					'size' => 318392,
				],
				'I'  => [
					'name' => 'DejaVuSansMono-Oblique.ttf',
					'blob' => 'e1af3413048d379311a219af07deedd47ad329d9',
					'size' => 245948,
				],
				'BI' => [
					'name' => 'DejaVuSansMono-BoldOblique.ttf',
					'blob' => 'd6536a5cc256583c156c6e284348ef066c740f13',
					'size' => 239876,
				],
			],
			'bmp'         => true,
		],
		'dejavuserif'           => [
			'use_otl'     => 0,
			'use_kashida' => 0,
			'faces'       => [
				'R'  => [
					'name' => 'DejaVuSerif.ttf',
					'blob' => '712a59b02d0eb9a1efede635cea8147fb12dab29',
					'size' => 367260,
				],
				'B'  => [
					'name' => 'DejaVuSerif-Bold.ttf',
					'blob' => '3fffea617d04853ed65d7db37aecf781c9bace45',
					'size' => 345364,
				],
				'I'  => [
					'name' => 'DejaVuSerif-Italic.ttf',
					'blob' => '46a718e32a8e3cad5929b5afbf30f405abb7e17f',
					'size' => 343388,
				],
				'BI' => [
					'name' => 'DejaVuSerif-BoldItalic.ttf',
					'blob' => '3a3a9dfe61f0d0d599156badc3624bf66f04d321',
					'size' => 336884,
				],
			],
			'bmp'         => true,
		],
		'dejavuserifcondensed'  => [
			'use_otl'     => 0,
			'use_kashida' => 0,
			'faces'       => [
				'R'  => [
					'name' => 'DejaVuSerifCondensed.ttf',
					'blob' => '3291fc506c9622ed3ebe5f17bffc6e31524e9593',
					'size' => 334040,
				],
				'B'  => [
					'name' => 'DejaVuSerifCondensed-Bold.ttf',
					'blob' => '673c66f0cb7639d8496f528b5586fd0d564486ce',
					'size' => 320720,
				],
				'I'  => [
					'name' => 'DejaVuSerifCondensed-Italic.ttf',
					'blob' => '6f8d4e4052f1d7c5ede390a76fb9615aa02d4f87',
					'size' => 342736,
				],
				'BI' => [
					'name' => 'DejaVuSerifCondensed-BoldItalic.ttf',
					'blob' => '5a3ba1ab7766d0c2b0964e8ea59d376628399c20',
					'size' => 335940,
				],
			],
			'bmp'         => true,
		],
		'dhyana'                => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Dhyana-Regular.ttf',
					'blob' => '9c5dcb4b048823497917a3e80e15929ec5e10f54',
					'size' => 72348,
				],
				'B' => [
					'name' => 'Dhyana-Bold.ttf',
					'blob' => 'b439bdc4ab897ab1a39f4e1989d897b7c0a9ae46',
					'size' => 61304,
				],
			],
			'languages'   => [
				'lao',
				'lo',
			],
		],
		'estrangeloedessa'      => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'SyrCOMEdessa.otf',
					'blob' => '9e1fbda08ea3aec65eb2d0603ba321b77c629dab',
					'size' => 79668,
				],
			],
			'languages'   => [
				'syr',
			],
		],
		'freemono'              => [
			'use_otl'     => 0,
			'use_kashida' => 0,
			'faces'       => [
				'R'  => [
					'name' => 'FreeMono.ttf',
					'blob' => 'ff5cc0e9bc663a3080aaf645c5b4245b42811753',
					'size' => 584424,
				],
				'B'  => [
					'name' => 'FreeMonoBold.ttf',
					'blob' => '48154499cd8f61bb5eca4953da4633e8fc891561',
					'size' => 296284,
				],
				'I'  => [
					'name' => 'FreeMonoOblique.ttf',
					'blob' => '87caa0ff1d26d243eebf647b4c3c15da8ed5c7b8',
					'size' => 390692,
				],
				'BI' => [
					'name' => 'FreeMonoBoldOblique.ttf',
					'blob' => '02692e1a38f7032a3396e3a37c78864c9e04eb4a',
					'size' => 295360,
				],
			],
			'languages'   => [
				'und-kali',
			],
		],
		'freesans'              => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R'  => [
					'name' => 'FreeSans.ttf',
					'blob' => '2072cdaf48552429234e030dc7a03511d369faca',
					'size' => 1563256,
				],
				'B'  => [
					'name' => 'FreeSansBold.ttf',
					'blob' => '6f36dc5feb459ac8778caad176f0e96a0f900608',
					'size' => 416128,
				],
				'I'  => [
					'name' => 'FreeSansOblique.ttf',
					'blob' => '6de107c5940bcc93e3ed1cadc884999246764e9c',
					'size' => 763696,
				],
				'BI' => [
					'name' => 'FreeSansBoldOblique.ttf',
					'blob' => 'c5440c483225ee32d285fae3994c04849919192e',
					'size' => 342488,
				],
			],
			'languages'   => [
				'vai',
			],
			'backup_subs' => true,
		],
		'freeserif'             => [
			'use_otl'     => 0xFF,
			'use_kashida' => 75,
			'faces'       => [
				'R'  => [
					'name' => 'FreeSerif.ttf',
					'blob' => '889c594f6674ead9a3d5fd2f762e12925cfa73f9',
					'size' => 3303588,
				],
				'B'  => [
					'name' => 'FreeSerifBold.ttf',
					'blob' => '49112d3a92c96f744c4107a278584bfbbb15f215',
					'size' => 1310828,
				],
				'I'  => [
					'name' => 'FreeSerifItalic.ttf',
					'blob' => '4ea68896dc3d3c047df07948a905d8f9ea5e426e',
					'size' => 922088,
				],
				'BI' => [
					'name' => 'FreeSerifBoldItalic.ttf',
					'blob' => 'c41a9651aa8af58ca60eca67081328c648e6312e',
					'size' => 608708,
				],
			],
			'languages'   => [
				'as',
				'asm',
				'ben',
				'bh',
				'bih',
				'bn',
				'bug',
				'div',
				'dv',
				'got',
				'gu',
				'guj',
				'hi',
				'hin',
				'kas',
				'ks',
				'mal',
				'mar',
				'ml',
				'mr',
				'ne',
				'nep',
				'or',
				'ori',
				'pa',
				'pan',
				'sa',
				'san',
				'ta',
				'tam',
			],
		],
		'garuda'                => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R'  => [
					'name' => 'Garuda.ttf',
					'blob' => 'b9fcdfd90e111c6d09bd19bb11d5c283c3ff14e3',
					'size' => 57324,
				],
				'B'  => [
					'name' => 'Garuda-Bold.ttf',
					'blob' => 'c2aa87edbfcf7e8373718260bf83e2ea4edb29e4',
					'size' => 57796,
				],
				'I'  => [
					'name' => 'Garuda-Oblique.ttf',
					'blob' => '7d1219907a318a97faabac93a02389600bb02d0e',
					'size' => 57412,
				],
				'BI' => [
					'name' => 'Garuda-BoldOblique.ttf',
					'blob' => '889be702af7776785ac730ce1bd2d3854474ac85',
					'size' => 57460,
				],
			],
			'languages'   => [
				'th',
				'tha',
			],
		],
		'jomolhari'             => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Jomolhari.ttf',
					'blob' => 'ca695b61dd49aecf40dc13a4977da76241e68fca',
					'size' => 2269108,
				],
			],
			'languages'   => [
				'bo',
				'bod',
				'dz',
				'dzo',
			],
		],
		'kaputaunicode'         => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'kaputaunicode.ttf',
					'blob' => 'fe0269726810dd7dc32844f16c10ffcee58a7af3',
					'size' => 159696,
				],
			],
			'languages'   => [
				'si',
				'sin',
			],
		],
		'kfgqpcuthmantahanaskh' => [
			'use_otl'     => 0xFF,
			'use_kashida' => 75,
			'faces'       => [
				'R' => [
					'name' => 'Uthman.otf',
					'blob' => 'bb650a24edd6aded9dc9b6c6fc7191f5d7875f67',
					'size' => 172980,
				],
			],
		],
		'khmeros'               => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'KhmerOS.ttf',
					'blob' => '7aa5bdcd5b743ac61dc407bc5f4b2fdf91a3d26a',
					'size' => 265988,
				],
			],
			'languages'   => [
				'khm',
				'km',
			],
		],
		'lannaalif'             => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'lannaalif-v1-03.ttf',
					'blob' => '7c20d97910fc8867e48dcebdc8abbc10ebb91913',
					'size' => 136648,
				],
			],
			'languages'   => [
				'und-lana',
			],
		],
		'lateef'                => [
			'use_otl'     => 0xFF,
			'use_kashida' => 75,
			'faces'       => [
				'R' => [
					'name' => 'LateefRegOT.ttf',
					'blob' => 'f89d32be304d945a1bc3bad37b56f8b659e2f6d4',
					'size' => 246224,
				],
			],
			'languages'   => [
				'sd',
				'snd',
			],
		],
		'lohitkannada'          => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Lohit-Kannada.ttf',
					'blob' => '2911ce7b67cc5511ff6997ea010b800f4c173e5c',
					'size' => 197872,
				],
			],
			'languages'   => [
				'kan',
				'kn',
			],
		],
		'mph2bdamase'           => [
			'use_otl'     => 0,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'damase_v.2.ttf',
					'blob' => 'a60240babc5d01151f46706b01afe5ad933ec772',
					'size' => 900260,
				],
			],
			'languages'   => [
				'syl',
				'und-dsrt',
				'und-glag',
				'und-khar',
				'und-osma',
				'und-shaw',
			],
			'aliases'     => [
				'damase',
			],
		],
		'ocrb'                  => [
			'use_otl'     => 0,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'ocrb10.ttf',
					'blob' => '34980b80c2b23bd2afdfe3c74cd40e1f933d523c',
					'size' => 23112,
				],
			],
			'aliases'     => [
				'ocr-b',
				'ocr-b10bt',
			],
		],
		'padaukbook'            => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Padauk-book.ttf',
					'blob' => '590cdadd40a3832e5769398ac3e5d1831d972ddd',
					'size' => 476528,
				],
			],
		],
		'pothana2000'           => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Pothana2000.ttf',
					'blob' => '296d7d665c77d2e8a2c7c48a5b42b029c3bbb8bf',
					'size' => 194268,
				],
			],
			'languages'   => [
				'te',
				'tel',
			],
		],
		'quivira'               => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Quivira.otf',
					'blob' => '531edf16cbad57f67d9033ea9d112110c21bfce8',
					'size' => 1475236,
				],
			],
			'languages'   => [
				'bku',
				'cop',
				'hnn',
				'lis',
				'tbw',
				'tl',
			],
		],
		'sun-exta'              => [
			'use_otl'     => 0,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Sun-ExtA.ttf',
					'blob' => '1c9c2cfa56b0c03ec1c95becb55a0986d5f974a6',
					'size' => 22993540,
				],
			],
			'languages'   => [
				'ii',
				'iii',
				'ja',
				'jpn',
				'lif',
				'und-bopo',
				'und-hans',
				'und-runr',
				'und-yiii',
				'zh',
				'zho',
			],
			'backup_subs' => true,
			'sip_ext'     => 'sun-extb',
		],
		'sun-extb'              => [
			'use_otl'     => 0,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Sun-ExtB.ttf',
					'blob' => '8a53b20c5f7af2650e17f39c343fa80bf813fc24',
					'size' => 17632200,
				],
			],
		],
		'sundaneseunicode'      => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'SundaneseUnicode-1.0.5.ttf',
					'blob' => 'a86b47578909b716a381553cda3e764138834151',
					'size' => 54000,
				],
			],
			'languages'   => [
				'su',
			],
		],
		'taameydavidclm'        => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'TaameyDavidCLM-Medium.ttf',
					'blob' => '2aed6f85e2395c837ab20a4768fb1e2f6d8f2b51',
					'size' => 96284,
				],
			],
			'languages'   => [
				'he',
				'heb',
				'yi',
				'yid',
			],
		],
		'taiheritagepro'        => [
			'use_otl'     => 0,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'TaiHeritagePro.ttf',
					'blob' => 'd4ed9538bb43da0acc23cb2f753cd587ba9b0f57',
					'size' => 210336,
				],
			],
			'languages'   => [
				'blt',
			],
		],
		'tharlon'               => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'Tharlon-Regular.ttf',
					'blob' => '4717d70cdc77d666b31a75474a9bf61598fa1c75',
					'size' => 353228,
				],
			],
			'languages'   => [
				'my',
				'mya',
				'tdd',
			],
		],
		'unbatang'              => [
			'use_otl'     => 0,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'UnBatang_0613.ttf',
					'blob' => '2e93d5f86b360e013588bcb8a5df1a72614aa2d6',
					'size' => 6937228,
				],
			],
			'languages'   => [
				'ko',
				'kor',
			],
		],
		'xbriyaz'               => [
			'use_otl'     => 0xFF,
			'use_kashida' => 75,
			'faces'       => [
				'R'  => [
					'name' => 'XB Riyaz.ttf',
					'blob' => '80e15803dc0aa7b0d8758e0e26793d10fa484ef1',
					'size' => 1144764,
				],
				'B'  => [
					'name' => 'XB RiyazBd.ttf',
					'blob' => 'e6cf58f93bb2d2093a9b858ab74393416537c46b',
					'size' => 1159192,
				],
				'I'  => [
					'name' => 'XB RiyazIt.ttf',
					'blob' => 'cc9cc3ab2a7da6ee26b3d78b18f2c18de42474a4',
					'size' => 1131668,
				],
				'BI' => [
					'name' => 'XB RiyazBdIt.ttf',
					'blob' => '19fba7887e965c9f331479862c53ec97f4caf22e',
					'size' => 1159500,
				],
			],
			'languages'   => [
				'ar',
				'ara',
				'fa',
				'fas',
				'ku',
				'kur',
				'ps',
				'pus',
				'und-arab',
				'ur',
				'urd',
			],
		],
		'zawgyi-one'            => [
			'use_otl'     => 0xFF,
			'use_kashida' => 0,
			'faces'       => [
				'R' => [
					'name' => 'ZawgyiOne.ttf',
					'blob' => '5731471586f70ec4e2182d5fd24a2058741b96a4',
					'size' => 341264,
				],
			],
		],
	];

	/**
	 * Every filename the installer could have written
	 *
	 * @return array<string, true> Keyed by filename, so callers can merge it into a claimed-file set
	 *
	 * @since 7.0
	 */
	public static function filenames(): array {
		$filenames = [];

		foreach ( static::FAMILIES as $family ) {
			foreach ( $family['faces'] as $face ) {
				$filenames[ $face['name'] ] = true;
			}
		}

		return $filenames;
	}

	/**
	 * Whether the file on disk is the one this map describes
	 *
	 * Size is checked first: it rules out a truncated or replaced file without hashing several megabytes.
	 *
	 * @param array{name: string, blob: string, size: int} $face
	 * @param string                                       $path Absolute path to the file
	 *
	 * @since 7.0
	 */
	public static function verify( array $face, string $path ): bool {
		if ( ! is_file( $path ) ) {
			return false;
		}

		$size = filesize( $path );

		if ( $size !== $face['size'] ) {
			return false;
		}

		/* Streamed rather than read whole: the largest of these files is 12 MB and this runs during an upgrade */
		$hash = hash_init( 'sha1' );
		hash_update( $hash, 'blob ' . $size . "\0" );
		hash_update_file( $hash, $path );

		return hash_equals( $face['blob'], hash_final( $hash ) );
	}
}
