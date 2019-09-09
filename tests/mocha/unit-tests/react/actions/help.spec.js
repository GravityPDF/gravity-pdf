import {
  GET_DATA,
  RESULT_ERROR,
  UPDATE_RESULTS,
  DELETE_RESULTS,
  getData,
  updateError,
  updateResult,
  deleteResult
} from '../../../../../src/assets/js/react/actions/help'

describe('Actions help -', () => {

  describe('getData', () => {
    it('check it returns the correct action', () => {
      let results = getData('data')

      expect(results.type).is.equal(GET_DATA)
      expect(results.payload).is.equal('data')
    })
  })

  describe('updateError', () => {
    it('check it returns the correct action', () => {
      let results = updateError('error')

      expect(results.type).is.equal(RESULT_ERROR)
      expect(results.payload).is.equal('error')
    })
  })

  describe('updateResult', () => {
    it('check it returns the correct action', () => {
      let results = updateResult('data')

      expect(results.type).is.equal(UPDATE_RESULTS)
      expect(results.payload).is.equal('data')
    })
  })

  describe('deleteResult', () => {
    it('check it returns the correct action', () => {
      let results = deleteResult()

      expect(results.type).is.equal(DELETE_RESULTS)
    })
  })
})
