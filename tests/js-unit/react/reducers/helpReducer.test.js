import {
  GET_DATA,
  RESULT_ERROR,
  UPDATE_RESULTS,
  DELETE_RESULTS
} from '../../../../src/assets/js/react/actions/help'
import reducer, { initialState } from '../../../../src/assets/js/react/reducers/helpReducer'

describe('Reducers - helpReducer', () => {

  let newState

  test('GET_DATA', () => {
    newState = reducer(initialState, { type: GET_DATA })

    expect(newState.loading).toBe(true)
    expect(newState.error).toBe('')
  })

  test('RESULT_ERROR', () => {
    newState = reducer(initialState, { type: RESULT_ERROR, payload: 'error' })

    expect(newState.error).toBe('error')
  })

  test('UPDATE_RESULTS', () => {
    let resultData = [
      {
        link: 'https://gravitypdf.com/documentation/v5/developer-php-form-data-array/',
        title: { rendered: 'PHP Form Data Array' },
        excerpt: { rendered: '<p>Introduction #introduction Gravity Forms merge tags and conditional shortcodes are useful PDF-building tools, but there are drawbacks. For instance, you cannot create nested conditionals or do any post-processing to the&#8230;</p>' }
      }
    ]
    newState = reducer(initialState, { type: UPDATE_RESULTS, payload: resultData })

    expect(newState.results).toEqual(expect.arrayContaining(resultData))
    expect(newState.results.length).toBe(1)

    resultData = []
    newState = reducer(initialState, { type: UPDATE_RESULTS, payload: resultData })

    expect(newState.results.length).toBe(0)
  })

  test('DELETE_RESULTS', () => {
    newState = reducer(initialState, { type: DELETE_RESULTS })

    expect(newState.results.length).toBe(0)
  })
})
