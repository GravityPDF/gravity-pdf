import React from 'react'
import PropTypes from 'prop-types'
import Spinner from '../Spinner'
import DisplayResultItem from './DisplayResultItem'
import DisplayResultEmpty from './DisplayResultEmpty'
import DisplayError from './DisplayError'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Displays result based on Search Input value
 *
 * @param searchInput (string)
 * @param loading (boolean)
 * @param helpResult (object)
 * @param error (string)
 *
 * @since 5.2
 */
const DisplayResultContainer = ({ searchInput, loading, helpResult, error }) => {
  if (searchInput.length <= 3) {
    return null
  }

  const displayLoading = loading ? <div style={{ float: 'right' }}><Spinner /></div> : null
  const showEmptyResults = helpResult.length === 0 && !loading
  const searchResults = helpResult.map((item, index) => (
    <DisplayResultItem item={item} key={index} />
  ))
  const displayError = error

  return (
    <div data-test='component-search-results' id='search-results'>
      <div id='dashboard_primary' className='metabox-holder'>
        <div id='documentation-api' className='postbox' style={{ display: 'block' }}>
          <h3 className='hndle'>
            <span>{GFPDF.searchResultHeadingText}</span>
            {displayLoading}
          </h3>

          <div style={{ display: 'block' }}>
            <ul className='searchParseHTML'>
              {searchResults}
              {showEmptyResults && !displayError && <DisplayResultEmpty />}
              {!!displayError && <DisplayError displayError={displayError} />}
            </ul>
          </div>
        </div>
      </div>
    </div>
  )
}

/**
 *
 * @since 5.2
 */
DisplayResultContainer.propTypes = {
  searchInput: PropTypes.string,
  loading: PropTypes.bool,
  helpResult: PropTypes.array,
  error: PropTypes.string
}

export default DisplayResultContainer
