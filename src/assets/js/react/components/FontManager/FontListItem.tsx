/* Dependencies */
import { Button, Spinner } from '@wordpress/components';
import { trash } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';
import type { MouseEvent, KeyboardEvent } from 'react';
/* Types */
import { FontItem } from '../../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface Props {
	font: FontItem;
	displayName: string;
	isEditing: boolean;
	isActive: boolean;
	isDeleting: boolean;
	onSelect: () => void;
	onRequestDelete: (e: MouseEvent) => void;
	onRequestDeleteKeypress: (e: KeyboardEvent) => void;
	itemId: string;
}

const countVariants = (font: FontItem): number => {
	let n = 0;
	if (font.regular) {
		n++;
	}
	if (font.italics) {
		n++;
	}
	if (font.bold) {
		n++;
	}
	if (font.bolditalics) {
		n++;
	}
	return n;
};

const FontListItem = ({
	font,
	displayName,
	isEditing,
	isActive,
	isDeleting,
	onSelect,
	onRequestDelete,
	onRequestDeleteKeypress,
	itemId,
}: Props) => {
	const variantCount = countVariants(font);
	const className = [
		'gfpdf-fm-row',
		isEditing ? 'is-selected' : '',
		isActive ? 'is-active' : '',
	]
		.filter(Boolean)
		.join(' ');

	const ariaLabel = isActive
		? sprintf(
				/* translators: %s: font name */
				__('%s, active font', 'gravity-pdf'),
				displayName
			)
		: displayName;

	return (
		/* eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/interactive-supports-focus */
		<div
			data-test="component-FontListItem"
			id={itemId}
			role="option"
			aria-selected={isEditing}
			aria-label={ariaLabel}
			className={className}
			onClick={onSelect}
		>
			{isActive && (
				<span className="gfpdf-fm-row__active-bar" aria-hidden="true" />
			)}
			<div className="gfpdf-fm-row__swatch" aria-hidden="true">
				Aa
			</div>
			<div className="gfpdf-fm-row__meta">
				<div className="gfpdf-fm-row__name-wrap">
					<div className="gfpdf-fm-row__name">
						{displayName ? (
							displayName
						) : (
							<span className="gfpdf-fm-row__name-placeholder">
								{__('New font', 'gravity-pdf')}
							</span>
						)}
					</div>
					{isActive && (
						<span className="gfpdf-fm-row__active-pill">
							{__('Active', 'gravity-pdf')}
						</span>
					)}
				</div>
				<div className="gfpdf-fm-row__sub">
					<span className="gfpdf-fm-row__variants">
						{sprintf(
							/* translators: %d: variant count (0–4) */
							__('%d/4 variants', 'gravity-pdf'),
							variantCount
						)}
					</span>
				</div>
			</div>
			<div
				className="gfpdf-fm-row__delete-wrap"
				onClick={(e) => e.stopPropagation()}
				onKeyDown={(e) => e.stopPropagation()}
				role="presentation"
			>
				{isDeleting ? (
					<Spinner />
				) : (
					<Button
						variant="tertiary"
						size="small"
						icon={trash}
						onClick={onRequestDelete}
						onKeyDown={onRequestDeleteKeypress}
						label={sprintf(
							/* translators: %s: font name */
							__('Delete %s', 'gravity-pdf'),
							displayName
						)}
					/>
				)}
			</div>
		</div>
	);
};

export default FontListItem;
