import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import FontListIcon from '../../../../../src/assets/js/react/components/FontManager/FontListIcon'

describe('FontManager - FontListIcon.js', () => {

  // Mock component props
  const props = { font: '' }
  const wrapper = shallow(<FontListIcon {...props} />)

  describe('RENDERS COMPONENT', () => {
    test('render <FontListIcon /> component', () => {
      const component = findByTestAttr(wrapper, 'component-FontListIcon')

      expect(component.length).toBe(1)
    })

    test('render "check" icon', () => {
      const wrapper = shallow(<FontListIcon font='arial' />)

      expect(wrapper.find('span.dashicons-yes').length).toBe(1)
    })

    test('render "x" icon', () => {
      const wrapper = shallow(<FontListIcon {...props} />)

      expect(wrapper.find('span.dashicons-no-alt').length).toBe(1)
    })
  })
})
