/* Dependencies */
import React from 'react';
import PropTypes from 'prop-types';
import { useSelector } from 'react-redux';
/* Components */
import FontListHeader from './FontListHeader';
import FontListItems from './FontListItems';
import FontListSkeleton from './FontListSkeleton';
import FontListAlertMessage from './FontListAlertMessage';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display font list UI
 *
 * @param {Object} root0
 * @param {*}      root0.id
 * @param {*}      root0.navigate
 * @since 6.0
 */
const FontList = ({ id, navigate }) => {
	const loading = useSelector((state) => state.fontManager.loading);
	const fontList = useSelector((state) => state.fontManager.fontList);
	const searchResult = useSelector((state) => state.fontManager.searchResult);
	const msg = useSelector((state) => state.fontManager.msg);
	const { error } = msg;

	const fontListError = error && error.fontList;
	const fontListEmpty = fontList.length === 0 && !searchResult;
	const checkSearchResult =
		(searchResult && searchResult.length === 0) || !searchResult;
	const latestData = fontList.length > 0 && !searchResult;
	const emptySearchResult =
		!fontListError && !loading && !latestData && checkSearchResult;

	return (
		<div
			data-test="component-FontList"
			className="font-list"
			aria-live="polite"
		>
			<FontListHeader />

			{loading ? (
				<FontListSkeleton />
			) : (
				<FontListItems id={id} navigate={navigate} />
			)}

			{fontListEmpty && emptySearchResult && (
				<FontListAlertMessage empty={fontListEmpty} />
			)}

			{!fontListEmpty && emptySearchResult && <FontListAlertMessage />}

			{fontListError && <FontListAlertMessage error={error.fontList} />}
		</div>
	);
};

FontList.propTypes = {
	id: PropTypes.string,
	navigate: PropTypes.func.isRequired,
};

export default FontList;
