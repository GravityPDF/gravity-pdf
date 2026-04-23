/* Dependencies */
import { useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
/* Store */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	FONT_MANAGER_STORE_NAME,
	fontManagerStore,
} from '../../store/fontManagerStore';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	onClose: () => void;
	onCloseDetail?: () => void;
	hasDetailOpen?: boolean;
}

export const CloseDialog = ({
	onClose,
	onCloseDetail,
	hasDetailOpen,
}: Props) => {
	const { clearAddFontMsg } = useDispatch(FONT_MANAGER_STORE_NAME);
	const msg = useSelect((select) => select(fontManagerStore).getMsg(), []);

	const handleKeyPress = (e: KeyboardEvent) => {
		if (e.key !== 'Escape') {
			return;
		}

		/* Close font manager 'Update Font' column first */
		if (hasDetailOpen && onCloseDetail) {
			const { success, error } = msg;
			if ((success && success.addFont) || (error && error.addFont)) {
				clearAddFontMsg();
			}
			onCloseDetail();
			return;
		}

		/* Close modal (unless typing in the search field) */
		const target = e.target as HTMLElement;
		if (
			target.className === 'wp-filter-search' &&
			(target as HTMLInputElement).value !== ''
		) {
			return;
		}
		onClose();
	};

	useEffect(() => {
		document.addEventListener('keydown', handleKeyPress, false);
		return () => {
			document.removeEventListener('keydown', handleKeyPress, false);
		};
	}); // eslint-disable-line react-hooks/exhaustive-deps

	return (
		<Button
			data-test="component-CloseDialog"
			className="close"
			onClick={onClose}
			icon={closeSmall}
			label={__('Close dialog', 'gravity-pdf')}
		/>
	);
};

export default CloseDialog;
