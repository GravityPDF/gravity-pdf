/* Dependencies */
import { __ } from '@wordpress/i18n';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface Props {
	isOpen: boolean;
	onConfirm: () => void;
	onCancel: () => void;
}

const SaveReplaceDialog = ({ isOpen, onConfirm, onCancel }: Props) => (
	<div data-test="component-SaveReplaceDialog">
		<ConfirmDialog
			isOpen={isOpen}
			title={__('Save changes to font files?', 'gravity-pdf')}
			confirmButtonText={__('Save changes', 'gravity-pdf')}
			cancelButtonText={__('Cancel', 'gravity-pdf')}
			onConfirm={onConfirm}
			onCancel={onCancel}
			className="gfpdf-fm-dialog"
		>
			{__(
				'This will apply to all PDFs using this font. Existing PDFs may re-render with the updated files.',
				'gravity-pdf'
			)}
		</ConfirmDialog>
	</div>
);

export default SaveReplaceDialog;
