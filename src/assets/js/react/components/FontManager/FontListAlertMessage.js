/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
/* Redux actions */
import { getCustomFontList, resetSearchResult } from '../../actions/fontManager'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display alert message for font list UI
 *
 * @param empty
 * @param error
 * @param getCustomFontList
 * @param resetSearchResult
 *
 * @since 6.0
 */
export const FontListAlertMessage = ({ empty, error, getCustomFontList, resetSearchResult }) => {
  const fontListEmpty = <span>{GFPDF.fontListEmpty}</span>
  const searchResultEmpty = (
    <span>
      {GFPDF.searchResultEmpty} <span className='link' onClick={resetSearchResult}>Clear Search.</span>
    </span>
  )
  const apiError = <p className='link' onClick={getCustomFontList}>{error}</p>
  const displayContent = empty ? fontListEmpty : !error ? searchResultEmpty : apiError

  return (
    <div data-test='component-FontListAlertMessage' className='alert-message'>
      {displayContent}
    </div>
  )
}

/**
 * PropTypes
 *
 * @since 6.0
 */
FontListAlertMessage.propTypes = {
  empty: PropTypes.bool,
  error: PropTypes.string,
  getCustomFontList: PropTypes.func.isRequired,
  resetSearchResult: PropTypes.func.isRequired
}

/**
 * Connect and dispatch redux actions as props
 *
 * @since 6.0
 */
export default connect(null, {
  getCustomFontList,
  resetSearchResult
})(FontListAlertMessage)
