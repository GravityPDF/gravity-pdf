import { channel } from 'redux-saga'
import { call, fork, take, takeLatest, put } from 'redux-saga/effects'
import {
  getFilesFromGitHubSuccess,
  getFilesFromGitHubFailed,
  addToConsole,
  addToRetryList,
  currentDownload,
  GET_FILES_FROM_GITHUB,
  DOWNLOAD_FONTS_API_CALL
} from '../actions/coreFonts'
import { apiGetFilesFromGitHub, apiPostDownloadFonts } from '../api/coreFonts'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Worker Saga getFilesFromGitHub - Success and error handling of AJAX call
 *
 * @since 5.2
 */
export function * getFilesFromGitHub () {
  try {
    const response = yield call(apiGetFilesFromGitHub)
    yield put(getFilesFromGitHubSuccess(response.body))
  } catch (error) {
    yield put(getFilesFromGitHubFailed(GFPDF.coreFontGithubError))
  }
}

/**
 * Worker Saga getDownloadFonts - Success and error handling of AJAX call
 *
 * @param chan
 *
 * @since 5.2
 */
export function * getDownloadFonts (chan) {
  while (true) {
    const payload = yield take(chan)

    /**
     * Add pending message to console
     *
     * @param string name The Font Name
     *
     * @since 5.0
     */
    yield put(addToConsole(payload, 'pending', GFPDF.coreFontItemPendingMessage.replace('%s', payload)))

    /**
     * Add success message to console
     *
     * @param string name The Font Name
     *
     * @since 5.2
     */
    try {
      const response = yield call(apiPostDownloadFonts, payload)

      if (!response.body) {
        throw true
      }

      yield put(addToConsole(payload, 'success', GFPDF.coreFontItemSuccessMessage.replace('%s', payload)))
    } catch (error) {
      /**
       * Add error message to console
       *
       * @param string name The Font Name
       *
       * @since 5.2
       */
      yield put(addToConsole(payload, 'error', GFPDF.coreFontItemErrorMessage.replace('%s', payload)))
      yield put(addToRetryList(payload))
    } finally {
      /**
       * Finally request data into our Redux store for showing the download completed status
       *
       * @since 5.2
       */
      yield put(currentDownload())
    }
  }
}

/**
 * Watcher Saga watchGetFilesFromGitHub for getFilesFromGitHub()
 *
 * @since 5.2
 */
export function * watchGetFilesFromGitHub () {
  yield takeLatest(GET_FILES_FROM_GITHUB, getFilesFromGitHub)
}

/**
 * Watcher Saga watchDownloadFonts for getDownloadFonts()
 *
 * @since 5.2
 */
export function * watchDownloadFonts () {
  const chan = yield call(channel)

  for (let i = 0; i < 5; i++) {
    yield fork(getDownloadFonts, chan)
  }

  while (true) {
    const {payload} = yield take(DOWNLOAD_FONTS_API_CALL)
    yield put(chan, payload)
  }
}
