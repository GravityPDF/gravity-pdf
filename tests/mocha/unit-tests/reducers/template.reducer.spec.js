import { SEARCH_TEMPLATES, SELECT_TEMPLATE } from '../../../../src/assets/js/actionTypes/templates'
import reducer, { initialState } from '../../../../src/assets/js/reducers/templateReducer'

describe('SEARCH_TEMPLATES', () => {

  it('check the correct state gets returned when this action runs', () => {
    let newState = reducer(undefined, { type: SEARCH_TEMPLATES, text: 'New Search Item' } )
    expect(newState.search).is.equal('New Search Item')

    newState = reducer(newState, { type: SEARCH_TEMPLATES, text: 'Another Search Item' } )
    expect(newState.search).is.equal('Another Search Item')
  })

  it('verify state is immutable', () => {
    let state = reducer(undefined, { type: SEARCH_TEMPLATES, text: 'New Search Item' } )
    let newState = reducer(newState, { type: SEARCH_TEMPLATES, text: 'Another Search Item' } )

    expect(newState).is.not.equal(state)
  })
})

describe('SELECT_TEMPLATE', () => {

  it('check the correct state gets returned when this action runs', () => {
    let newState = reducer(undefined, { type: SELECT_TEMPLATE, id: 'template-id' } )
    expect(newState.activeTemplate).is.equal('template-id')

    newState = reducer(newState, { type: SELECT_TEMPLATE, id: 'new-template-id' } )
    expect(newState.activeTemplate).is.equal('new-template-id')
  })

  it('verify state is immutable', () => {
    let state = reducer(undefined, { type: SELECT_TEMPLATE, id: 'template-id' } )
    let newState = reducer(newState, { type: SELECT_TEMPLATE, id: 'new-template-id' } )

    expect(newState).is.not.equal(state)
  })
})

describe('Check state gets returned when no actions match', () => {

  it('Check the state does not change when no action matches', () => {
    let state = reducer(undefined, { type: 'none', id: 'template-id' } )
    expect(state).is.equal(initialState)
  })
})