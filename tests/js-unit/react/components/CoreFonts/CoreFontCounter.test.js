import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import CoreFontCounter from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontCounter'

describe('CoreFonts - CoreFontCounter.js', () => {

  test('renders <CoreFontCounter /> component container', () => {
    const wrapper = shallow(<CoreFontCounter />)
    const component = findByTestAttr(wrapper, 'component-coreFont-counter')

    expect(component.length).toBe(1)
  })

  test('display an inline counter', () => {
    const props = {
      text: 'Fonts remaining:',
      queue: 8
    }
    const wrapper = shallow(<CoreFontCounter {...props} />)

    expect(wrapper.find('span').text()).toBe('Fonts remaining: 8')
  })
})
