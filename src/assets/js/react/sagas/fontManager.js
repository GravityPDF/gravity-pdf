import { call, put, takeLatest } from 'redux-saga/effects'
import {
  apiGetCustomFontList,
  apiAddFont,
  apiEditFont,
  apiDeleteFont
} from '../api/fontManager'
import {
  GET_CUSTOM_FONT_LIST,
  GET_CUSTOM_FONT_LIST_SUCCESS,
  GET_CUSTOM_FONT_LIST_ERROR,
  ADD_FONT,
  ADD_FONT_SUCCESS,
  ADD_FONT_ERROR,
  EDIT_FONT,
  EDIT_FONT_SUCCESS,
  EDIT_FONT_ERROR,
  DELETE_FONT,
  DELETE_FONT_SUCCESS,
  DELETE_FONT_ERROR
} from '../actions/fontManager'

export function * watchGetCustomFontList () {
  yield takeLatest(GET_CUSTOM_FONT_LIST, getCustomFontList)
}

export function * getCustomFontList () {
  try {
    const response = yield call(apiGetCustomFontList)

    if (!response.ok) {
      throw response
    }

    const responseBody = yield response.json()

    yield put({
      type: GET_CUSTOM_FONT_LIST_SUCCESS,
      payload: responseBody
    })
  } catch (error) {
    yield put({
      type: GET_CUSTOM_FONT_LIST_ERROR,
      payload: GFPDF.addFatalError
    })
  }
}

export function * watchAddFont () {
  yield takeLatest(ADD_FONT, addFont)
}

export function * addFont ({ payload }) {
  try {
    const response = yield call(apiAddFont, payload)

    if (!response.ok) {
      throw response
    }

    const responseBody = yield response.json()

    const data = {
      font: responseBody,
      msg: '<strong>' + GFPDF.addUpdateFontSuccess + '</strong>'
    }

    yield put({
      type: ADD_FONT_SUCCESS,
      payload: data
    })
  } catch (error) {
    if (error.status === 500) {
      return yield put({
        type: ADD_FONT_ERROR,
        payload: GFPDF.addFatalError
      })
    }

    const response = yield error.json()

    if (error.status === 400 && response.code === 'font_validation_error') {
      return yield put({
        type: ADD_FONT_ERROR,
        payload: { fontValidationError: GFPDF.fontFileInvalid, msg: response.message }
      })
    }

    yield put({
      type: ADD_FONT_ERROR,
      payload: response.message
    })
  }
}

export function * watchEditFont () {
  yield takeLatest(EDIT_FONT, editFont)
}

export function * editFont ({ payload }) {
  try {
    const response = yield call(apiEditFont, payload)

    if (!response.ok) {
      throw response
    }

    const responseBody = yield response.json()

    const data = {
      font: responseBody,
      msg: '<strong>' + GFPDF.addUpdateFontSuccess + '</strong>'
    }

    yield put({
      type: EDIT_FONT_SUCCESS,
      payload: data
    })
  } catch (error) {
    const response = yield error.json()

    if (error.status === 500 && response.code !== 'font_file_gone_missing') {
      return yield put({
        type: EDIT_FONT_ERROR,
        payload: GFPDF.addFatalError
      })
    }

    if (error.status === 400 && response.code === 'font_validation_error') {
      return yield put({
        type: EDIT_FONT_ERROR,
        payload: { fontValidationError: GFPDF.fontFileInvalid, msg: response.message }
      })
    }

    yield put({
      type: EDIT_FONT_ERROR,
      payload: response.message === '' ? GFPDF.addFatalError : response.message
    })
  }
}

export function * watchDeleteFont () {
  yield takeLatest(DELETE_FONT, deleteFont)
}

export function * deleteFont ({ payload }) {
  try {
    const response = yield call(apiDeleteFont, payload)

    if (!response.ok) {
      throw response
    }

    yield put({
      type: DELETE_FONT_SUCCESS,
      payload
    })
  } catch (error) {
    yield put({
      type: DELETE_FONT_ERROR,
      payload: GFPDF.addFatalError
    })
  }
}
