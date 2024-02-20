/* Dependencies */
import React from 'react'
import algoliasearch from 'algoliasearch/lite'
import { Configure, InstantSearch, SearchBox, useHits, useInstantSearch } from 'react-instantsearch'
/* Components */
import DisplayResultContainer from './DisplayResultContainer'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Handles the grunt work for our Help Page Search Input (API calls, display, state ect)
 *
 * @since 5.2
 */
export const HelpContainer = () => {
  const algoliaClient = algoliasearch('NKKEAC9I6I', '8c7d9c872c821829fac8251da2c9151c')

  return (
    <InstantSearch
      searchClient={algoliaClient}
      indexName='gravitypdf'
      future={{
        preserveSharedStateOnUnmount: true
      }}
    >
      <Configure
        facetFilters={['version:v6']}
        highlightPreTag='<mark>'
        highlightPostTag='</mark>'
        attributesToRetrieve={['hierarchy.lvl0', 'hierarchy.lvl1', 'hierarchy.lvl2', 'hierarchy.lvl3', 'hierarchy.lvl4', 'hierarchy.lvl5', 'hierarchy.lvl6', 'content', 'type', 'url']}
        attributesToSnippet={['hierarchy.lvl1:5', 'hierarchy.lvl2:5', 'hierarchy.lvl3:5', 'hierarchy.lvl4:5', 'hierarchy.lvl5:5', 'hierarchy.lvl6:5', 'content:5']}
        snippetEllipsisText='…'
        distinct={1}
      />

      <SearchBox
        placeholder={GFPDF.searchBoxPlaceHolderText}
        translations={{
          submitButtonTitle: GFPDF.searchBoxSubmitTitle,
          resetButtonTitle: GFPDF.searchBoxResetTitle
        }}
        autoFocus
      />

      <EmptyQueryBoundary fallback={null}>
        <Hits />
      </EmptyQueryBoundary>
    </InstantSearch>
  )
}

function EmptyQueryBoundary ({ children, fallback }) {
  const { indexUiState } = useInstantSearch()

  if (!indexUiState.query) {
    return fallback
  }

  return children
}

function Hits (props) {
  const { hits } = useHits(props)

  /* Group into categories */
  const groups = hits.reduce((groups, item) => ({
    ...groups,
    [item.hierarchy.lvl0]: [...(groups[item.hierarchy.lvl0] || []), [item.hierarchy, item.url, item.content]]
  }), {})

  return <DisplayResultContainer groups={groups} />
}

export default HelpContainer
