import React from 'react'
import { shallow } from 'enzyme'
import DisplayResult from '../../../../../src/assets/js/react/components/Help/DisplayResult'
import Spinner from '../../../../../src/assets/js/react/components/Spinner'
import { expect } from 'chai'

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

describe('<DisplayResult />', () => {
  it('Should load loading spinner', () => {
    const wrapper = shallow(<DisplayResult searchInput={'formm'} loading={true} helpResult={fixResultsA.body} />)
    expect(wrapper.containsAllMatchingElements([
      <Spinner />
    ])).to.equal(true)
  })
  it(`Should display 'available result'`, () => {
    const wrapper = shallow(<DisplayResult searchInput={'formm'} helpResult={fixResultsA.body} />)
    expect(wrapper.find('li.resultExist')).to.have.length(2)
  })

  it(`Should display 'no result'`, () => {
    const wrapper = shallow(<DisplayResult searchInput={'formm'} helpResult={[]} />)
    expect(wrapper.find('li.noResult')).to.have.length(1)
  })
})
