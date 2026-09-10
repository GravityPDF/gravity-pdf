/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { check, chevronLeft } from '@wordpress/icons';

/**
 * The title row every detail pane opens with
 *
 * Carries the mobile back link, because on a phone the two panes are one and this is the only way out of the
 * detail view, and the Active control, which is hidden outright when the Font Manager was mounted with no
 * associated font select (the Tools tab) — there is nothing there for a font to be active *in*.
 *
 * @param {Object}    props
 * @param {string}    props.title
 * @param {?string}   props.eyebrow      A line above the title: the source, the licence, the key
 * @param {Function}  props.onBack
 * @param {boolean}   props.active
 * @param {boolean}   props.canSetActive
 * @param {?Function} props.onSetActive
 *
 * @return {JSX.Element} The header
 *
 * @since 7.0
 */
export default function DetailHeader({
	title,
	eyebrow = '',
	onBack,
	active = false,
	canSetActive = false,
	onSetActive = null,
}) {
	return (
		<div className="fm-detail-header">
			<Button
				className="fm-mobile-back"
				icon={chevronLeft}
				onClick={onBack}
			>
				{__('Fonts', 'gravity-pdf')}
			</Button>

			<div className="fm-detail-heading">
				{eyebrow && <div className="fm-detail-eyebrow">{eyebrow}</div>}
				<h2 className="fm-detail-title">{title}</h2>
			</div>

			<div className="fm-detail-header-spacer" />

			{active && (
				<span className="active-indicator">
					{__('Active font', 'gravity-pdf')}
				</span>
			)}

			{!active && canSetActive && (
				<Button variant="secondary" icon={check} onClick={onSetActive}>
					{__('Set as active', 'gravity-pdf')}
				</Button>
			)}
		</div>
	);
}
