import React from 'react'
import { mount } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import FontVariantLabel from '../../../../../src/assets/js/react/components/FontManager/FontVariantLabel'

describe('FontManager - FontVariantLabel.js', () => {

  const props = { label: 'regular', font: 'false' }
  const wrapper = mount(<FontVariantLabel {...props} />)

  describe('RENDERS COMPONENT', () => {
    test('render <FontVariantLabel /> component', () => {
      const component = findByTestAttr(wrapper, 'component-FontVariantLabel')

      expect(component.length).toBe(1)
      expect(wrapper.find('span').text()).toBe('Regular')
    })
  })
})
