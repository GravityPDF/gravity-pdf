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

describe('Reducers templateReducer - ', () => {

  describe('SEARCH_TEMPLATES', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, {type: SEARCH_TEMPLATES, text: 'New Search Item'})

      expect(newState.search).is.equal('New Search Item')

      newState = reducer(newState, {type: SEARCH_TEMPLATES, text: 'Another Search Item'})

      expect(newState.search).is.equal('Another Search Item')
    })
  })

  describe('SELECT_TEMPLATE', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, {type: SELECT_TEMPLATE, id: 'template-id'})

      expect(newState.activeTemplate).is.equal('template-id')

      newState = reducer(newState, {type: SELECT_TEMPLATE, id: 'new-template-id'})

      expect(newState.activeTemplate).is.equal('new-template-id')
    })
  })

  describe('ADD_TEMPLATE', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, {type: ADD_TEMPLATE, template: {id: 'template-id'}})

      expect(newState.list.length).is.equal(4)

      newState = reducer(newState, {type: ADD_TEMPLATE, template: {id: 'template-id1'}})

      expect(newState.list.length).is.equal(5)
    })
  })

  describe('UPDATE_TEMPLATE_PARAM', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, {type: UPDATE_TEMPLATE_PARAM, id: 'zadani', name: 'owner', value: 'Wilson'})

      expect(newState.list[0].owner).is.equal('Wilson')

      newState = reducer(initialState, {type: UPDATE_TEMPLATE_PARAM, id: 'zadani', name: 'owner', value: 'Billy'})

      expect(newState.list[0].owner).is.equal('Billy')
    })
  })

  describe('DELETE_TEMPLATE', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, {type: DELETE_TEMPLATE, id: 'zadani'})

      expect(newState.list.length).is.equal(2)

      newState = reducer(newState, {type: DELETE_TEMPLATE, id: 'rubix'})

      expect(newState.list.length).is.equal(1)
    })
  })

  describe('UPDATE_SELECT_BOX_SUCCESS', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, {type: UPDATE_SELECT_BOX_SUCCESS, payload: 'data'})

      expect(newState.updateSelectBoxText).is.equal('data')

      newState = reducer(newState, {type: UPDATE_SELECT_BOX_SUCCESS, payload: 'new-data'})

      expect(newState.updateSelectBoxText).is.equal('new-data')
    })
  })

  describe('TEMPLATE_PROCESSING_SUCCESS', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, {type: TEMPLATE_PROCESSING_SUCCESS, payload: 'data'})

      expect(newState.templateProcessing).is.equal('data')

      newState = reducer(newState, {type: TEMPLATE_PROCESSING_SUCCESS, payload: 'new-data'})

      expect(newState.templateProcessing).is.equal('new-data')
    })
  })

  describe('TEMPLATE_PROCESSING_FAILED', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, {type: TEMPLATE_PROCESSING_FAILED, payload: 'data'})

      expect(newState.templateProcessing).is.equal('data')

      newState = reducer(newState, {type: TEMPLATE_PROCESSING_FAILED, payload: 'new-data'})

      expect(newState.templateProcessing).is.equal('new-data')
    })
  })

  describe('CLEAR_TEMPLATE_PROCESSING', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, {type: CLEAR_TEMPLATE_PROCESSING})

      expect(newState.templateProcessing).is.equal('')

      newState = reducer(newState, {type: CLEAR_TEMPLATE_PROCESSING})

      expect(newState.templateProcessing).is.equal('')
    })
  })

  describe('TEMPLATE_UPLOAD_PROCESSING_SUCCESS', () => {
    it('check the correct state gets returned when this action runs', () => {
      let test = {data: 'test'}
      let newtest = {newtest: 'new-test'}
      let newState = reducer(initialState, {type: TEMPLATE_UPLOAD_PROCESSING_SUCCESS, payload: test})

      expect(newState.templateUploadProcessingSuccess).is.equal(test)

      newState = reducer(newState, {type: TEMPLATE_UPLOAD_PROCESSING_SUCCESS, payload: newtest})

      expect(newState.templateUploadProcessingSuccess).is.equal(newtest)
    })
  })

  describe('TEMPLATE_UPLOAD_PROCESSING_FAILED', () => {
    it('check the correct state gets returned when this action runs', () => {
      let error = {error: 'error'}
      let newerror = {newerror: 'newerror'}
      let newState = reducer(initialState, {type: TEMPLATE_UPLOAD_PROCESSING_FAILED, payload: error})

      expect(newState.templateUploadProcessingError).is.equal(error)

      newState = reducer(newState, {type: TEMPLATE_UPLOAD_PROCESSING_FAILED, payload: newerror})

      expect(newState.templateUploadProcessingError).is.equal(newerror)
    })
  })

  describe('CLEAR_TEMPLATE_UPLOAD_PROCESSING', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, {type: CLEAR_TEMPLATE_UPLOAD_PROCESSING})

      expect(newState.templateUploadProcessingSuccess).to.be.eql({})
      expect(newState.templateUploadProcessingError).to.be.eql({})

      newState = reducer(newState, {type: CLEAR_TEMPLATE_UPLOAD_PROCESSING})

      expect(newState.templateUploadProcessingSuccess).to.be.eql({})
      expect(newState.templateUploadProcessingError).to.be.eql({})
    })
  })

  describe('Check state gets returned when no actions match', () => {
    it('Check the state does not change when no action matches', () => {
      let state = reducer(undefined, {type: 'none', id: 'template-id'})

      expect(state).is.equal(initialState)
    })
  })
})
