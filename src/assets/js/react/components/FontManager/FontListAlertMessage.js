/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { useDispatch } from 'react-redux';
/* Redux actions */
import {
	getCustomFontList as getCustomFontListAction,
	resetSearchResult as resetSearchResultAction,
} from '../../actions/fontManager';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display alert message for font list UI
 *
 * @param {Object} root0
 * @param {*}      root0.empty
 * @param {*}      root0.error
 * @since 6.0
 */
const FontListAlertMessage = ({ empty, error }) => {
	const dispatch = useDispatch();

	const fontListEmpty = <span>{GFPDF.fontListEmpty}</span>;
	const searchResultEmpty = (
		<span>
			{GFPDF.searchResultEmpty}{' '}
			<button
				type="button"
				className="link"
				onClick={() => dispatch(resetSearchResultAction())}
			>
				{GFPDF.searchBoxResetTitle}
			</button>
		</span>
	);
	const apiError = (
		<button
			type="button"
			className="link"
			onClick={() => dispatch(getCustomFontListAction())}
		>
			{error}
		</button>
	);
	const hasNoError = !error ? searchResultEmpty : apiError;
	const displayContent = empty ? fontListEmpty : hasNoError;

	return (
		<div
			data-test="component-FontListAlertMessage"
			className="alert-message"
		>
			{displayContent}
		</div>
	);
};

FontListAlertMessage.propTypes = {
	empty: PropTypes.bool,
	error: PropTypes.string,
};

export default FontListAlertMessage;
