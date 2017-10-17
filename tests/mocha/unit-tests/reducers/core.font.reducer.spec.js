import {
  ADD_TO_CONSOLE,
  ADD_TO_RETRY_LIST,
  CLEAR_RETRY_LIST,
  CLEAR_CONSOLE
} from '../../../../src/assets/js/react/actionTypes/coreFonts'

import reducer, { initialState } from '../../../../src/assets/js/react/reducers/coreFontReducer'

describe('ADD_TO_CONSOLE', () => {

  it('check the correct state gets returned when this action runs', () => {
    let newState = reducer(initialState, {type: ADD_TO_CONSOLE, key: 'key 1', status: 'status 1', message: 'Message 1'})

    expect(newState.console['key 1'].status).is.equal('status 1')

    newState = reducer(newState, {type: ADD_TO_CONSOLE, key: 'key 2', status: 'status 2', message: 'Message 2'})
    newState = reducer(newState, {type: ADD_TO_CONSOLE, key: 'key 3', status: 'status 3', message: 'Message 3'})

    expect(newState.console['key 2'].status).is.equal('status 2')
    expect(Object.keys(newState.console).length).is.equal(3)
  })

  it('verify state is immutable', () => {
    let state = reducer(initialState, {type: ADD_TO_CONSOLE, key: 'key 1', status: 'status 1', message: 'Message 1'})
    let newState = reducer(state, {type: ADD_TO_CONSOLE, key: 'key 1', status: 'status 1', message: 'Message 1'})

    expect(newState).is.not.equal(state)
  })
})

describe('CLEAR_CONSOLE', () => {
  it('check the correct state gets returned when this action runs', () => {
    let newState = reducer(initialState, {type: ADD_TO_CONSOLE, key: 'key 1', status: 'status 1', message: 'Message 1'})
    newState = reducer(newState, {type: ADD_TO_CONSOLE, key: 'key 2', status: 'status 2', message: 'Message 2'})
    newState = reducer(newState, {type: ADD_TO_CONSOLE, key: 'key 3', status: 'status 3', message: 'Message 3'})
    newState = reducer(newState, {type: CLEAR_CONSOLE})

    expect(Object.keys(newState.console).length).is.equal(0)
  })
})

describe('ADD_TO_RETRY_LIST', () => {
  it('check the correct state gets returned when this action runs', () => {
    let newState = reducer(initialState, {type: ADD_TO_RETRY_LIST, name: 'Font 1'})

    expect(newState.retry.length).is.equal(1)

    newState = reducer(newState, {type: ADD_TO_RETRY_LIST, name: 'Font 2'})
    newState = reducer(newState, {type: ADD_TO_RETRY_LIST, name: 'Font 3'})

    expect(Object.keys(newState.retry).length).is.equal(3)

    newState = reducer(newState, {type: ADD_TO_RETRY_LIST, name: 'Font 2'})
    expect(Object.keys(newState.retry).length).is.equal(3)
  })
})

describe('CLEAR_RETRY_LIST', () => {
  it('check the correct state gets returned when this action runs', () => {
    let newState = reducer(initialState, {type: ADD_TO_RETRY_LIST, name: 'Font 1'})
    newState = reducer(newState, {type: ADD_TO_RETRY_LIST, name: 'Font 2'})
    newState = reducer(newState, {type: ADD_TO_RETRY_LIST, name: 'Font 3'})
    newState = reducer(newState, {type: CLEAR_RETRY_LIST})

    expect(Object.keys(newState.retry).length).is.equal(0)
  })
})