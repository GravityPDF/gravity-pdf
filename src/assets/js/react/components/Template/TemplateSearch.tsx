/* Dependencies */
import * as React from '@wordpress/element';
import { useRef, useEffect, useMemo } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import debounce from 'lodash.debounce';
/* Store */
import { TEMPLATE_STORE_NAME } from '../../store/templateStore';

/**
 * Handles the PDF template search functionality
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

const TemplateSearch = () => {
	const { searchTemplates } = useDispatch(TEMPLATE_STORE_NAME);
	const search = useSelect(
		(select) => select(TEMPLATE_STORE_NAME).getSearch(),
		[]
	);
	const inputRef = useRef<HTMLInputElement>(null);

	const runSearch = useMemo(
		() => debounce((value: string) => searchTemplates(value), 200),
		[searchTemplates]
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
