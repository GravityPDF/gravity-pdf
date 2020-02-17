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

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (c) 2020, Blue Liquid Designs

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Found
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
