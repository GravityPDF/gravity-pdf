import {
 UPDATE_RESULTS,
 DELETE_RESULTS
} from '../../../../../src/assets/js/react/actionTypes/help'
import {
 updateResult,
 deleteResult
} from '../../../../../src/assets/js/react/actions/help'

describe('updateResult', () => {
 it('check if it returns the correct action', () => {
  let results = updateResult('data')
  expect(results.payload).is.equal('data')
  expect(results.type).is.equal(UPDATE_RESULTS)
 })
})

describe('deleteResult', () => {
 it('check if it returns the correct action', () => {
   let results = deleteResult()
   expect(results.type).is.equal(DELETE_RESULTS)
 })
})