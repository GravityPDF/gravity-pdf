import React from 'react';
import { liteClient } from 'algoliasearch/lite';
import {
	Configure,
	InstantSearch,
	SearchBox,
	useHits,
	useInstantSearch,
} from 'react-instantsearch';
import DisplayResult from './DisplayResult';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Handles the grunt work for our Help Page Search Input (API calls, display, state ect)
 *
 * @return { JSX.Element } HelpContainer Component
 *
 * @since 5.2
 */
export const HelpContainer = () => {
	const algoliaClient = liteClient(
		'NKKEAC9I6I',
		'8c7d9c872c821829fac8251da2c9151c'
	);

	return (
		<InstantSearch
			searchClient={algoliaClient}
			indexName="gravitypdf"
			future={{
				preserveSharedStateOnUnmount: true,
			}}
		>
			<Configure
				facetFilters={['version:v6']}
				highlightPreTag="<mark>"
				highlightPostTag="</mark>"
				attributesToRetrieve={[
					'hierarchy.lvl0',
					'hierarchy.lvl1',
					'hierarchy.lvl2',
					'hierarchy.lvl3',
					'hierarchy.lvl4',
					'hierarchy.lvl5',
					'hierarchy.lvl6',
					'content',
					'type',
					'url',
				]}
				attributesToSnippet={[
					'hierarchy.lvl1',
					'hierarchy.lvl2',
					'hierarchy.lvl3',
					'hierarchy.lvl4',
					'hierarchy.lvl5',
					'hierarchy.lvl6',
					'content',
				]}
				snippetEllipsisText="…"
				distinct={1}
			/>

			<SearchBox
				placeholder={GFPDF.searchBoxPlaceHolderText}
				translations={{
					submitButtonTitle: GFPDF.searchBoxSubmitTitle,
					resetButtonTitle: GFPDF.searchBoxResetTitle,
				}}
			/>

			<EmptyQueryBoundary fallback={<NoResults />}>
				<div className="search-result">
					<CustomHits />
				</div>
			</EmptyQueryBoundary>
		</InstantSearch>
	);
};

/**
 * Determine if the search results are empty and output the children or fallback component
 *
 * @param { Object }          props
 * @param { React.ReactNode } props.children
 * @param { React.ReactNode } props.fallback
 *
 * @return { JSX.Element } EmptyQueryBoundary Component
 *
 * @since 5.2
 */
function EmptyQueryBoundary({ children, fallback }) {
	const { results, indexUiState } = useInstantSearch();

	if (!indexUiState.query) {
		return null;
	}

	if (!results.__isArtificial && results.nbHits === 0) {
		return fallback;
	}

	return children;
}

/**
 * Show no results message when search could not find anything
 *
 * @return { JSX.Element } NoResults Component
 *
 * @since 6.12.4
 */
function NoResults() {
	return (
		<div className="search-result">
			<em>{GFPDF.noResultText}</em>
		</div>
	);
}

/**
 * Display documentation search results in groups
 *
 * @param { Object } props
 *
 * @return { JSX.Element } CustomHits Component
 *
 * @since 6.12.4
 */
function CustomHits(props) {
	const { items } = useHits(props);

	/* Group and order the data */
	const groupedItems = {};
	items.forEach((hit) => {
		if (!groupedItems[hit?.hierarchy?.lvl0]) {
			groupedItems[hit?.hierarchy?.lvl0] = [];
		}

		groupedItems[hit?.hierarchy?.lvl0].push(hit);
	});

	return Object.keys(groupedItems)
		.sort((a, b) => {
			/* push developer docs after the user docs */
			if (a.startsWith('Developer') && b.startsWith('User')) {
				return 1;
			}

			return 0;
		})
		.map((title) => {
			return (
				<div key={title}>
					<div className="group-name">{title}</div>

					<ol>
						{groupedItems[title].map((hit) => (
							<li key={hit.objectID}>
								<DisplayResult hit={hit} />
							</li>
						))}
					</ol>
				</div>
			);
		});
}

export default HelpContainer;
