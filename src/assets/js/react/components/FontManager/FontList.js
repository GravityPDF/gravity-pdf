/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
/* Components */
import FontListHeader from './FontListHeader'
import FontListItems from './FontListItems'
import FontListSkeleton from './FontListSkeleton'
import FontListAlertMessage from './FontListAlertMessage'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display font list UI
 *
 * @param id
 * @param loading
 * @param fontList
 * @param searchResult
 * @param error
 * @param history
 * @returns {JSX.Element}
 *
 * @since 6.0
 */
const FontList = ({ id, loading, fontList, searchResult, msg: { error }, history }) => {
  const fontListError = error && error.fontList
  const fontListEmpty = fontList.length === 0 && !searchResult
  const checkSearchResult = (searchResult && searchResult.length === 0) || !searchResult
  const latestData = fontList.length > 0 && !searchResult
  const emptySearchResult = (!fontListError && !loading) && (!latestData && checkSearchResult)

  return (
    <div className='font-list'>
      <FontListHeader />

      {loading ? <FontListSkeleton /> : <FontListItems id={id} history={history} />}

      {fontListEmpty && emptySearchResult && <FontListAlertMessage empty={fontListEmpty} />}

      {!fontListEmpty && emptySearchResult && <FontListAlertMessage />}

      {fontListError && <FontListAlertMessage error={error.fontList} />}
    </div>
  )
}

/**
 * Map redux state to props
 *
 * @param state: object
 *
 * @returns {{
 *   loading: boolean,
 *   fontList: array of object,
 *   searchResult: (null || array of object),
 *   msg: object,
 * }}
 *
 * @since 6.0
 */
const mapStateToProps = state => ({
  loading: state.fontManager.loading,
  fontList: state.fontManager.fontList,
  searchResult: state.fontManager.searchResult,
  msg: state.fontManager.msg
})

/**
 * PropTypes
 *
 * @since 6.0
 */
FontList.propTypes = {
  id: PropTypes.string,
  loading: PropTypes.bool.isRequired,
  fontList: PropTypes.arrayOf(PropTypes.object).isRequired,
  searchResult: PropTypes.oneOfType([
    PropTypes.oneOf([null]).isRequired,
    PropTypes.arrayOf(PropTypes.object).isRequired
  ]),
  msg: PropTypes.object.isRequired,
  history: PropTypes.object.isRequired
}

export default connect(mapStateToProps, {})(FontList)
