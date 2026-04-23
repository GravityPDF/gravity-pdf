/* Dependencies */
import { useEffect } from '@wordpress/element';
import { useNavigate, useLocation } from 'react-router';
import { Button } from '@wordpress/components';
import { closeSmall } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
/* Store */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	FONT_MANAGER_STORE_NAME,
	fontManagerStore,
} from '../../store/fontManagerStore';
/* Utilities */
import { toggleUpdateFont } from '../../utilities/FontManager/toggleUpdateFont';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	id?: string;
	closeRoute?: string;
}

export const CloseDialog = ({ id, closeRoute }: Props) => {
	const navigate = useNavigate();
	const { pathname } = useLocation();
	const { clearAddFontMsg } = useDispatch(FONT_MANAGER_STORE_NAME);
	const msg = useSelect((select) => select(fontManagerStore).getMsg(), []);

	const handleCloseDialog = () => {
		navigate(closeRoute || '/');
	};

	const handleKeyPress = (e: KeyboardEvent) => {
		const { success, error } = msg;

		/* Close font manager 'Update Font' column first */
		if (e.key === 'Escape' && id) {
			if ((success && success.addFont) || (error && error.addFont)) {
				clearAddFontMsg();
			}

			return toggleUpdateFont(navigate, '', pathname);
		}

		/* Close modal */
		if (
			e.key === 'Escape' &&
			((e.target as HTMLElement).className !== 'wp-filter-search' ||
				(e.target as HTMLInputElement).value === '')
		) {
			handleCloseDialog();
		}
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
			onClick={handleCloseDialog}
			icon={closeSmall}
			label={__('Close dialog', 'gravity-pdf')}
		/>
	);
};

export default CloseDialog;
