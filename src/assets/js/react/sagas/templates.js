/* Dependencies */
import { takeLatest, actionChannel, take, call, put } from 'redux-saga/effects'
/* Redux action types & actions */
import {
  updateSelectBoxSuccess,
  updateSelectBoxFailed,
  templateProcessingSuccess,
  templateProcessingFailed,
  templateUploadProcessingSuccess,
  templateUploadProcessingFailed,
  UPDATE_SELECT_BOX,
  TEMPLATE_PROCESSING,
  POST_TEMPLATE_UPLOAD_PROCESSING
} from '../actions/templates'
/* APIs */
import { apiPostUpdateSelectBox, apiPostTemplateProcessing, apiPostTemplateUploadProcessing } from '../api/templates'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Worker Saga updateSelectBox - Success and error handling of AJAX call
 *
 * @since 5.2
 */
export function * updateSelectBox () {
  try {
    const response = yield call(apiPostUpdateSelectBox)
    yield put(updateSelectBoxSuccess(response.text))
  } catch (error) {
    yield put(updateSelectBoxFailed())
  }
}

/**
 * Worker Saga templateProcessing - Success and error handling of AJAX call
 *
 * @param {Object} action
 *
 * @since 5.2
 */
export function * templateProcessing (action) {
  try {
    yield call(apiPostTemplateProcessing, action.payload)
    yield put(templateProcessingSuccess('success'))
  } catch (error) {
    yield put(templateProcessingFailed('failed'))
  }
}

/**
 * Worker Saga templateUploadProcessing - Success and error handling of AJAX call
 *
 * @param {Object} action
 *
 * @since 5.2
 */
export function * templateUploadProcessing (action) {
  const { file, filename, id } = action.payload
  let message

  try {
    const response = yield call(apiPostTemplateUploadProcessing, file, filename)

    if (response.ok && Array.isArray(response.body?.templates)) {
      yield put(templateUploadProcessingSuccess(id, response.body.templates))
      return
    }

    message = response.body?.error
  } catch (error) {
    message = error.message
  }

  yield put(templateUploadProcessingFailed(id, message || GFPDF.problemWithTheUpload))
}

/**
 * Watcher Saga watchUpdateSelectBox for updateSelectBox()
 *
 * @since 5.2
 */
export function * watchUpdateSelectBox () {
  yield takeLatest(UPDATE_SELECT_BOX, updateSelectBox)
}

/**
 * Watcher Saga watchTemplateProcessing for templateProcessing()
 *
 * @since 5.2
 */
export function * watchTemplateProcessing () {
  yield takeLatest(TEMPLATE_PROCESSING, templateProcessing)
}

/**
 * Watcher Saga watchpostTemplateUploadProcessing for templateUploadProcessing()
 *
 * Uploads one zip at a time, so a large drop doesn't tie up every PHP worker
 *
 * @since 5.2
 */
export function * watchpostTemplateUploadProcessing () {
  const uploads = yield actionChannel(POST_TEMPLATE_UPLOAD_PROCESSING)

  while (true) {
    yield call(templateUploadProcessing, yield take(uploads))
  }
}
