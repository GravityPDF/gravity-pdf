import { channel } from 'redux-saga'
import { actionChannel, call, put, take, takeLatest } from 'redux-saga/effects'
import {
  watchUpdateSelectBox,
  watchTemplateProcessing,
  watchpostTemplateUploadProcessing,
  updateSelectBox,
  templateProcessing,
  templateUploadProcessing
} from '../../../../src/assets/js/react/sagas/templates'
import {
  UPDATE_SELECT_BOX,
  UPDATE_SELECT_BOX_FAILED,
  TEMPLATE_PROCESSING,
  TEMPLATE_PROCESSING_FAILED,
  POST_TEMPLATE_UPLOAD_PROCESSING,
  TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
  TEMPLATE_UPLOAD_PROCESSING_FAILED
} from '../../../../src/assets/js/react/actions/templates'
import * as api from '../../../../src/assets/js/react/api/templates'

describe('Sagas - templates', () => {

  describe('watchUpdateSelectBox()', () => {
    const gen = watchUpdateSelectBox()

    test('should check the watcher to loads up the updateSelectBox function and call UPDATE_SELECT_BOX action', () => {
      expect(gen.next().value).toEqual(takeLatest(UPDATE_SELECT_BOX, updateSelectBox))
    })
  })

  describe('updateSelectBox()', () => {
    const gen = updateSelectBox()

    test('should check that saga asks to call the API for updateSelectBox', () => {
      expect(gen.next().value).toEqual(call(api.apiPostUpdateSelectBox))
    })

    test('should check that saga handles correctly to the failure of updateSelectBox API call', () => {
      expect(gen.throw().value).toEqual(put({
        type: UPDATE_SELECT_BOX_FAILED
      }))
    })
  })

  describe('watchTemplateProcessing()', () => {
    const gen = watchTemplateProcessing()

    test('should check the watcher to loads up the templateProcessing function and call TEMPLATE_PROCESSING action', () => {
      expect(gen.next().value).toEqual(takeLatest(TEMPLATE_PROCESSING, templateProcessing))
    })
  })

  describe('templateProcessing()', () => {
    const action = { payload: 'data' }
    const gen = templateProcessing(action)

    test('should check that saga asks to call the API for templateProcessing', () => {
      expect(gen.next().value).toEqual(call(api.apiPostTemplateProcessing, action.payload))
    })

    test('should check that saga handles correctly to the failure of templateProcessing API call', () => {
      expect(gen.throw('failed').value).toEqual(put({
        type: TEMPLATE_PROCESSING_FAILED,
        payload: 'failed'
      }))
    })
  })

  describe('watchpostTemplateUploadProcessing()', () => {
    test('should queue every POST_TEMPLATE_UPLOAD_PROCESSING action and upload them one at a time', () => {
      const gen = watchpostTemplateUploadProcessing()
      const uploads = channel()
      const action = { payload: { file: {}, filename: 'one.zip' } }

      expect(gen.next().value).toEqual(actionChannel(POST_TEMPLATE_UPLOAD_PROCESSING))
      expect(gen.next(uploads).value).toEqual(take(uploads))
      expect(gen.next(action).value).toEqual(call(templateUploadProcessing, action))
      expect(gen.next().value).toEqual(take(uploads))
    })
  })

  describe('templateUploadProcessing()', () => {
    const action = { payload: { file: { data: 'test' }, filename: 'test', id: 7 } }

    const failedWith = (message) => put({
      type: TEMPLATE_UPLOAD_PROCESSING_FAILED,
      payload: { id: 7, message }
    })

    test('should check that saga asks to call the API for templateUploadProcessing', () => {
      const gen = templateUploadProcessing(action)

      expect(gen.next().value).toEqual(call(api.apiPostTemplateUploadProcessing, action.payload.file, action.payload.filename))
    })

    test('should route to success when the API responds with ok and a templates array', () => {
      const gen = templateUploadProcessing(action)
      gen.next()

      const response = { ok: true, status: 200, body: { templates: [{ id: 'foo' }] } }
      expect(gen.next(response).value).toEqual(put({
        type: TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
        payload: { id: 7, templates: [{ id: 'foo' }] }
      }))
      expect(gen.next().done).toBe(true)
    })

    test('should route to failed when the API responds with a non-ok status', () => {
      const gen = templateUploadProcessing(action)
      gen.next()

      const response = { ok: false, status: 400, body: { error: 'invalid zip' } }
      expect(gen.next(response).value).toEqual(failedWith('invalid zip'))
    })

    test('should fall back to the generic error when the API response is missing a templates array', () => {
      const gen = templateUploadProcessing(action)
      gen.next()

      const response = { ok: true, status: 200, body: 400 }
      expect(gen.next(response).value).toEqual(failedWith(GFPDF.problemWithTheUpload))
    })

    test('should route to failed when the fetch itself throws', () => {
      const gen = templateUploadProcessing(action)
      gen.next()

      expect(gen.throw({ message: 'network failure' }).value).toEqual(failedWith('network failure'))
    })
  })
})
