/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import FontSwatch from './FontSwatch';

/**
 * One row of the sidebar list
 *
 * @param {Object}   props
 * @param {string}   props.id       The font key or `source/entry` the row stands for
 * @param {string}   props.label
 * @param {string}   props.sub      The line under the name
 * @param {?string}  props.preview  The catalogue preview URL, when the row has one
 * @param {boolean}  props.selected Whether the detail pane is showing it
 * @param {boolean}  props.active   Whether it is the font the underlying select holds
 * @param {boolean}  props.dimmed   Hidden on this site (multisite)
 * @param {?string}  props.scripts  The row's script tags, which choose its glyph
 * @param {Function} props.onSelect
 *
 * @return {JSX.Element} The row
 *
 * @since 7.0
 */
export default function FontRow({
	id,
	label,
	sub,
	preview = null,
	selected = false,
	active = false,
	dimmed = false,
	scripts = '',
	onSelect,
}) {
	const classes = [
		'font-row',
		selected ? 'selected' : '',
		active ? 'is-active' : '',
		dimmed ? 'is-dimmed' : '',
	]
		.filter(Boolean)
		.join(' ');

	return (
		<div
			className={classes}
			role="button"
			tabIndex={0}
			aria-current={selected}
			onClick={onSelect}
			onKeyDown={(event) => {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					onSelect();
				}
			}}
		>
			{active && (
				<span className="font-row-active-bar" aria-hidden="true" />
			)}

			<FontSwatch id={id} preview={preview} scripts={scripts} />

			<div className="font-meta">
				<div className="font-name-row">
					<div className="font-name">{label}</div>
					{active && (
						<span className="font-active-tag">
							{__('Active', 'gravity-pdf')}
						</span>
					)}
				</div>
				<div className="font-sub">
					<span className="font-variants">{sub}</span>
				</div>
			</div>
		</div>
	);
}
