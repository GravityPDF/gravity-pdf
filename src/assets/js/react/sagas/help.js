import { takeLatest, call, put } from 'redux-saga/effects'
import {
  updateResult,
  updateError,
  GET_DATA
} from '../actions/help'
import { apiGetSearchResult } from '../api/help'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Worker Saga getResults - Success and error handling of AJAX call
 *
 * @param action
 *
 * @since 5.2
 */
export function * getResults (action) {
  try {
    const result = yield call(apiGetSearchResult, action.payload)
    yield put(updateResult(result.body))
  } catch (error) {
    yield put(updateError(GFPDF.getSearchResultError))
  }
}

/**
 * Watcher Saga watchGetResults for getResults()
 *
 * @since 5.2
 */
export function * watchGetResults () {
  yield takeLatest(GET_DATA, getResults)
}
