/* Dependencies */
import React from 'react';
import { useAppDispatch } from '../../store/hooks';
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

interface Props {
	empty?: boolean;
	error?: string;
}

const FontListAlertMessage = ({ empty, error }: Props) => {
	const dispatch = useAppDispatch();

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

export default FontListAlertMessage;
