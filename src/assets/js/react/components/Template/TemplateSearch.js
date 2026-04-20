/* Dependencies */
import React, { useRef, useEffect, useMemo } from 'react';
import { useSelector, useDispatch } from 'react-redux';
import debounce from 'lodash.debounce';
/* Redux actions */
import { searchTemplates as searchTemplatesAction } from '../../actions/templates';

/**
 * Handles the PDF template search functionality
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @since 4.1
 */
const TemplateSearch = () => {
	const dispatch = useDispatch();
	const search = useSelector((s) => s.template.search);
	const inputRef = useRef(null);

	const runSearch = useMemo(
		() => debounce((value) => dispatch(searchTemplatesAction(value)), 200),
		[dispatch]
	);

	useEffect(() => {
		inputRef.current.focus();
	}, []);

	const handleSearch = (e) => {
		runSearch(e.target.value || '');
	};

	return (
		<div data-test="component-templateSearch" role="form">
			<input
				className="wp-filter-search"
				id="wp-filter-search-input"
				ref={inputRef}
				placeholder={GFPDF.searchTemplatePlaceholder}
				type="search"
				aria-describedby="live-search-desc"
				onChange={handleSearch}
				defaultValue={search}
			/>
		</div>
	);
};

export default TemplateSearch;
