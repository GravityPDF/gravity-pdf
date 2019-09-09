import {
  GET_DATA,
  RESULT_ERROR,
  UPDATE_RESULTS,
  DELETE_RESULTS
} from '../../../../../src/assets/js/react/actions/help'
import reducer, { initialState } from '../../../../../src/assets/js/react/reducers/helpReducer'

describe('Reducers helpReducer - ', () => {

  describe('GET_DATA', () => {
    let newState = reducer(initialState, { type: GET_DATA })

    expect(newState.loading).to.equal(true)
  })

  describe('RESULT_ERROR', () => {
    let newState = reducer(initialState, { type: RESULT_ERROR, payload: 'error' })

    expect(newState.error).to.equal('error')
  })

  describe('UPDATE_RESULTS', () => {
    let resultData = [
      {
        link: 'https://gravitypdf.com/documentation/v5/developer-php-form-data-array/',
        title: { rendered: 'PHP Form Data Array' },
        excerpt: { rendered: '<p>Introduction #introduction Gravity Forms merge tags and conditional shortcodes are useful PDF-building tools, but there are drawbacks. For instance, you cannot create nested conditionals or do any post-processing to the&#8230;</p>' }
      }
    ]
    let newState = reducer(initialState, { type: UPDATE_RESULTS, payload: resultData })

    expect(newState.results).to.be.a('array')
    expect(newState.results.length).to.equal(1)

    resultData = []
    newState = reducer(initialState, { type: UPDATE_RESULTS, payload: resultData })

    expect(newState.results.length).to.equal(0)
  })

  describe('DELETE_RESULTS', () => {
    let newState = reducer(initialState, { type: DELETE_RESULTS })

    expect(newState.results.length).is.equal(0)
  })
})
