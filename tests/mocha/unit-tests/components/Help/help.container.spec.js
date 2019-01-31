import React from 'react'
import { expect } from 'chai'
import { mount } from 'enzyme'
import request from 'superagent'
import { HelpContainer } from '../../../../../src/assets/js/react/components/Help/HelpContainer'
let mock = require('superagent-mocker')(request)
mock.timeout = 1

const state = { searchInputA: 'form', searchInputB: 'forrp' }
const fixResultsA = {
  body: [
    {
      link: 'https://gravitypdf.com/documentation/v5/user-hide-form-fields/',
      title: { rendered: 'Hide Form Fields' },
      excerpt: { rendered: '<p>Only certain form data is important to you. That&#8217;s why Gravity PDF has a number of ways to filter out the unimportant fields in your generated PDF. It&#8217;s important to&#8230;</p>' }
    },
    {
      link: 'https://gravitypdf.com/documentation/v5/user-gravity-forms-compatibility/',
      title: { rendered: 'Gravity Forms Compatibility' },
      excerpt: { rendered: '<p>Gravity PDF is a third party extension for Gravity Forms. The company who builds Gravity PDF, Blue Liquid Designs, is an independent third party who has no control over Gravity&#8230;</p>' }
    }
  ]
}
const fixResultsB = {
  body: []
}

describe('<HelpContainer />', () => {
  beforeEach(function () {
    mock.clearRoutes()
  })

  afterEach(function () {
    mock.clearRoutes()
  })

  it(`Should fetch data from API and return 'Available result'`, async () => {
    mock.get(`https://gravitypdf.com/wp-json/wp/v2/v5_docs/?search=${state.searchInputA}`, () => {
      return fixResultsA
    })

    const wrapper = mount(<HelpContainer />)
    const inst = wrapper.instance()

    inst.searchInputLength(state.searchInputA)
    const files = await inst.fetchData(state.searchInputA)

    expect(files.length).to.equal(2)
  })

  it(`Should display 'Available results' based on input change value `, () => {
    const wrapper = mount(<HelpContainer helpResult={fixResultsA.body} />)
    wrapper.find('input').simulate('change', { target: { value: state.searchInputA } })

    expect(wrapper.find('li.resultExist')).to.have.length(2)
  })

  it(`Should fetch data from API and return 'No result'`, async () => {
    mock.get(`https://gravitypdf.com/wp-json/wp/v2/v5_docs/?search=${state.searchInputB}`, () => {
      return fixResultsB
    })

    const wrapper = mount(<HelpContainer />)
    const inst = wrapper.instance()

    inst.searchInputLength(state.searchInputB)
    const files = await inst.fetchData(state.searchInputB)

    expect(files.length).to.equal(0)
  })

  it(`Should display 'No Available results' based on input change value `, () => {
    const wrapper = mount(<HelpContainer helpResult={fixResultsB.body} />)

    wrapper.find('input').simulate('change', { target: { value: state.searchInputB } })
    wrapper.setState({ loading: false })

    expect(wrapper.find('li.noResult')).to.have.length(1)
  })
})
