import {
  ADD_TO_CONSOLE,
  ADD_TO_RETRY_LIST,
  CLEAR_BUTTON_CLICKED_AND_RETRY_LIST,
  CLEAR_CONSOLE,
  GET_FILES_FROM_GITHUB,
  GET_FILES_FROM_GITHUB_SUCCESS,
  GET_FILES_FROM_GITHUB_FAILED,
  CLEAR_REQUEST_REMAINING_DATA,
  REQUEST_SENT_COUNTER
} from '../../../../../src/assets/js/react/actions/coreFonts'
import reducer, { initialState } from '../../../../../src/assets/js/react/reducers/coreFontReducer'

describe('Reducers coreFontReducer - ', () => {

  describe('ADD_TO_CONSOLE', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, {
        type: ADD_TO_CONSOLE,
        key: 'key 1',
        status: 'status 1',
        message: 'Message 1'
      })

      expect(newState.console['key 1'].status).is.equal('status 1')

      newState = reducer(newState, { type: ADD_TO_CONSOLE, key: 'key 2', status: 'status 2', message: 'Message 2' })
      newState = reducer(newState, { type: ADD_TO_CONSOLE, key: 'key 3', status: 'status 3', message: 'Message 3' })

      expect(newState.console['key 2'].status).is.equal('status 2')
      expect(Object.keys(newState.console).length).is.equal(3)
    })

    it('verify state is immutable', () => {
      let state = reducer(initialState, {
        type: ADD_TO_CONSOLE,
        key: 'key 1',
        status: 'status 1',
        message: 'Message 1'
      })
      let newState = reducer(state, { type: ADD_TO_CONSOLE, key: 'key 1', status: 'status 1', message: 'Message 1' })

      expect(newState).is.not.equal(state)
    })
  })

  describe('CLEAR_CONSOLE', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, {
        type: ADD_TO_CONSOLE,
        key: 'key 1',
        status: 'status 1',
        message: 'Message 1'
      })
      newState = reducer(newState, { type: ADD_TO_CONSOLE, key: 'key 2', status: 'status 2', message: 'Message 2' })
      newState = reducer(newState, { type: ADD_TO_CONSOLE, key: 'key 3', status: 'status 3', message: 'Message 3' })
      newState = reducer(newState, { type: CLEAR_CONSOLE })

      expect(Object.keys(newState.console).length).is.equal(0)
    })
  })

  describe('ADD_TO_RETRY_LIST', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, { type: ADD_TO_RETRY_LIST, name: 'Font 1' })

      expect(newState.retry.length).is.equal(1)

      newState = reducer(newState, { type: ADD_TO_RETRY_LIST, name: 'Font 2' })
      newState = reducer(newState, { type: ADD_TO_RETRY_LIST, name: 'Font 3' })

      expect(Object.keys(newState.retry).length).is.equal(3)

      newState = reducer(newState, { type: ADD_TO_RETRY_LIST, name: 'Font 4' })

      expect(Object.keys(newState.retry).length).is.equal(4)
    })
  })

  describe('CLEAR_BUTTON_CLICKED_AND_RETRY_LIST', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, { type: ADD_TO_RETRY_LIST, name: 'Font 1' })
      newState = reducer(newState, { type: ADD_TO_RETRY_LIST, name: 'Font 2' })
      newState = reducer(newState, { type: ADD_TO_RETRY_LIST, name: 'Font 3' })
      newState = reducer(newState, { type: CLEAR_BUTTON_CLICKED_AND_RETRY_LIST })

      expect(Object.keys(newState.retry).length).is.equal(0)
      expect(newState.buttonClicked).is.equal(false)
    })
  })

  describe('GET_FILES_FROM_GITHUB', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, { type: GET_FILES_FROM_GITHUB })

      expect(newState.buttonClicked).is.equal(true)
    })
  })

  describe('GET_FILES_FROM_GITHUB_SUCCESS', () => {
    it('check the correct state gets returned when this action runs', () => {
      let data = ['Font 1', 'Font 2']
      let newData = ['Font 3', 'Font 4', 'Font 5']
      let newState = reducer(initialState, { type: GET_FILES_FROM_GITHUB_SUCCESS, payload: data })

      expect(newState.fontList.length).is.equal(2)

      newState = reducer(newState, { type: GET_FILES_FROM_GITHUB_SUCCESS, payload: newData })

      expect(newState.fontList.length).is.equal(3)
    })
  })

  describe('GET_FILES_FROM_GITHUB_FAILED', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, { type: GET_FILES_FROM_GITHUB_FAILED, payload: 'error' })

      expect(newState.getFilesFromGitHubFailed).is.equal('error')
    })
  })

  describe('REQUEST_SENT_COUNTER', () => {
    it('check the correct state gets returned when this action runs', () => {
      GFPDF.coreFontError = '%s CORE FONT(S) DID NOT INSTALL CORRECTLY'
      GFPDF.coreFontSuccess = 'ALL CORE FONTS SUCCESSFULLY INSTALLED'

      initialState.downloadCounter = 1
      initialState.retry = []
      let state = reducer(initialState, { type: REQUEST_SENT_COUNTER })

      expect(state.console.completed.status).is.equal('success')
      expect(state.console.completed.message).is.equal('ALL CORE FONTS SUCCESSFULLY INSTALLED')
      expect(state.downloadCounter).is.equal(0)
      expect(state.requestDownload).is.equal('finished')

      initialState.downloadCounter = 1
      initialState.retry = ['font1', 'font2', 'font3']
      let newState = reducer(initialState, { type: REQUEST_SENT_COUNTER })

      expect(newState.console.completed.status).is.equal('error')
      expect(newState.console.completed.message).is.equal('3 CORE FONT(S) DID NOT INSTALL CORRECTLY')
      expect(newState.downloadCounter).is.equal(3)
      expect(newState.requestDownload).is.equal('finished')
    })
  })

  describe('CLEAR_REQUEST_REMAINING_DATA', () => {
    it('check the correct state gets returned when this action runs', () => {
      let newState = reducer(initialState, { type: CLEAR_REQUEST_REMAINING_DATA })

      expect(newState.requestDownload).is.equal('')
    })
  })
})
