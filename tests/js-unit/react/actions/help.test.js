import {
  getData,
  GET_DATA,
  updateError,
  RESULT_ERROR,
  updateResult,
  UPDATE_RESULTS,
  deleteResult,
  DELETE_RESULTS
} from '../../../../src/assets/js/react/actions/help'

describe('Actions - help', () => {

  let results

  test('getData - check if it returns the correct action', () => {
    results = getData('data')

    expect(results.type).toEqual(GET_DATA)
    expect(results.payload).toBe('data')
  })

  test('updateError - check if it returns the correct action', () => {
    results = updateError('error')

    expect(results.type).toEqual(RESULT_ERROR)
    expect(results.payload).toBe('error')
  })

  test('updateResult - check if it returns the correct action', () => {
    results = updateResult({})

    expect(results.type).toEqual(UPDATE_RESULTS)
    expect(results.payload).toEqual({})
  })

  test('deleteResult - check if it returns the correct action', () => {
    results = deleteResult()

    expect(results.type).toEqual(DELETE_RESULTS)
  })
})
