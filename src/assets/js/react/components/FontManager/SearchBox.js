/* Dependencies */
import React, { useState, useEffect, useRef } from 'react';
import { useSelector, useDispatch } from 'react-redux';
import PropTypes from 'prop-types';
/* Redux actions */
import { resetSearchResult, searchFontList } from '../../actions/fontManager';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * SearchBox component
 *
 * @param {Object} root0
 * @param {*}      root0.id
 * @since 6.0
 */
const SearchBox = ({ id }) => {
	const dispatch = useDispatch();
	const searchResult = useSelector((state) => state.fontManager.searchResult);
	const msg = useSelector((state) => state.fontManager.msg);
	const [searchInput, setSearchInput] = useState('');
	const inputRef = useRef(null);

	/* Track the latest searchInput value for unmount cleanup without stale closure */
	const lastSearchInput = useRef('');

	/* Focus the search input on mount */
	useEffect(() => {
		inputRef.current.focus();
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
				dispatch(resetSearchResult());
			}
		};
	}, [dispatch]);

	const handleSearch = (e) => {
		const data = e.target.value;
		lastSearchInput.current = data;
		setSearchInput(data);
		dispatch(searchFontList(data));
	};

	return (
		<div role="form">
			<input
				data-test="component-SearchBox"
				type="search"
				id="font-manager-search-box"
				className="wp-filter-search"
				placeholder={GFPDF.fontManagerSearchPlaceHolder}
				value={searchInput}
				onChange={handleSearch}
				onKeyDown={(e) => e.keyCode === 13 && e.preventDefault()}
				ref={inputRef}
			/>
		</div>
	);
};

SearchBox.propTypes = {
	id: PropTypes.string,
};

export default SearchBox;
