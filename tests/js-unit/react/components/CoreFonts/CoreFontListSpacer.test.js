import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import CoreFontListSpacer from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontListSpacer'

describe('CoreFonts - CoreFontListSpacer.js', () => {

  const wrapper = shallow(<CoreFontListSpacer />)

  test('renders <CoreFontListSpacer /> component container', () => {
    const component = findByTestAttr(wrapper, 'component-coreFontList-spacer')

    expect(component.length).toBe(1)
  })

  test('display spacer content', () => {
    expect(wrapper.find('div').text()).toBe('---')
  })
})
