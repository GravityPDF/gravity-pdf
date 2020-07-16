import {
  ADD_TO_CONSOLE,
  CLEAR_CONSOLE,
  ADD_TO_RETRY_LIST,
  CLEAR_BUTTON_CLICKED_AND_RETRY_LIST,
  GET_FILES_FROM_GITHUB,
  GET_FILES_FROM_GITHUB_SUCCESS,
  GET_FILES_FROM_GITHUB_FAILED,
  REQUEST_SENT_COUNTER,
  CLEAR_REQUEST_REMAINING_DATA
} from '../../../../src/assets/js/react/actions/coreFonts'
import reducer, { initialState } from '../../../../src/assets/js/react/reducers/coreFontReducer'

describe('Reducers - coreFontReducer', () => {

  let state
  let newState

  describe('ADD_TO_CONSOLE', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, {
        type: ADD_TO_CONSOLE,
        key: 'key 1',
        status: 'status 1',
        message: 'Message 1'
      })

      expect(newState.console['key 1'].status).toBe('status 1')

      newState = reducer(newState, { type: ADD_TO_CONSOLE, key: 'key 2', status: 'status 2', message: 'Message 2' })
      newState = reducer(newState, { type: ADD_TO_CONSOLE, key: 'key 3', status: 'status 3', message: 'Message 3' })

      expect(newState.console['key 2'].status).toBe('status 2')
      expect(Object.keys(newState.console).length).toBe(3)
    })

    test('verify state is immutable', () => {
      state = reducer(initialState, {
        type: ADD_TO_CONSOLE,
        key: 'key 1',
        status: 'status 1',
        message: 'Message 1'
      })

      newState = reducer(state, { type: ADD_TO_CONSOLE, key: 'key 1', status: 'status 1', message: 'Message 1' })

      expect(newState).toEqual(state)
    })
  })

  describe('CLEAR_CONSOLE', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, {
        type: ADD_TO_CONSOLE,
        key: 'key 1',
        status: 'status 1',
        message: 'Message 1'
      })
      newState = reducer(newState, { type: ADD_TO_CONSOLE, key: 'key 2', status: 'status 2', message: 'Message 2' })
      newState = reducer(newState, { type: ADD_TO_CONSOLE, key: 'key 3', status: 'status 3', message: 'Message 3' })
      newState = reducer(newState, { type: CLEAR_CONSOLE })

      expect(Object.keys(newState.console).length).toBe(0)
    })
  })

  describe('ADD_TO_RETRY_LIST', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: ADD_TO_RETRY_LIST, name: 'Font 1' })

      expect(newState.retry.length).toBe(1)

      newState = reducer(newState, { type: ADD_TO_RETRY_LIST, name: 'Font 2' })
      newState = reducer(newState, { type: ADD_TO_RETRY_LIST, name: 'Font 3' })

      expect(Object.keys(newState.retry).length).toBe(3)

      newState = reducer(newState, { type: ADD_TO_RETRY_LIST, name: 'Font 4' })

      expect(Object.keys(newState.retry).length).toBe(4)
    })
  })

  describe('CLEAR_BUTTON_CLICKED_AND_RETRY_LIST', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: ADD_TO_RETRY_LIST, name: 'Font 1' })
      newState = reducer(newState, { type: ADD_TO_RETRY_LIST, name: 'Font 2' })
      newState = reducer(newState, { type: ADD_TO_RETRY_LIST, name: 'Font 3' })
      newState = reducer(newState, { type: CLEAR_BUTTON_CLICKED_AND_RETRY_LIST })

      expect(Object.keys(newState.retry).length).toBe(0)
      expect(newState.buttonClicked).toBe(false)
    })
  })

  describe('GET_FILES_FROM_GITHUB', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: GET_FILES_FROM_GITHUB })

      expect(newState.buttonClicked).toBe(true)
    })
  })

  describe('GET_FILES_FROM_GITHUB_SUCCESS', () => {

    test('check the correct state gets returned when this action runs', () => {
      let data = ['Font 1', 'Font 2']
      let newData = ['Font 3', 'Font 4', 'Font 5']
      newState = reducer(initialState, { type: GET_FILES_FROM_GITHUB_SUCCESS, payload: data })

      expect(newState.fontList.length).toBe(2)

      newState = reducer(newState, { type: GET_FILES_FROM_GITHUB_SUCCESS, payload: newData })

      expect(newState.fontList.length).toBe(3)
    })
  })

  describe('GET_FILES_FROM_GITHUB_FAILED', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: GET_FILES_FROM_GITHUB_FAILED, payload: 'error' })

      expect(newState.getFilesFromGitHubFailed).toBe('error')
    })
  })

  describe('REQUEST_SENT_COUNTER', () => {

    test('check the correct state gets returned when this action runs', () => {
      GFPDF.coreFontError = '%s CORE FONT(S) DID NOT INSTALL CORRECTLY'
      GFPDF.coreFontSuccess = 'ALL CORE FONTS SUCCESSFULLY INSTALLED'

      initialState.downloadCounter = 1
      initialState.retry = []
      state = reducer(initialState, { type: REQUEST_SENT_COUNTER })

      expect(state.console.completed.status).toBe('success')
      expect(state.console.completed.message).toBe('ALL CORE FONTS SUCCESSFULLY INSTALLED')
      expect(state.downloadCounter).toBe(0)
      expect(state.requestDownload).toBe('finished')

      initialState.downloadCounter = 1
      initialState.retry = ['font1', 'font2', 'font3']
      newState = reducer(initialState, { type: REQUEST_SENT_COUNTER })

      expect(newState.console.completed.status).toBe('error')
      expect(newState.console.completed.message).toBe('3 CORE FONT(S) DID NOT INSTALL CORRECTLY')
      expect(newState.downloadCounter).toBe(3)
      expect(newState.requestDownload).toBe('finished')
    })
  })

  describe('CLEAR_REQUEST_REMAINING_DATA', () => {

    test('check the correct state gets returned when this action runs', () => {
      newState = reducer(initialState, { type: CLEAR_REQUEST_REMAINING_DATA })

      expect(newState.requestDownload).toBe('')
    })
  })
})
