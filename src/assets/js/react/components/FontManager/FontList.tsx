/* Dependencies */
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { fontManagerStore } from '../../store/fontManagerStore';
/* Components */
import FontListItems from './FontListItems';
import FontListAlertMessage from './FontListAlertMessage';

const SKELETON_ROWS = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];

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
			<div className="font-list-header">
				<div className="font-name">
					{__('Installed Fonts', 'gravity-pdf')}
				</div>
				<div>{__('Regular', 'gravity-pdf')}</div>
				<div>{__('Italics', 'gravity-pdf')}</div>
				<div>{__('Bold', 'gravity-pdf')}</div>
				<div>{__('Bold Italics', 'gravity-pdf')}</div>
				<div />
			</div>

			{loading ? (
				<div className="font-list-items-skeleton">
					{SKELETON_ROWS.map((row) => (
						<div key={row} className="font-list-item">
							<div>
								<span className="placeholder dashicons dashicons-trash" />
							</div>
							<span className="placeholder font-name" />
							<div>
								<span className="placeholder dashicons dashicons-yes" />
							</div>
							<div>
								<span className="placeholder dashicons dashicons-no-alt" />
							</div>
							<div>
								<span className="placeholder dashicons dashicons-no-alt" />
							</div>
							<div>
								<span className="placeholder dashicons dashicons-no-alt" />
							</div>
						</div>
					))}
				</div>
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
