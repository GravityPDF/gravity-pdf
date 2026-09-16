/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

const SCRIPTS = {
	'und-Arab': 'Arabic',
	'und-Armn': 'Armenian',
	'und-Beng': 'Bengali',
	'und-Cans': 'Canadian Aboriginal',
	'und-Copt': 'Coptic',
	'und-Cyrl': 'Cyrillic',
	'und-Deva': 'Devanagari',
	'und-Egyp': 'Egyptian Hieroglyphs',
	'und-Ethi': 'Ethiopic',
	'und-Geor': 'Georgian',
	'und-Goth': 'Gothic',
	'und-Grek': 'Greek',
	'und-Gujr': 'Gujarati',
	'und-Guru': 'Gurmukhi',
	'und-Hang': 'Hangul',
	'und-Hani': 'Han',
	'und-Hans': 'Han (Simplified)',
	'und-Hant': 'Han (Traditional)',
	'und-Hebr': 'Hebrew',
	'und-Hira': 'Hiragana',
	'und-Jpan': 'Japanese',
	'und-Kana': 'Katakana',
	'und-Khmr': 'Khmer',
	'und-Knda': 'Kannada',
	'und-Kore': 'Korean',
	'und-Laoo': 'Lao',
	'und-Latn': 'Latin',
	'und-Linb': 'Linear B',
	'und-Mlym': 'Malayalam',
	'und-Mymr': 'Myanmar',
	'und-Orya': 'Odia',
	'und-Sinh': 'Sinhala',
	'und-Sund': 'Sundanese',
	'und-Syrc': 'Syriac',
	'und-Taml': 'Tamil',
	'und-Tavt': 'Tai Viet',
	'und-Telu': 'Telugu',
	'und-Thai': 'Thai',
	'und-Tibt': 'Tibetan',
	'und-Xsux': 'Cuneiform',
	'und-Zsye': 'Emoji',
};

/**
 * The scripts a coverage entry answers for
 *
 * The names are the whole point of the chips: `und-Mymr` tells nobody anything, and the catalogue row carries
 * the tag rather than a label because the tag is what mPDF is keyed on.
 *
 * @param {Object}  props
 * @param {?string} props.scripts The CSV the catalogue row carries
 * @param {number}  props.limit   How many to show before "+N more"
 *
 * @return {?JSX.Element} The chips, or null when the entry claims no scripts
 *
 * @since 7.0
 */
export default function ScriptChips({ scripts, limit = 0 }) {
	const tags = (scripts ?? '').split(',').filter(Boolean);

	if (tags.length === 0) {
		return null;
	}

	const shown = limit ? tags.slice(0, limit) : tags;

	return (
		<span className="gfpdf-fm-chips">
			{shown.map((tag) => (
				<span className="gfpdf-fm-chip" key={tag}>
					{SCRIPTS[tag] ?? tag}
				</span>
			))}
			{shown.length < tags.length && (
				<span className="gfpdf-fm-chip gfpdf-fm-chip-more">
					{`+${tags.length - shown.length}`}
				</span>
			)}
		</span>
	);
}

export { SCRIPTS };
