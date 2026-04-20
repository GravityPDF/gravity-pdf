/* Dependencies */
import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import { useSelector, useDispatch } from 'react-redux';
import { useNavigate, useLocation } from 'react-router-dom';
/* Redux actions */
import { clearAddFontMsg } from '../../actions/fontManager';
/* Utilities */
import { toggleUpdateFont } from '../../utilities/FontManager/toggleUpdateFont';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * CloseDialog component
 *
 * @param {Object} root0
 * @param {*}      root0.id
 * @param {*}      root0.closeRoute
 * @since 6.0
 */
export const CloseDialog = ({ id, closeRoute }) => {
	const navigate = useNavigate();
	const { pathname } = useLocation();
	const dispatch = useDispatch();
	const msg = useSelector((state) => state.fontManager.msg);

	const handleCloseDialog = () => {
		navigate(closeRoute || '/');
	};

	const handleKeyPress = (e) => {
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
			(e.target.className !== 'wp-filter-search' || e.target.value === '')
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

CloseDialog.propTypes = {
	id: PropTypes.string,
	closeRoute: PropTypes.string,
};

export default CloseDialog;
