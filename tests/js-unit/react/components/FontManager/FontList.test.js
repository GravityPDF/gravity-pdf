import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import { FontList } from '../../../../../src/assets/js/react/components/FontManager/FontList'

describe('FontManager - FontList.js', () => {

  // Mock component props
  const props = {
    loading: true,
    fontList: [],
    searchResult: [],
    msg: { error: { fontList: 'error' } },
    history: {}
  }
  const wrapper = shallow(<FontList {...props} />)

  describe('RENDERS COMPONENT', () => {
    test('render <FontList /> component', () => {
      const component = findByTestAttr(wrapper, 'component-FontList')

      expect(component.length).toBe(1)
    })

    test('render <FontListHeader /> component', () => {
      expect(wrapper.find('FontListHeader').length).toBe(1)
    })

    test('render <FontListSkeleton /> component', () => {
      expect(wrapper.find('FontListSkeleton').length).toBe(1)
    })

    test('render <FontListItems /> component', () => {
      const wrapper = shallow(<FontList {...props} loading={false} />)

      expect(wrapper.find('Connect(FontListItems)').length).toBe(1)
    })

    test('render <FontListAlertMessage /> component', () => {
      expect(wrapper.find('Connect(FontListAlertMessage)').length).toBe(1)
    })
  })
})
