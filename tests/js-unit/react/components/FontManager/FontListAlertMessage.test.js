import React from 'react'
import { shallow, mount } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import { FontListAlertMessage } from '../../../../../src/assets/js/react/components/FontManager/FontListAlertMessage'

describe('FontManager - FontListAlertMessage.js', () => {

  // Mock component props
  const props = {
    getCustomFontList: jest.fn(),
    resetSearchResult: jest.fn()
  }

  describe('RENDERS COMPONENT', () => {
    test('render <FontListAlertMessage /> component', () => {
      const wrapper = shallow(<FontListAlertMessage {...props} />)
      const component = findByTestAttr(wrapper, 'component-FontListAlertMessage')

      expect(component.length).toBe(1)
    })

    test('display font list empty message', () => {
      const empty = true
      const wrapper = mount(<FontListAlertMessage {...props} empty={empty} />)

      expect(wrapper.find('span').text()).toBe('Font list empty.')
    })

    test('display search result empty message', () => {
      const empty = false
      const wrapper = mount(<FontListAlertMessage {...props} empty={empty} />)

      expect(wrapper.find('span').at(0).text()).toBe('No fonts matching your search found. Clear Search.')
    })

    test('display API call request link', () => {
      const empty = false
      const error = 'error'
      const wrapper = mount(
        <FontListAlertMessage
          {...props}
          empty={empty}
          error={error}
        />
      )

      expect(wrapper.find('p.link').length).toBe(1)
    })
  })
})
