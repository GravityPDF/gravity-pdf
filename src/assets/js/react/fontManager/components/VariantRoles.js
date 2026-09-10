/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import { ROLES } from '../constants';
import { parseStyles } from '../utils/variants';

/**
 * Which of a family's weights fills each of mPDF's four roles
 *
 * A display family often ships nine weights and mPDF has four slots, so somebody has to choose. The labels,
 * weights and the Upright/Italic grouping are derived from the style ids the catalogue row already carries —
 * no request, and nothing for the server to translate.
 *
 * @param {Object}   props
 * @param {?string}  props.styles   The row's `styles` CSV
 * @param {Object}   props.value    Role → style id
 * @param {Function} props.onChange
 *
 * @return {?JSX.Element} The four selects, or null for an entry that offers no choice
 *
 * @since 7.0
 */
export default function VariantRoles({ styles, value, onChange }) {
	const parsed = parseStyles(styles);

	if (parsed.length === 0) {
		return null;
	}

	const groups = [__('Upright', 'gravity-pdf'), __('Italic', 'gravity-pdf')];

	/* `SelectControl` only renders a flat `options` list, and these have to be grouped */
	const choices = (
		<>
			<option value="">{__('None', 'gravity-pdf')}</option>
			{groups.map((group) => {
				const members = parsed.filter((style) => style.group === group);

				if (members.length === 0) {
					return null;
				}

				return (
					<optgroup label={group} key={group}>
						{members.map((style) => (
							<option value={style.id} key={style.id}>
								{style.label}
							</option>
						))}
					</optgroup>
				);
			})}
		</>
	);

	return (
		<div className="gfpdf-fm-roles">
			{ROLES.map((role) => (
				<SelectControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					key={role.id}
					label={role.label}
					value={value[role.id] ?? ''}
					onChange={(style) =>
						onChange({ ...value, [role.id]: style })
					}
				>
					{choices}
				</SelectControl>
			))}
		</div>
	);
}
