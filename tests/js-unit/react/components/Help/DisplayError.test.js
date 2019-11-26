import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import DisplayError from '../../../../../src/assets/js/react/components/Help/DisplayError'

describe('Help - DisplayError.js', () => {

  const wrapper = shallow(<DisplayError displayError={'An error occurred. Please try again'} />)

  test('renders <DisplayError /> component container', () => {
    const component = findByTestAttr(wrapper, 'component-error')

    expect(component.length).toBe(1)
  })

  test('display error text', () => {
    expect(wrapper.find('li').text()).toBe('An error occurred. Please try again')
  })
})
