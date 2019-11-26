import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import DisplayResultEmpty from '../../../../../src/assets/js/react/components/Help/DisplayResultEmpty'

describe('Help - DisplayResultEmpty.js', () => {

  const wrapper = shallow(<DisplayResultEmpty />)

  test('renders <DisplayResultEmpty /> component container', () => {
    const component =  findByTestAttr(wrapper, 'component-result-empty')

    expect(component.length).toBe(1)
  })

  test('display empty search results text', () => {
    expect(wrapper.find('li').text()).toBe('It doesn\'t look like there are any topics related to your issue.')
  })
})
