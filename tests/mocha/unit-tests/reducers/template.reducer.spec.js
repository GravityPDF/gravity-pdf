import Immutable from 'immutable'

import {
  SEARCH_TEMPLATES,
  SELECT_TEMPLATE,
  ADD_TEMPLATE,
  UPDATE_TEMPLATE,
  UPDATE_TEMPLATE_PARAM,
  DELETE_TEMPLATE,
} from '../../../../src/assets/js/react/actionTypes/templates'

import reducer, { initialState } from '../../../../src/assets/js/react/reducers/templateReducer'

describe('SEARCH_TEMPLATES', () => {

  it('check the correct state gets returned when this action runs', () => {
    let newState = reducer(undefined, { type: SEARCH_TEMPLATES, text: 'New Search Item' })
    expect(newState.search).is.equal('New Search Item')

    newState = reducer(newState, { type: SEARCH_TEMPLATES, text: 'Another Search Item' })
    expect(newState.search).is.equal('Another Search Item')
  })

  it('verify state is immutable', () => {
    let state = reducer(undefined, { type: SEARCH_TEMPLATES, text: 'New Search Item' })
    let newState = reducer(state, { type: SEARCH_TEMPLATES, text: 'Another Search Item' })

    expect(newState).is.not.equal(state)
  })
})

describe('SELECT_TEMPLATE', () => {

  it('check the correct state gets returned when this action runs', () => {
    let newState = reducer(undefined, { type: SELECT_TEMPLATE, id: 'template-id' })
    expect(newState.activeTemplate).is.equal('template-id')

    newState = reducer(newState, { type: SELECT_TEMPLATE, id: 'new-template-id' })
    expect(newState.activeTemplate).is.equal('new-template-id')
  })

  it('verify state is immutable', () => {
    let state = reducer(undefined, { type: SELECT_TEMPLATE, id: 'template-id' })
    let newState = reducer(state, { type: SELECT_TEMPLATE, id: 'new-template-id' })

    expect(newState).is.not.equal(state)
  })
})

describe('ADD_TEMPLATE', () => {

  it('check the correct state gets returned when this action runs', () => {
    let newState = reducer(undefined, { type: ADD_TEMPLATE, template: Immutable.fromJS({ id: 'template-id' }) })
    expect(newState.list.size).is.equal(4)

    newState = reducer(newState, { type: ADD_TEMPLATE, template: Immutable.fromJS({ id: 'template-id1' }) })
    expect(newState.list.size).is.equal(5)
  })

  it('verify state is immutable', () => {
    let state = reducer(undefined, { type: ADD_TEMPLATE, template: Immutable.fromJS({ id: 'template-id' }) })
    let newState = reducer(state, { type: ADD_TEMPLATE, template: Immutable.fromJS({ id: 'template-id' }) })

    expect(newState).is.not.equal(state)
  })
})

describe('UPDATE_TEMPLATE', () => {

  it('check the correct state gets returned when this action runs', () => {
    let newState = reducer(undefined, { type: UPDATE_TEMPLATE, template: Immutable.fromJS({ id: 'zadani', 'owner': 'Harry' }) })
    expect(newState.list.getIn([0, 'owner'])).is.equal('Harry')
  })

  it('verify state is immutable', () => {
    let state = reducer(undefined, { type: UPDATE_TEMPLATE, template: Immutable.fromJS({ id: 'zadani', 'owner': 'Simon' }) })
    let newState = reducer(state, { type: UPDATE_TEMPLATE, template: Immutable.fromJS({ id: 'zadani', 'owner': 'Billy' }) })

    expect(newState.list.getIn([0, 'owner'])).is.not.equal(state.list.getIn([0, 'owner']))
  })
})

describe('UPDATE_TEMPLATE_PARAM', () => {

  it('check the correct state gets returned when this action runs', () => {
    let newState = reducer(undefined, { type: UPDATE_TEMPLATE_PARAM, id: 'zadani', name: 'owner', value: 'Wilson' })
    expect(newState.list.getIn([0, 'owner'])).is.equal('Wilson')

    newState = reducer(undefined, { type: UPDATE_TEMPLATE_PARAM, id: 'zadani', name: 'owner', value: 'Billy' })
    expect(newState.list.getIn([0, 'owner'])).is.equal('Billy')
  })

  it('verify state is immutable', () => {
    let state = reducer(undefined, { type: UPDATE_TEMPLATE_PARAM, id: 'zadani', name: 'owner', value: 'Wilson' })
    let newState = reducer(state, { type: UPDATE_TEMPLATE_PARAM, id: 'zadani', name: 'owner', value: 'Billy' })

    expect(newState.list.getIn([0, 'owner'])).is.not.equal(state.list.getIn([0, 'owner']))
  })
})

describe('DELETE_TEMPLATE', () => {

  it('check the correct state gets returned when this action runs', () => {
    let newState = reducer(undefined, { type: DELETE_TEMPLATE, id: 'zadani' })
    expect(newState.list.size).is.equal(2)

    newState = reducer(newState, { type: DELETE_TEMPLATE, id: 'rubix' })
    expect(newState.list.size).is.equal(1)
  })

  it('verify state is immutable', () => {
    let state = reducer(undefined, { type: DELETE_TEMPLATE, id: 'zadani' })
    let newState = reducer(undefined, { type: DELETE_TEMPLATE, id: 'zadani' })

    expect(newState).is.not.equal(state)
  })
})

describe('Check state gets returned when no actions match', () => {

  it('Check the state does not change when no action matches', () => {
    let state = reducer(undefined, { type: 'none', id: 'template-id' })
    expect(state).is.equal(initialState)
  })
})