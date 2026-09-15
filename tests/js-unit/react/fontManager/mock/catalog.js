/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

/**
 * Catalogue fixtures for the mocked REST layer
 *
 * The rows are the real 7.0 pack split and a slice of the Google index, so the browser's filters, pager and
 * install forms are exercised against the shapes the routes will return. Sizes and file counts are the plan's;
 * hashes, preview files and licence text are not modelled, because nothing in the UI reads them.
 *
 * @since 7.0
 */

const packEntry = (position, entry, label, fonts, size, files, extra = {}) => ({
	source: 'packs',
	entry,
	label,
	version: '1.0.0',
	notes: '',
	released: '2026-08-01',
	coverage: 1,
	position,
	license: 'OFL-1.1',
	size,
	files,
	category: null,
	subsets: null,
	preview: null,
	preview_text: null,
	preview_url: null,
	styles: null,
	always: 0,
	scripts: '',
	languages: '',
	font_keys: fonts.join(','),
	...extra,
});

/**
 * The twenty language packs, in the order the pipeline publishes them (the `position` column)
 *
 * @since 7.0
 */
export const PACK_ENTRIES = [
	packEntry(0, 'emoji', 'Emoji', ['notoemoji'], 943718, 2, {
		always: 1,
		scripts: 'und-Zsye',
	}),
	packEntry(
		1,
		'serif-mono',
		'Serif & Monospace',
		['tinos', 'cousine'],
		2831155,
		9
	),
	packEntry(
		2,
		'popular-sans',
		'Popular Sans Serif',
		['roboto', 'open-sans', 'lato', 'montserrat'],
		5265448,
		17
	),
	packEntry(
		3,
		'popular-serif',
		'Popular Serif',
		['lora', 'merriweather', 'playfair-display'],
		5563248,
		13
	),
	packEntry(
		4,
		'popular-mono',
		'Popular Monospace',
		['roboto-mono', 'jetbrains-mono', 'inconsolata'],
		1043612,
		11
	),
	packEntry(
		5,
		'popular-cursive',
		'Popular Cursive',
		[
			'dancing-script',
			'pacifico',
			'caveat',
			'great-vibes',
			'homemade-apple',
			'permanent-marker',
			'rock-salt',
		],
		1772820,
		11
	),
	packEntry(
		6,
		'dejavu',
		'Extended Latin',
		[
			'dejavusanscondensed',
			'dejavusans',
			'dejavuserif',
			'dejavuserifcondensed',
			'dejavusansmono',
		],
		9163404,
		21,
		{
			scripts: 'und-Latn,und-Grek,und-Cyrl,und-Armn,und-Geor',
			languages: 'hy,ka,ru,uk,bg,sr,el',
		}
	),
	packEntry(
		7,
		'chinese-simplified',
		'Chinese (Simplified)',
		['notosanssc'],
		10595932,
		3,
		{ scripts: 'und-Hans,und-Hani', languages: 'zh,zh-cn' }
	),
	packEntry(8, 'japanese', 'Japanese', ['notosansjp'], 5766884, 3, {
		scripts: 'und-Jpan,und-Kana,und-Hira',
		languages: 'ja',
	}),
	packEntry(
		9,
		'chinese-traditional',
		'Chinese (Traditional)',
		['notosanstc'],
		7149180,
		3,
		{ scripts: 'und-Hant', languages: 'zh-tw,zh-hk' }
	),
	packEntry(10, 'cjk-ext-b', 'CJK Extension B', ['sun-extb'], 17632200, 2),
	packEntry(
		11,
		'indic',
		'Indic scripts',
		[
			'freesans',
			'freeserif',
			'freemono',
			'lohitkannada',
			'pothana2000',
			'notosanssinhala',
			'jomolhari',
			'eeyekunicode',
		],
		13545688,
		26,
		{
			scripts:
				'und-Deva,und-Beng,und-Gujr,und-Guru,und-Orya,und-Taml,und-Mlym,und-Knda,und-Telu,und-Sinh,und-Tibt',
			languages: 'hi,bn,gu,pa,or,ta,ml,kn,te,si,bo,dz',
		}
	),
	packEntry(
		12,
		'arabic',
		'Arabic',
		['xbriyaz', 'lateef', 'kfgqpcuthmantahanaskh'],
		4841348,
		10,
		{ scripts: 'und-Arab', languages: 'ar,fa,ps,ur,ku,sd' }
	),
	packEntry(13, 'korean', 'Korean', ['notosanskr'], 12447580, 4, {
		scripts: 'und-Hang,und-Kore',
		languages: 'ko',
	}),
	packEntry(14, 'african', 'African scripts', ['abyssinicasil'], 629145, 3, {
		scripts: 'und-Ethi',
		languages: 'am,ti',
	}),
	packEntry(
		15,
		'hebrew-syriac',
		'Hebrew & Syriac',
		['taameydavidclm', 'estrangeloedessa'],
		209715,
		4,
		{ scripts: 'und-Hebr,und-Syrc', languages: 'he,yi,syr' }
	),
	packEntry(
		16,
		'southeast-asian',
		'Southeast Asian',
		[
			'garuda',
			'khmeros',
			'dhyana',
			'tharlon',
			'padaukbook',
			'notosansmyanmar',
			'ayar',
			'taiheritagepro',
			'daibannasilbook',
			'notosanstaitham',
			'notosanssundanese',
		],
		4606127,
		31,
		{
			scripts: 'und-Thai,und-Khmr,und-Laoo,und-Mymr,und-Tavt,und-Sund',
			languages: 'th,km,lo,my',
		}
	),
	packEntry(
		17,
		'americas',
		'Americas',
		['notosanscanadianaboriginal'],
		188544,
		3,
		{ scripts: 'und-Cans', languages: 'iu,cr' }
	),
	packEntry(
		18,
		'ancient-scripts',
		'Ancient scripts',
		['aegean', 'aegyptus', 'akkadian', 'quivira', 'mph2bdamase'],
		12163481,
		11,
		{ scripts: 'und-Linb,und-Egyp,und-Xsux,und-Copt,und-Goth' }
	),
	packEntry(19, 'barcode', 'Barcode', ['ocrb'], 20971, 2),
];

