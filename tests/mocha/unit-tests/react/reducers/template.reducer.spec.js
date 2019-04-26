import {
  SEARCH_TEMPLATES,
  SELECT_TEMPLATE,
  ADD_TEMPLATE,
  UPDATE_TEMPLATE_PARAM,
  DELETE_TEMPLATE,
} from '../../../../../src/assets/js/react/actionTypes/templates'

import reducer, { initialState } from '../../../../../src/assets/js/react/reducers/templateReducer'

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

describe('Check state gets returned when no actions match', () => {

  it('Check the state does not change when no action matches', () => {
    let state = reducer(undefined, {type: 'none', id: 'template-id'})
    expect(state).is.equal(initialState)
  })
})
