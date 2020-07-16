import {
  SEARCH_TEMPLATES,
  SELECT_TEMPLATE,
  ADD_TEMPLATE,
  UPDATE_TEMPLATE_PARAM,
  DELETE_TEMPLATE,
  UPDATE_SELECT_BOX_SUCCESS,
  TEMPLATE_PROCESSING_SUCCESS,
  TEMPLATE_PROCESSING_FAILED,
  CLEAR_TEMPLATE_PROCESSING,
  TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
  TEMPLATE_UPLOAD_PROCESSING_FAILED,
  CLEAR_TEMPLATE_UPLOAD_PROCESSING
} from '../../../../src/assets/js/react/actions/templates'
import reducer, { initialState } from '../../../../src/assets/js/react/reducers/templateReducer'

describe('Reducers - templateReducer', () => {

  let newState

  describe('SEARCH_TEMPLATES', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: SEARCH_TEMPLATES, text: 'New Search Item' })

      expect(newState.search).toBe('New Search Item')

      newState = reducer(newState, { type: SEARCH_TEMPLATES, text: 'Another Search Item' })

      expect(newState.search).toBe('Another Search Item')
    })
  })

  describe('SELECT_TEMPLATE', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: SELECT_TEMPLATE, id: 'template-id' })

      expect(newState.activeTemplate).toBe('template-id')

      newState = reducer(newState, { type: SELECT_TEMPLATE, id: 'new-template-id' })

      expect(newState.activeTemplate).toBe('new-template-id')
    })
  })

  describe('ADD_TEMPLATE', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: ADD_TEMPLATE, template: { id: 'template-id' } })

      expect(newState.list.length).toBe(4)

      newState = reducer(newState, { type: ADD_TEMPLATE, template: { id: 'template-id1' } })

      expect(newState.list.length).toBe(5)
    })
  })

  describe('UPDATE_TEMPLATE_PARAM', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, {
        type: UPDATE_TEMPLATE_PARAM,
        id: 'zadani',
        name: 'owner',
        value: 'Wilson'
      })

      expect(newState.list[0].owner).toBe('Wilson')

      newState = reducer(initialState, { type: UPDATE_TEMPLATE_PARAM, id: 'zadani', name: 'owner', value: 'Billy' })

      expect(newState.list[0].owner).toBe('Billy')
    })
  })

  describe('DELETE_TEMPLATE', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: DELETE_TEMPLATE, id: 'zadani' })

      expect(newState.list.length).toBe(2)

      newState = reducer(newState, { type: DELETE_TEMPLATE, id: 'rubix' })

      expect(newState.list.length).toBe(1)
    })
  })

  describe('UPDATE_SELECT_BOX_SUCCESS', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: UPDATE_SELECT_BOX_SUCCESS, payload: 'data' })

      expect(newState.updateSelectBoxText).toBe('data')

      newState = reducer(newState, { type: UPDATE_SELECT_BOX_SUCCESS, payload: 'new-data' })

      expect(newState.updateSelectBoxText).toBe('new-data')
    })
  })

  describe('TEMPLATE_PROCESSING_SUCCESS', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: TEMPLATE_PROCESSING_SUCCESS, payload: 'data' })

      expect(newState.templateProcessing).toBe('data')

      newState = reducer(newState, { type: TEMPLATE_PROCESSING_SUCCESS, payload: 'new-data' })

      expect(newState.templateProcessing).toBe('new-data')
    })
  })

  describe('TEMPLATE_PROCESSING_FAILED', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: TEMPLATE_PROCESSING_FAILED, payload: 'data' })

      expect(newState.templateProcessing).toBe('data')

      newState = reducer(newState, { type: TEMPLATE_PROCESSING_FAILED, payload: 'new-data' })

      expect(newState.templateProcessing).toBe('new-data')
    })
  })

  describe('CLEAR_TEMPLATE_PROCESSING', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: CLEAR_TEMPLATE_PROCESSING })

      expect(newState.templateProcessing).toBe('')

      newState = reducer(newState, { type: CLEAR_TEMPLATE_PROCESSING })

      expect(newState.templateProcessing).toBe('')
    })
  })

  describe('TEMPLATE_UPLOAD_PROCESSING_SUCCESS', () => {

    test('check the correct state gets returned when this action runs', () => {
      let test = { data: 'test' }
      let newtest = { newtest: 'new-test' }
      newState = reducer(initialState, { type: TEMPLATE_UPLOAD_PROCESSING_SUCCESS, payload: test })

      expect(newState.templateUploadProcessingSuccess).toBe(test)

      newState = reducer(newState, { type: TEMPLATE_UPLOAD_PROCESSING_SUCCESS, payload: newtest })

      expect(newState.templateUploadProcessingSuccess).toBe(newtest)
    })
  })

  describe('TEMPLATE_UPLOAD_PROCESSING_FAILED', () => {

    test('check the correct state gets returned when this action runs', () => {
      let error = { error: 'error' }
      let newerror = { newerror: 'newerror' }
      newState = reducer(initialState, { type: TEMPLATE_UPLOAD_PROCESSING_FAILED, payload: error })

      expect(newState.templateUploadProcessingError).toBe(error)

      newState = reducer(newState, { type: TEMPLATE_UPLOAD_PROCESSING_FAILED, payload: newerror })

      expect(newState.templateUploadProcessingError).toBe(newerror)
    })
  })

  describe('CLEAR_TEMPLATE_UPLOAD_PROCESSING', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: CLEAR_TEMPLATE_UPLOAD_PROCESSING })

      expect(newState.templateUploadProcessingSuccess).toEqual({})
      expect(newState.templateUploadProcessingError).toEqual({})

      newState = reducer(newState, { type: CLEAR_TEMPLATE_UPLOAD_PROCESSING })

      expect(newState.templateUploadProcessingSuccess).toEqual({})
      expect(newState.templateUploadProcessingError).toEqual({})
    })
  })

  describe('Check state gets returned when no actions match', () => {

    test('Check the state does not change when no action matches', () => {
      let state = reducer(undefined, { type: 'none', id: 'template-id' })

      expect(state).toBe(initialState)
    })
  })
})
