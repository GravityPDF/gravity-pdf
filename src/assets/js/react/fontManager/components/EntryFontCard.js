/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __, _n, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { check } from '@wordpress/icons';
import TemplateUse from './TemplateUse';

/**
 * One font inside a language pack
 *
 * A pack is several families under one entry, and each of them is separately selectable and separately
 * reachable from a template — which is why the key sits on the card rather than in a paragraph explaining that
 * pack fonts have keys too.
 *
 * @param {Object}   props
 * @param {Object}   props.row
 * @param {boolean}  props.active
 * @param {boolean}  props.canSetActive
 * @param {Function} props.onSetActive
 *
 * @return {JSX.Element} The card
 *
 * @since 7.0
 */
export default function EntryFontCard({
	row,
	active,
	canSetActive,
	onSetActive,
}) {
	const count = Object.keys(row.files ?? {}).length;

	return (
		<div className="gfpdf-fm-font-card">
			<div className="gfpdf-fm-font-card-meta">
				<div className="gfpdf-fm-font-card-name">{row.label}</div>
				<div className="gfpdf-fm-font-card-files">
					{sprintf(
						/* translators: %d: how many face files this font installed */
						_n('%d file', '%d files', count, 'gravity-pdf'),
						count
					)}
				</div>
				<TemplateUse fontKey={row.id} />
			</div>

			{active ? (
				<span className="active-indicator">
					{__('Active font', 'gravity-pdf')}
				</span>
			) : (
				canSetActive && (
					<Button
						variant="secondary"
						size="small"
						icon={check}
						onClick={() => onSetActive(row.id)}
					>
						{__('Set as active', 'gravity-pdf')}
					</Button>
				)
			)}
		</div>
	);
}
