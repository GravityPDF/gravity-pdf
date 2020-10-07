import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import FontListSkeleton from '../../../../../src/assets/js/react/components/FontManager/FontListSkeleton'

describe('FontManager - FontListSkeleton.js', () => {

  describe('RENDERS COMPONENT', () => {
    test('render <FontListSkeleton /> component', () => {
      const wrapper = shallow(<FontListSkeleton />)
      const component = findByTestAttr(wrapper, 'component-FontListSkeleton')

      expect(component.length).toBe(1)
    })
  })
})
