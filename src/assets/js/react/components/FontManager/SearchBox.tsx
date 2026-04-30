/* Dependencies */
import { useState, useEffect, useRef } from '@wordpress/element';
import { SearchControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
/* Store */
import {
	FONT_MANAGER_STORE_NAME,
	fontManagerStore,
} from '../../store/fontManagerStore';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

const SearchBox = () => {
	const { resetSearchResult, searchFontList } = useDispatch(
		FONT_MANAGER_STORE_NAME
	);
	const searchResult = useSelect(
		(select) => select(fontManagerStore).getSearchResult(),
		[]
	);
	const [value, setValue] = useState('');
	const lastValueRef = useRef('');

	/* Reset local input when searchResult clears externally */
	useEffect(() => {
		if (!searchResult) {
			setValue('');
			lastValueRef.current = '';
		}
	}, [searchResult]);

	useEffect(() => {
		return () => {
			if (lastValueRef.current !== '') {
				resetSearchResult();
			}
		};
	}, [resetSearchResult]);

	const handleChange = (next: string) => {
		lastValueRef.current = next;
		setValue(next);
		searchFontList(next);
	};

	return (
		<div data-test="component-SearchBox" className="gfpdf-fm-search">
			<SearchControl
				__nextHasNoMarginBottom
				label={__('Search fonts', 'gravity-pdf')}
				placeholder={__('Search fonts…', 'gravity-pdf')}
				value={value}
				onChange={handleChange}
			/>
		</div>
	);
};

export default SearchBox;