const googleEntry = (
	position,
	entry,
	label,
	category,
	subsets,
	styles,
	size,
	files
) => ({
	source: 'google',
	entry,
	label,
	version: 'v30',
	notes: '',
	released: '2026-07-14',
	coverage: 0,
	position,
	license: 'OFL-1.1',
	size,
	files,
	category,
	subsets: subsets.join(','),
	preview: null,
	preview_text: 'The quick brown fox',
	preview_url: null,
	styles: styles.join(','),
	always: 0,
	scripts: '',
	languages: '',
	font_keys: entry.replace(/-/g, ''),
});

const LATIN = ['latin', 'latin-ext'];
const FOUR = ['regular', 'italic', '700', '700italic'];
const TWO = ['regular', '700'];

/**
 * A slice of the Google index, in Google's popularity order
 *
 * Enough rows and enough spread of category and subset for the browser's filters, its pager and the role
 * selects to have something to say.
 *
 * @since 7.0
 */
export const GOOGLE_ENTRIES = [
	googleEntry(
		0,
		'roboto',
		'Roboto',
		'sans-serif',
		[...LATIN, 'cyrillic', 'greek', 'vietnamese'],
		['300', 'regular', '500', '700', 'italic', '700italic'],
		650532,
		6
	),
	googleEntry(
		1,
		'open-sans',
		'Open Sans',
		'sans-serif',
		[...LATIN, 'cyrillic', 'greek', 'hebrew'],
		FOUR,
		534656,
		4
	),
	googleEntry(
		2,
		'lato',
		'Lato',
		'sans-serif',
		LATIN,
		['300', 'regular', '700', '900', 'italic', '700italic'],
		2734376,
		6
	),
	googleEntry(
		3,
		'montserrat',
		'Montserrat',
		'sans-serif',
		[...LATIN, 'cyrillic', 'vietnamese'],
		FOUR,
		1345884,
		4
	),
	googleEntry(
		4,
		'poppins',
		'Poppins',
		'sans-serif',
		[...LATIN, 'devanagari'],
		FOUR,
		812004,
		4
	),
	googleEntry(
		5,
		'inter',
		'Inter',
		'sans-serif',
		[...LATIN, 'cyrillic', 'greek', 'vietnamese'],
		FOUR,
		918232,
		4
	),
	googleEntry(
		6,
		'oswald',
		'Oswald',
		'sans-serif',
		[...LATIN, 'cyrillic', 'vietnamese'],
		TWO,
		402188,
		2
	),
	googleEntry(
		7,
		'raleway',
		'Raleway',
		'sans-serif',
		[...LATIN, 'cyrillic', 'vietnamese'],
		FOUR,
		764112,
		4
	),
	googleEntry(
		8,
		'nunito',
		'Nunito',
		'sans-serif',
		[...LATIN, 'cyrillic', 'vietnamese'],
		FOUR,
		690112,
		4
	),
	googleEntry(
		9,
		'noto-sans',
		'Noto Sans',
		'sans-serif',
		[...LATIN, 'cyrillic', 'greek', 'devanagari'],
		FOUR,
		1104220,
		4
	),
	googleEntry(
		10,
		'merriweather',
		'Merriweather',
		'serif',
		[...LATIN, 'cyrillic'],
		FOUR,
		4272364,
		4
	),
	googleEntry(
		11,
		'playfair-display',
		'Playfair Display',
		'serif',
		[...LATIN, 'cyrillic', 'vietnamese'],
		FOUR,
		743508,
		4
	),
	googleEntry(
		12,
		'lora',
		'Lora',
		'serif',
		[...LATIN, 'cyrillic', 'vietnamese'],
		FOUR,
		547376,
		4
	),
	googleEntry(
		13,
		'pt-serif',
		'PT Serif',
		'serif',
		[...LATIN, 'cyrillic'],
		FOUR,
		1410048,
		4
	),
	googleEntry(
		14,
		'crimson-text',
		'Crimson Text',
		'serif',
		LATIN,
		FOUR,
		398112,
		4
	),
	googleEntry(
		15,
		'eb-garamond',
		'EB Garamond',
		'serif',
		[...LATIN, 'cyrillic', 'greek', 'vietnamese'],
		FOUR,
		934420,
		4
	),
	googleEntry(
		16,
		'roboto-mono',
		'Roboto Mono',
		'monospace',
		[...LATIN, 'cyrillic', 'greek', 'vietnamese'],
		FOUR,
		365172,
		4
	),
	googleEntry(
		17,
		'jetbrains-mono',
		'JetBrains Mono',
		'monospace',
		[...LATIN, 'cyrillic', 'greek'],
		FOUR,
		465592,
		4
	),
	googleEntry(
		18,
		'inconsolata',
		'Inconsolata',
		'monospace',
		LATIN,
		TWO,
		212848,
		2
	),
	googleEntry(
		19,
		'source-code-pro',
		'Source Code Pro',
		'monospace',
		[...LATIN, 'cyrillic', 'greek'],
		FOUR,
		588448,
		4
	),
	googleEntry(
		20,
		'dancing-script',
		'Dancing Script',
		'handwriting',
		[...LATIN, 'vietnamese'],
		TWO,
		162560,
		2
	),
	googleEntry(
		21,
		'caveat',
		'Caveat',
		'handwriting',
		[...LATIN, 'cyrillic'],
		TWO,
		514284,
		2
	),
	googleEntry(
		22,
		'pacifico',
		'Pacifico',
		'handwriting',
		[...LATIN, 'cyrillic', 'vietnamese'],
		['regular'],
		329380,
		1
	),
	googleEntry(
		23,
		'great-vibes',
		'Great Vibes',
		'handwriting',
		[...LATIN, 'cyrillic', 'vietnamese'],
		['regular'],
		457588,
		1
	),
	googleEntry(
		24,
		'bebas-neue',
		'Bebas Neue',
		'display',
		LATIN,
		['regular'],
		88104,
		1
	),
	googleEntry(
		25,
		'abril-fatface',
		'Abril Fatface',
		'display',
		LATIN,
		['regular'],
		175612,
		1
	),
	googleEntry(
		26,
		'lobster',
		'Lobster',
		'display',
		[...LATIN, 'cyrillic', 'vietnamese'],
		['regular'],
		148516,
		1
	),
	googleEntry(
		27,
		'amiri',
		'Amiri',
		'serif',
		[...LATIN, 'arabic'],
		FOUR,
		1187416,
		4
	),
	googleEntry(
		28,
		'cairo',
		'Cairo',
		'sans-serif',
		[...LATIN, 'arabic'],
		TWO,
		706256,
		2
	),
	googleEntry(
		29,
		'noto-sans-hebrew',
		'Noto Sans Hebrew',
		'sans-serif',
		[...LATIN, 'hebrew'],
		TWO,
		141928,
		2
	),
];

/**
 * The registered sources, as `Font_Sources` would report them
 *
 * @since 7.0
 */
export const SOURCE_RECORDS = [
	{
		id: 'packs',
		label: 'Language packs',
		description:
			'Font packs maintained by Gravity PDF, each covering the scripts of one language or region. Files are downloaded from fonts.gravitypdf.com and verified against the signed index before they are installed.',
		entries: PACK_ENTRIES,
	},
	{
		id: 'google',
		label: 'Google Fonts',
		description:
			'The Google Fonts library, mirrored by Gravity PDF: choose a family and the weights you want, and only those files are downloaded. No request ever leaves your site for Google, and every file is verified against the signed index before it is installed.',
		entries: GOOGLE_ENTRIES,
	},
];
