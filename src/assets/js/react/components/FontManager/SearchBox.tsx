/* Dependencies */
import * as React from '@wordpress/element';
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { FONT_MANAGER_STORE_NAME } from '../../store/fontManagerStore';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

interface Props {
	id?: string;
}

const SearchBox = ({ id }: Props) => {
	const { resetSearchResult, searchFontList } = useDispatch(
		FONT_MANAGER_STORE_NAME
	);
	const searchResult = useSelect(
		(select) => select(FONT_MANAGER_STORE_NAME).getSearchResult(),
		[]
	);
	const msg = useSelect(
		(select) => select(FONT_MANAGER_STORE_NAME).getMsg(),
		[]
	);
	const [searchInput, setSearchInput] = useState('');
	const inputRef = useRef<HTMLInputElement>(null);

	/* Track the latest searchInput value for unmount cleanup without stale closure */
	const lastSearchInput = useRef('');

	/* Focus the search input on mount */
	useEffect(() => {
		inputRef.current?.focus();
	}, []);

	/* Reset search state when searchResult becomes null */
	useEffect(() => {
		if (!searchResult) {
			setSearchInput('');
			lastSearchInput.current = '';
		}
	}, [searchResult]);

	/* Clear search box after a successful font has been added */
	useEffect(() => {
		if (msg.success && id) {
			setSearchInput('');
			lastSearchInput.current = '';
		}
	}, [msg, id]);

	/* Dispatch resetSearchResult on unmount if search input is not empty */
	useEffect(() => {
		return () => {
			if (lastSearchInput.current !== '') {
				resetSearchResult();
			}
		};
	}, [resetSearchResult]);

	const handleSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
		const data = e.target.value;
		lastSearchInput.current = data;
		setSearchInput(data);
		searchFontList(data);
	};

	return (
		<div role="form">
			<input
				data-test="component-SearchBox"
				type="search"
				id="font-manager-search-box"
				className="wp-filter-search"
				placeholder={__('Search installed fonts', 'gravity-pdf')}
				value={searchInput}
				onChange={handleSearch}
				onKeyDown={(e) => e.keyCode === 13 && e.preventDefault()}
				ref={inputRef}
			/>
		</div>
	);
};

export default SearchBox;
