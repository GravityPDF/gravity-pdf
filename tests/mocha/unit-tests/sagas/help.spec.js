import { call, put, takeLatest } from 'redux-saga/effects'
import { getResults, watchGetResults } from '../../../../src/assets/js/react/sagas/help'
import { GET_DATA, RESULT_ERROR } from '../../../../src/assets/js/react/actions/help'
import * as api from '../../../../src/assets/js/react/api/help'

describe('Sagas help -', () => {

  describe('watchGetResults()', () => {
    const gen = watchGetResults()

    it('should check the watcher to loads up the getResults function and call GET_DATA action', () => {
      expect(gen.next().value).to.deep.eql(takeLatest(GET_DATA, getResults))
    })
  })

  describe('getResults()', () => {
    const action = { payload: 'data' }
    const gen = getResults(action)

    it('should check that saga asks to call the API for getResults', () => {
      expect(gen.next().value).to.deep.eql(call(api.apiGetSearchResult, action.payload))
    })

    it('should check that saga handles correctly to the failure of getResults API call', () => {
      expect(gen.throw({ error: GFPDF.getSearchResultError }).value).to.deep.eql(put({
        type: RESULT_ERROR,
        payload: GFPDF.getSearchResultError
      }))
    })
  })
})
