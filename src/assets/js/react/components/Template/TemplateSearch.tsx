/* Dependencies */
import { useRef, useEffect } from '@wordpress/element';
import type { ChangeEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useDebounce } from '@wordpress/compose';
/* Store */
import { TEMPLATE_STORE_NAME, templateStore } from '../../store/templateStore';

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
	const search = useSelect((select) => select(templateStore).getSearch(), []);
	const inputRef = useRef<HTMLInputElement>(null);

	const runSearch = useDebounce(searchTemplates, 200);

	useEffect(() => {
		inputRef.current?.focus();
	}, []);

	const handleSearch = (e: ChangeEvent<HTMLInputElement>) => {
		runSearch(e.target.value || '');
	};

	return (
		<div data-test="component-templateSearch" role="form">
			<input
				className="wp-filter-search"
				id="wp-filter-search-input"
				ref={inputRef}
				placeholder={__('Search Installed Templates', 'gravity-pdf')}
				type="search"
				aria-describedby="live-search-desc"
				onChange={handleSearch}
				defaultValue={search}
			/>
		</div>
	);
};

export default TemplateSearch;
