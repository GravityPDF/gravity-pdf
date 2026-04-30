/* Dependencies */
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface Props {
	isDraft: boolean;
	canDelete: boolean;
	canSave: boolean;
	canCancel: boolean;
	saving: boolean;
	onSave: () => void;
	onCancel: () => void;
	onRequestDelete: () => void;
}

const FontDetailFooter = ({
	isDraft,
	canDelete,
	canSave,
	canCancel,
	saving,
	onSave,
	onCancel,
	onRequestDelete,
}: Props) => (
	<div
		data-test="component-FontDetailFooter"
		className="gfpdf-fm-detail-footer"
	>
		{canDelete ? (
			<Button
				data-test="component-FontDetailFooter-delete"
				className="gfpdf-fm-delete-button"
				variant="secondary"
				isDestructive
				onClick={onRequestDelete}
			>
				{__('Delete font', 'gravity-pdf')}
			</Button>
		) : (
			<span className="gfpdf-fm-detail-footer__placeholder" />
		)}
		<div className="gfpdf-fm-detail-footer__spacer" />
		<Button
			variant="secondary"
			onClick={onCancel}
			disabled={!canCancel}
			__next40pxDefaultSize
		>
			{__('Cancel', 'gravity-pdf')}
		</Button>
		<Button
			data-test="component-FontDetailFooter-save"
			variant="primary"
			onClick={onSave}
			disabled={!canSave || saving}
			__next40pxDefaultSize
		>
			{saving && <Spinner />}
			{isDraft
				? __('Add font', 'gravity-pdf')
				: __('Save changes', 'gravity-pdf')}
		</Button>
	</div>
);

export default FontDetailFooter;
