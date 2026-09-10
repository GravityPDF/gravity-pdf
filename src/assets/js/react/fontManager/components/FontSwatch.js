/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { useFontFace } from '../hooks/useFontFace';
import { EmojiGlyph } from './EmojiGlyph';

/**
 * The 44px "Aa" beside every row
 *
 * A sourced row renders in the catalogue's small preview subset; a custom or imported row has no preview face
 * to render in and stays in the UI font. Either way the swatch never loads a local TTF: that is the rule the
 * §4.6 preview budget rests on, and this is the component that would break it.
 *
 * A font with no Latin draws something it can actually render: the glyph comes from the row's own scripts
 * rather than from a list of entry ids, so a pack from any source picks its own.
 *
 * @param {Object}  props
 * @param {string}  props.id      The font key, which names the registered face
 * @param {?string} props.preview The catalogue's preview URL, when the row has one
 * @param {?string} props.scripts The row's script tags, when it has them
 *
 * @return {JSX.Element} The swatch
 *
 * @since 7.0
 */
export default function FontSwatch({ id, preview = null, scripts = '' }) {
	const { ref, fontFamily } = useFontFace(
		`gfpdf-preview-${id}`,
		[{ url: preview, weight: 400, style: 'normal' }],
		{ observe: true }
	);

	return (
		<div
			className="font-swatch"
			ref={ref}
			style={fontFamily ? { fontFamily } : undefined}
			aria-hidden="true"
		>
			{(scripts ?? '').split(',').includes(EMOJI_SCRIPT) ? (
				<EmojiGlyph />
			) : (
				'Aa'
			)}
		</div>
	);
}

/**
 * The script tag an emoji font answers for
 *
 * @since 7.0
 */
export const EMOJI_SCRIPT = 'und-Zsye';
