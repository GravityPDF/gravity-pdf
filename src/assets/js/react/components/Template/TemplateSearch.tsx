/* Dependencies */
import React, { useRef, useEffect, useMemo } from 'react';
import debounce from 'lodash.debounce';
/* Redux hooks and actions */
import { useAppSelector, useAppDispatch } from '../../store/hooks';
import { searchTemplates as searchTemplatesAction } from '../../actions/templates';

/**
 * Handles the PDF template search functionality
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

const TemplateSearch = () => {
	const dispatch = useAppDispatch();
	const search = useAppSelector((s) => s.template.search);
	const inputRef = useRef<HTMLInputElement>(null);

	const runSearch = useMemo(
		() =>
			debounce(
				(value: string) => dispatch(searchTemplatesAction(value)),
				200
			),
		[dispatch]
	);

	useEffect(() => {
		inputRef.current?.focus();
	}, []);

	const handleSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
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
