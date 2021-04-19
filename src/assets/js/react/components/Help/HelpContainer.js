/* Dependencies */
import React, { Component } from 'react'
import algoliasearch from 'algoliasearch/lite'
import { InstantSearch, SearchBox, Configure, connectHits } from 'react-instantsearch-dom'
/* Components */
import DisplayResultContainer from './DisplayResultContainer'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Handles the grunt work for our Help Page Search Input (API calls, display, state ect)
 *
 * @since 5.2
 */
export class HelpContainer extends Component {
  /**
   * Render and group search result and then call the <DisplayResultContainer /> component
   *
   * @param hierarchy (object)
   *
   * @since 5.2
   */
  onHandleHit = ({ hits }) => {
    /* Group into categories */
    const groups = hits.reduce((groups, item) => ({
      ...groups,
      [item.hierarchy.lvl0]: [...(groups[item.hierarchy.lvl0] || []), [item.hierarchy, item.url, item.content]]
    }), {})

    return <DisplayResultContainer groups={groups} />
  }

  /**
   * Renders search box component UI
   *
   * @since 5.2
   */
  render () {
    const algoliaClient = algoliasearch('BH4D9OD16A', '3f8f81a078907e98ed8d3a5bedc3c61c')
    /* Prevent search for initial load */
    const searchClient = {
      search (requests) {
        /* Don't display any results if the query is empty */
        if (requests[0].params.query === '') {
          return
        }
        return algoliaClient.search(requests)
      }
    }
    const CustomHits = connectHits(this.onHandleHit)

    return (
      <InstantSearch searchClient={searchClient} indexName='gravitypdf'>
        <Configure
          facetFilters={['version:v6']}
          highlightPreTag='<mark>'
          highlightPostTag='</mark>'
          attributesToRetrieve={['hierarchy.lvl0', 'hierarchy.lvl1', 'hierarchy.lvl2', 'hierarchy.lvl3', 'hierarchy.lvl4', 'hierarchy.lvl5', 'hierarchy.lvl6', 'content', 'type', 'url']}
          attributesToSnippet={['hierarchy.lvl1:5', 'hierarchy.lvl2:5', 'hierarchy.lvl3:5', 'hierarchy.lvl4:5', 'hierarchy.lvl5:5', 'hierarchy.lvl6:5', 'content:5']}
          snippetEllipsisText='…'
        />

        <SearchBox
          translations={{
            submitTitle: GFPDF.searchBoxSubmitTitle,
            resetTitle: GFPDF.searchBoxResetTitle,
            placeholder: GFPDF.searchBoxPlaceHolderText
          }}
          autofocus
        />

        <CustomHits />
      </InstantSearch>
    )
  }
}

export default HelpContainer
