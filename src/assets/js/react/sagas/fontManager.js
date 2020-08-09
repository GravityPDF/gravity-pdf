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
  ADD_FONT,
  ADD_FONT_SUCCESS,
  EDIT_FONT,
  EDIT_FONT_SUCCESS,
  DELETE_FONT,
  DELETE_FONT_SUCCESS
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

    yield put({ type: GET_CUSTOM_FONT_LIST_SUCCESS, payload: responseBody })
  } catch (error) {
    console.log('saga getCustomFontList error - ', error)
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

    yield put({ type: ADD_FONT_SUCCESS, payload: responseBody })
  } catch (error) {
    console.log('saga addFont error - ', error)
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

    yield put({ type: EDIT_FONT_SUCCESS })
  } catch (error) {
    console.log('Saga editFont error - ', error)
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

    yield put({ type: DELETE_FONT_SUCCESS, payload: payload })
  } catch (error) {
    console.log('Saga deleteFont error - ', error)
  }
}
