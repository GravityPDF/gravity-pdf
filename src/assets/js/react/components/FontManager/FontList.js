import React from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import FontListHeader from './FontListHeader'
import FontListItems from './FontListItems'
import FontListSkeleton from './FontListSkeleton'
import FontListAlertMessage from './FontListAlertMessage'

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

const mapStateToProps = state => ({
  loading: state.fontManager.loading,
  fontList: state.fontManager.fontList,
  searchResult: state.fontManager.searchResult,
  msg: state.fontManager.msg
})

FontList.propTypes = {
  id: PropTypes.string,
  loading: PropTypes.bool.isRequired,
  fontList: PropTypes.arrayOf(PropTypes.object).isRequired,
  searchResult: PropTypes.array,
  msg: PropTypes.object.isRequired,
  history: PropTypes.object.isRequired
}

export default connect(mapStateToProps, {})(FontList)
