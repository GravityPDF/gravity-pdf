/* Dependencies */
import { useSelect } from '@wordpress/data';
import { fontManagerStore } from '../../store/fontManagerStore';
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
	activeFontId: string;
	onSelectFont: (id: string) => void;
	hasDetailOpen: boolean;
}

const FontList = ({ activeFontId, onSelectFont, hasDetailOpen }: Props) => {
	const loading = useSelect(
		(select) => select(fontManagerStore).getLoading(),
		[]
	);
	const fontList = useSelect(
		(select) => select(fontManagerStore).getFontList(),
		[]
	);
	const searchResult = useSelect(
		(select) => select(fontManagerStore).getSearchResult(),
		[]
	);
	const msg = useSelect((select) => select(fontManagerStore).getMsg(), []);
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
				<FontListItems
					activeFontId={activeFontId}
					onSelectFont={onSelectFont}
					hasDetailOpen={hasDetailOpen}
				/>
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
