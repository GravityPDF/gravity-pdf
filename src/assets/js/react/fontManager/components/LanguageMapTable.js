/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import { Button, SelectControl } from '@wordpress/components';
import { memo, useMemo } from '@wordpress/element';
import { rotateLeft, trash } from '@wordpress/icons';

/**
 * The effective language map, one row per code
 *
 * ~80 rows of selects is enough that a naive filter re-renders visibly, so the rows are memoised on their code
 * and the filter toggles `hidden` rather than unmounting them. The table scrolls inside itself, so the filter
 * and Save stay in reach however far down the list an admin is.
 *
 * @param {Object}        props
 * @param {Array<Object>} props.groups   The map, grouped as `GET /fonts/settings` sent it
 * @param {Array<Object>} props.fonts    Every installed row, for the font select
 * @param {string}        props.filter   The client-side filter term
 * @param {Function}      props.onChange Called with `(code, font)`
 * @param {Function}      props.onRemove Called with `code`, for an override-only row
 *
 * @return {JSX.Element} The table
 *
 * @since 7.0
 */
export default function LanguageMapTable({
	groups,
	fonts,
	filter,
	onChange,
	onRemove,
}) {
	/* Rebuilt per render, the option list would defeat the row memo and re-render ~80 selects per keystroke */
	const options = useMemo(
		() => [
			{
				label: __('Document font (leave as is)', 'gravity-pdf'),
				value: '*',
			},
			...fonts.map((font) => ({
				label: font.label,
				value: font.id,
			})),
		],
		[fonts]
	);

	const term = filter.trim().toLowerCase();

	return (
		<div className="gfpdf-fm-language-table">
			{groups.map((group) => (
				<div className="gfpdf-fm-language-group" key={group.group}>
					<h4 className="gfpdf-fm-language-heading">{group.label}</h4>

					{group.rows.map((row) => (
						<LanguageRow
							key={row.code}
							row={row}
							options={options}
							hidden={
								!!term &&
								!row.label.toLowerCase().includes(term) &&
								!row.code.toLowerCase().includes(term)
							}
							onChange={onChange}
							onRemove={group.group === 'other' ? onRemove : null}
						/>
					))}
				</div>
			))}
		</div>
	);
}

const LanguageRow = memo(function LanguageRow({
	row,
	options,
	hidden,
	onChange,
	onRemove,
}) {
	return (
		<div className="gfpdf-fm-language-row" hidden={hidden}>
			<span className="gfpdf-fm-language-label">
				{row.label}
				<code>{row.code}</code>
			</span>

			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={row.label}
				hideLabelFromVision
				value={row.font}
				options={options}
				onChange={(font) => onChange(row.code, font)}
			/>

			{row.font !== row.default_font && (
				<Button
					icon={rotateLeft}
					size="small"
					label={__('Reset to the default', 'gravity-pdf')}
					onClick={() => onChange(row.code, row.default_font)}
				/>
			)}

			{onRemove && (
				<Button
					icon={trash}
					size="small"
					label={__('Remove this language', 'gravity-pdf')}
					onClick={() => onRemove(row.code)}
				/>
			)}
		</div>
	);
});
