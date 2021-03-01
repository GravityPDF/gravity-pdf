import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import FontListHeader from '../../../../../src/assets/js/react/components/FontManager/FontListHeader'

describe('FontManager - FontListHeader.js', () => {

  describe('RENDERS COMPONENT', () => {
    test('render <FontListHeader /> component', () => {
      const wrapper = shallow(<FontListHeader />)
      const component = findByTestAttr(wrapper, 'component-FontListHeader')

      expect(component.length).toBe(1)
      expect(component.find('div.font-name').text()).toBe('Installed Fonts')
      expect(component.find('div').at(2).text()).toBe('Regular')
      expect(component.find('div').at(3).text()).toBe('Italics')
      expect(component.find('div').at(4).text()).toBe('Bold')
      expect(component.find('div').at(5).text()).toBe('Bold Italics')
    })
  })
})
