/* Dependencies */
import { __, sprintf } from '@wordpress/i18n';
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
	fontName: string;
	onConfirm: () => void;
	onCancel: () => void;
}

const DeleteFontDialog = ({ isOpen, fontName, onConfirm, onCancel }: Props) => (
	<div data-test="component-DeleteFontDialog">
		<ConfirmDialog
			isOpen={isOpen}
			title={
				fontName
					? sprintf(
							/* translators: %s: font name */
							__('Delete %s?', 'gravity-pdf'),
							`"${fontName}"`
						)
					: __('Delete font?', 'gravity-pdf')
			}
			confirmButtonText={__('Delete font', 'gravity-pdf')}
			cancelButtonText={__('Cancel', 'gravity-pdf')}
			onConfirm={onConfirm}
			onCancel={onCancel}
			className="gfpdf-fm-dialog gfpdf-fm-dialog--destructive"
		>
			{__(
				'This font will be removed from the site. Any PDFs set to use it will fall back to the active font.',
				'gravity-pdf'
			)}
		</ConfirmDialog>
	</div>
);

export default DeleteFontDialog;
