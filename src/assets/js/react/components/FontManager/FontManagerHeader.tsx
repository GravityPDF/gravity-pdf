/* Dependencies */
import { __ } from '@wordpress/i18n';
/* Components */
import CloseDialog from '../Modal/CloseDialog';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	activeFontId: string;
	onSelectFont: (id: string) => void;
	onClose: () => void;
}

const FontManagerHeader = ({ activeFontId, onSelectFont, onClose }: Props) => (
	<div data-test="component-FontManagerHeader" className="theme-header">
		<h1>{__('Font Manager', 'gravity-pdf')}</h1>

		<CloseDialog
			onClose={onClose}
			onCloseDetail={() => {
				document
					.querySelector('.update-font')
					?.classList.remove('show');
				onSelectFont('');
			}}
			hasDetailOpen={!!activeFontId}
		/>
	</div>
);

export default FontManagerHeader;
