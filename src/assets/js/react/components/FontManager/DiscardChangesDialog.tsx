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

const DiscardChangesDialog = ({ isOpen, onConfirm, onCancel }: Props) => (
	<div data-test="component-DiscardChangesDialog">
		<ConfirmDialog
			isOpen={isOpen}
			title={__('Discard unsaved changes?', 'gravity-pdf')}
			confirmButtonText={__('Discard changes', 'gravity-pdf')}
			cancelButtonText={__('Keep editing', 'gravity-pdf')}
			onConfirm={onConfirm}
			onCancel={onCancel}
			className="gfpdf-fm-dialog gfpdf-fm-dialog--destructive"
		>
			{__(
				'Your unsaved changes will be lost. Are you sure you want to leave this font?',
				'gravity-pdf'
			)}
		</ConfirmDialog>
	</div>
);

export default DiscardChangesDialog;
