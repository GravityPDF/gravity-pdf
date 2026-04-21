/* Dependencies */
import React from 'react';
import { NavigateFunction } from 'react-router-dom';
import { useAppSelector } from '../../store/hooks';
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

interface Props {
	id?: string;
	navigate: NavigateFunction;
}

const FontList = ({ id, navigate }: Props) => {
	const loading = useAppSelector((state) => state.fontManager.loading);
	const fontList = useAppSelector((state) => state.fontManager.fontList);
	const searchResult = useAppSelector(
		(state) => state.fontManager.searchResult
	);
	const msg = useAppSelector((state) => state.fontManager.msg);
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

export default FontList;
