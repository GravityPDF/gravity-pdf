import { call, put, takeLatest } from 'redux-saga/effects'
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
  TEMPLATE_UPLOAD_PROCESSING_FAILED
} from '../../../../src/assets/js/react/actions/templates'
import * as api from '../../../../src/assets/js/react/api/templates'

describe('Sagas templates -', () => {

  describe('watchUpdateSelectBox()', () => {
    const gen = watchUpdateSelectBox()

    it('should check the watcher to loads up the updateSelectBox function and call UPDATE_SELECT_BOX action', () => {
      expect(gen.next().value).to.deep.eql(takeLatest(UPDATE_SELECT_BOX, updateSelectBox))
    })
  })

  describe('updateSelectBox()', () => {
    const gen = updateSelectBox()

    it('should check that saga asks to call the API for updateSelectBox', () => {
      expect(gen.next().value).to.deep.eql(call(api.apiPostUpdateSelectBox))
    })

    it('should check that saga handles correctly to the failure of updateSelectBox API call', () => {
      expect(gen.throw().value).to.deep.eql(put({
        type: UPDATE_SELECT_BOX_FAILED
      }))
    })
  })

  describe('watchTemplateProcessing()', () => {
    const gen = watchTemplateProcessing()

    it('should check the watcher to loads up the templateProcessing function and call TEMPLATE_PROCESSING action', () => {
      expect(gen.next().value).to.deep.eql(takeLatest(TEMPLATE_PROCESSING, templateProcessing))
    })
  })

  describe('templateProcessing()', () => {
    const action = { payload: 'data' }
    const gen = templateProcessing(action)

    it('should check that saga asks to call the API for templateProcessing', () => {
      expect(gen.next().value).to.deep.eql(call(api.apiPostTemplateProcessing, action.payload))
    })

    it('should check that saga handles correctly to the failure of templateProcessing API call', () => {
      expect(gen.throw('failed').value).to.deep.eql(put({
        type: TEMPLATE_PROCESSING_FAILED,
        payload: 'failed'
      }))
    })
  })

  describe('watchpostTemplateUploadProcessing()', () => {
    const gen = watchpostTemplateUploadProcessing()

    it('should check the watcher to loads up the templateUploadProcessing function and call POST_TEMPLATE_UPLOAD_PROCESSING action', () => {
      expect(gen.next().value).to.deep.eql(takeLatest(POST_TEMPLATE_UPLOAD_PROCESSING, templateUploadProcessing))
    })
  })

  describe('templateUploadProcessing()', () => {
    const newaction = { payload: { file: { data: 'test' }, filename: 'test' } }
    const gen = templateUploadProcessing(newaction)

    it('should check that saga asks to call the API for templateUploadProcessing', () => {
      expect(gen.next().value).to.deep.eql(call(api.apiPostTemplateUploadProcessing, newaction.payload.file, newaction.payload.filename))
    })

    it('should check that saga handles correctly to the failure of templateUploadProcessing API call', () => {
      expect(gen.throw({ error: 'template upload processing failed' }).value).to.deep.eql(put({
        type: TEMPLATE_UPLOAD_PROCESSING_FAILED,
        payload: { error: 'template upload processing failed' }
      }))
    })
  })
})
