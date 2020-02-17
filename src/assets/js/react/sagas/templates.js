import { takeLatest, call, put } from 'redux-saga/effects'
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
import { apiPostUpdateSelectBox, apiPostTemplateProcessing, apiPostTemplateUploadProcessing } from '../api/templates'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
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
  try {
    const response = yield call(apiPostTemplateUploadProcessing, action.payload.file, action.payload.filename)
    yield put(templateUploadProcessingSuccess(response))
  } catch (error) {
    yield put(templateUploadProcessingFailed(error))
  }
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
 * Watcher Saga watchTemplateProcessing for templateUploadProcessing()
 *
 * @since 5.2
 */
export function * watchpostTemplateUploadProcessing () {
  yield takeLatest(POST_TEMPLATE_UPLOAD_PROCESSING, templateUploadProcessing)
}
