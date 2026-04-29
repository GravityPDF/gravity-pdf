/* Dependencies */
import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { FONT_MANAGER_STORE_NAME } from '../../store/fontManagerStore';

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
	const { resetSearchResult, getCustomFontList } = useDispatch(
		FONT_MANAGER_STORE_NAME
	);

	const fontListEmpty = <span>{__('Font list empty.', 'gravity-pdf')}</span>;
	const searchResultEmpty = (
		<span>
			{__('No fonts matching your search found.', 'gravity-pdf')}{' '}
			<button
				type="button"
				className="link"
				onClick={() => resetSearchResult()}
			>
				{__('Clear your search query.', 'gravity-pdf')}
			</button>
		</span>
	);
	const apiError = (
		<button
			type="button"
			className="link"
			onClick={() => getCustomFontList()}
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
