import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import AddFontFooter from '../../../../../src/assets/js/react/components/FontManager/AddFontFooter'

describe('FontManager - AddFontFooter.js', () => {

  // Mock component props
  const props = {
    msg: { success: { addFont: 'success' }, error: { addFont: 'error' } },
    loading: true,
    tabIndex: '148'
  }
  const wrapper = shallow(<AddFontFooter {...props} />)

  describe('RENDERS COMPONENT', () => {
    test('render <AddFontFooter /> component', () => {
      const component = findByTestAttr(wrapper, 'component-AddFontFooter')

      expect(component.length).toBe(1)
    })

    test('render cancel button', () => {
      const wrapper = shallow(<AddFontFooter {...props} id='active' />)

      expect(wrapper.find('div.cancel').length).toBe(1)
    })

    test('render add font button', () => {
      expect(wrapper.find('button').text()).toBe('Add Font →')
    })

    test('render update font button', () => {
      const wrapper = shallow(<AddFontFooter {...props} id='active' />)

      expect(wrapper.find('button').text()).toBe('Update Font →')
    })

    test('render loading spinner', () => {
      expect(wrapper.find('Spinner').length).toBe(1)
    })

    test('render success message', () => {
      expect(wrapper.find('span.success').length).toBe(1)
    })

    test('render error message', () => {
      expect(wrapper.find('span.error').length).toBe(1)
    })
  })
})
