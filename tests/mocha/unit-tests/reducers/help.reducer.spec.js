import {
 UPDATE_RESULTS,
 DELETE_RESULTS
} from '../../../../src/assets/js/react/actionTypes/help'

import reducer, { initialState } from '../../../../src/assets/js/react/reducers/helpReducer'

describe('help Reducer', () => {

 it('should handle UPDATE_RESULTS', () => {
  let resultData = [
   {
    link: "https://gravitypdf.com/documentation/v5/developer-php-form-data-array/",
    title: { rendered: "PHP Form Data Array" },
    excerpt: { rendered: "<p>Introduction #introduction Gravity Forms merge tags and conditional shortcodes are useful PDF-building tools, but there are drawbacks. For instance, you cannot create nested conditionals or do any post-processing to the&#8230;</p>" }
   }
  ]

  let newState = reducer(initialState, {type: UPDATE_RESULTS, payload: resultData})
  expect(Object.keys(newState.results).length).to.be.at.least(1)

  resultData = []

  newState = reducer(initialState, {type: UPDATE_RESULTS, payload: resultData})
  expect(Object.keys(newState.results).length).to.equal(0)
 })

 it('should handle DELETE_RESULTS', () => {
  let newState = reducer(initialState, { type: DELETE_RESULTS })
  expect(Object.keys(newState.results).legnth).is.equal(undefined)
 })
})