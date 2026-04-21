/* Dependencies */
import { useEffect } from '@wordpress/element';
import { useNavigate, useLocation } from 'react-router-dom';
/* Redux actions */
import { clearAddFontMsg } from '../../actions/fontManager';
/* Redux hooks */
import { useAppSelector, useAppDispatch } from '../../store/hooks';
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
	const dispatch = useAppDispatch();
	const msg = useAppSelector((state) => state.fontManager.msg);

	const handleCloseDialog = () => {
		navigate(closeRoute || '/');
	};

	const handleKeyPress = (e: KeyboardEvent) => {
		const { success, error } = msg;

		/* Close font manager 'Update Font' column first */
		if (e.key === 'Escape' && id) {
			if ((success && success.addFont) || (error && error.addFont)) {
				dispatch(clearAddFontMsg());
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
		<button
			type="button"
			data-test="component-CloseDialog"
			className="close dashicons dashicons-no"
			onClick={handleCloseDialog}
			aria-label="close"
		>
			<span className="screen-reader-text">{GFPDF.closeDialog}</span>
		</button>
	);
};

export default CloseDialog;
