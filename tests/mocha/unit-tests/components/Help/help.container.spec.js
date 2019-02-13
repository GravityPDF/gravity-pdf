import { expect } from 'chai'
import { doHandleChange, toggleLoadingTrue, toggleLoadingFalse, fetchData } from '../../../../../src/assets/js/react/components/Help/HelpContainer'

describe('<HelpContainer />', () => {

 it('Should change the searchInput state', () => {
  const state = { searchInput: 'newvaluehere' };
  const newState = doHandleChange(state.searchInput);

  expect(newState.searchInput).to.equal('newvaluehere')
 })

 it('Should change loading state into True', () => {
  const state = { loading: true }
  const newState = toggleLoadingTrue(state.loading)

  expect(newState.loading).to.equal(true)
 })

 it('Should change loading state into False', () => {
  const state = { loading: false }
  const newState = toggleLoadingFalse(state.loading)

  expect(newState.loading).to.equal(false)
 })

 it(`Should fetchData from API for the searchInput value with available result data to return`, () => {
  const state = { searchInput: 'setup' }
  return fetchData(state.searchInput)
   .then(response => {
    // Response array length should be 0 since there is no available data for the 'searchInput' value
    expect(response.body.length).to.be.at.least(1)
   })  
 })

 it(`Should fetchData from API for the searchInput value with no available result data to return`, () => {
  const state = { searchInput: 'setuppp' }
  return fetchData(state.searchInput)
   .then(response => {
    // Response array length should be 0 since there is no available data for the 'searchInput' value
    expect(response.body.length).to.equal(0)
   })  
 })
})