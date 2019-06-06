import React from 'react'
import { expect } from 'chai'
import { mount } from 'enzyme'
import { HelpContainer } from '../../../../../src/assets/js/react/components/Help/HelpContainer'

const fixResults = [
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
const spy = sinon.spy()

describe('<HelpContainer />', () => {

  it(`Should display 'Available results' based on input change value `, () => {
    const wrapper = mount(<HelpContainer getData={spy} helpResult={fixResults} />)
    wrapper.find('input').simulate('change', { target: { value: 'form' } })

    expect(wrapper.find('li.resultExist')).to.have.length(2)
  })

  it(`Should display 'No Available results' based on input change value `, () => {
    const wrapper = mount(<HelpContainer getData={spy} helpResult={[]} error='' />)
    wrapper.find('input').simulate('change', { target: { value: 'forrp' } })
    wrapper.setState({ loading: false })

    expect(wrapper.find('li.noResult')).to.have.length(1)
  })
})
