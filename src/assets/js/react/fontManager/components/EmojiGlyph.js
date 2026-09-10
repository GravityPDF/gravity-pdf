/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

/**
 * A monochrome emoji glyph, drawn rather than typed
 *
 * The platform's colour emoji is not what the PDF will contain, and showing it beside a pack that renders Noto
 * Emoji's outlines would be a promise the renderer does not keep.
 *
 * @return {JSX.Element} The glyph
 *
 * @since 7.0
 */
export const EmojiGlyph = () => (
	<svg
		viewBox="0 0 24 24"
		width="22"
		height="22"
		fill="none"
		stroke="currentColor"
		strokeWidth="1.4"
		aria-hidden="true"
	>
		<circle cx="12" cy="12" r="9" />
		<path d="M8.5 14.5a4.5 4.5 0 0 0 7 0" strokeLinecap="round" />
		<circle cx="9" cy="10" r="1" fill="currentColor" stroke="none" />
		<circle cx="15" cy="10" r="1" fill="currentColor" stroke="none" />
	</svg>
);

export default EmojiGlyph;
