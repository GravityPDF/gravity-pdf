import * as React from '@wordpress/element';
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
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
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

interface EmptyQueryBoundaryProps {
	children: React.ReactNode;
	fallback: React.ReactNode;
}

function EmptyQueryBoundary({ children, fallback }: EmptyQueryBoundaryProps) {
	const { results, indexUiState } = useInstantSearch();

	if (!indexUiState.query) {
		return null;
	}

	if (!results.__isArtificial && results.nbHits === 0) {
		return fallback;
	}

	return children;
}

function NoResults() {
	return (
		<div className="search-result">
			<em>{GFPDF.noResultText}</em>
		</div>
	);
}

function CustomHits(props: object) {
	const { items } = useHits(props);

	/* Group and order the data */
	// TODO: type Algolia hit properly
	const groupedItems: Record<string, any[]> = {};
	items.forEach((hit) => {
		const h = hit as any;
		if (!groupedItems[h?.hierarchy?.lvl0]) {
			groupedItems[h?.hierarchy?.lvl0] = [];
		}

		groupedItems[h?.hierarchy?.lvl0].push(hit);
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
