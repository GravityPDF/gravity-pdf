/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

/**
 * A centred pane with a sentence and a way forward
 *
 * The three states that use it — nothing selected, the font is gone, the catalogue was never downloaded —
 * differ only in what they say and what the button does, so they are prop sets rather than components. It
 * renders its own `fm-detail` section, because the alternative was two of the three callers remembering to.
 *
 * @param {Object}  props
 * @param {?string} props.glyph    A large glyph above the text
 * @param {string}  props.text
 * @param {boolean} props.plain    Render without the surrounding detail section
 * @param {*}       props.children The notice and the button
 *
 * @return {JSX.Element} The pane
 *
 * @since 7.0
 */
export default function EmptyState({
	glyph = '',
	text,
	plain = false,
	children,
}) {
	const body = (
		<div className="fm-empty-detail">
			{glyph && (
				<div className="fm-empty-glyph" aria-hidden="true">
					{glyph}
				</div>
			)}
			<p className="fm-empty-text-lg">{text}</p>
			{children}
		</div>
	);

	return plain ? body : <section className="fm-detail">{body}</section>;
}
