/* Dependencies */
import { Button } from '@wordpress/components';
import { check, chevronLeft } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface Props {
	mode: 'add' | 'edit';
	isActive: boolean;
	canSetActive: boolean;
	dirty: boolean;
	onSetActive: () => void;
	onMobileBack: () => void;
}

const FontDetailHeader = ({
	mode,
	isActive,
	canSetActive,
	dirty,
	onSetActive,
	onMobileBack,
}: Props) => {
	const backLabel = dirty
		? __(
				'Back to font list (unsaved changes will be discarded)',
				'gravity-pdf'
			)
		: __('Back to font list', 'gravity-pdf');

	return (
		<div
			data-test="component-FontDetailHeader"
			className="gfpdf-fm-detail-header"
		>
			<Button
				className="gfpdf-fm-mobile-back"
				icon={chevronLeft}
				onClick={onMobileBack}
				label={backLabel}
				showTooltip={false}
			>
				{__('Fonts', 'gravity-pdf')}
			</Button>
			<h2 className="gfpdf-fm-detail-title">
				{mode === 'edit'
					? __('Edit font', 'gravity-pdf')
					: __('Add font', 'gravity-pdf')}
			</h2>
			<div className="gfpdf-fm-detail-header-spacer" />
			{isActive && (
				<span
					className="gfpdf-fm-active-indicator"
					data-test="component-ActiveIndicator"
				>
					<span aria-hidden="true" className="gfpdf-fm-active-icon">
						<svg
							viewBox="0 0 24 24"
							width="16"
							height="16"
							fill="currentColor"
						>
							<path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z" />
						</svg>
					</span>
					{__('Active font', 'gravity-pdf')}
				</span>
			)}
			{!isActive && canSetActive && (
				<Button
					data-test="component-SetActiveButton"
					variant="secondary"
					icon={check}
					onClick={onSetActive}
					__next40pxDefaultSize
				>
					{__('Set as active', 'gravity-pdf')}
				</Button>
			)}
		</div>
	);
};

export default FontDetailHeader;
