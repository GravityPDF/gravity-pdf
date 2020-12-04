import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import FontManager from '../../../../../src/assets/js/react/components/FontManager/FontManager'

describe('FontManager - FontManager.js', () => {

  const props = { history: {} }
  const wrapper = shallow(<FontManager {...props} />)

  describe('RUN LIFECYCLE METHODS', () => {
    test('componentDidMount() - Add focus event to document option on mount', () => {
      FontManager.prototype.container = { focus: jest.fn() }

      const map = {}

      document.addEventListener = jest.fn((event, cb) => { map[event] = cb })

      const focus = jest.spyOn(FontManager.prototype.container, 'focus')
      const handleFocus = jest
        .spyOn(FontManager.prototype, 'handleFocus')
        .mockImplementation(() => jest.fn())
      const wrapper = shallow(<FontManager {...props} />)

      // Call componentDidMount()
      wrapper.instance().componentDidMount()
      // Simulate 'tab' keyboard press
      map.focus({ keyCode: 9, stopPropagation: jest.fn() })

      expect(focus).toHaveBeenCalledTimes(1)
      expect(handleFocus).toHaveBeenCalledTimes(1)
    })

    test('componentWillUnmount - Cleanup our document event listeners', () => {
      FontManager.prototype.container = { focus: jest.fn() }

      const map = {}

      document.removeEventListener = jest.fn((event, cb) => { map[event] = cb })

      const focus = jest.spyOn(FontManager.prototype.container, 'focus')
      const handleFocus = jest
        .spyOn(FontManager.prototype, 'handleFocus')
        .mockImplementation(() => jest.fn())
      const wrapper = shallow(<FontManager {...props} />)

      // Call componentDidMount()
      wrapper.instance().componentWillUnmount()
      // Simulate 'tab' keyboard press
      map.focus({ keyCode: 9, stopPropagation: jest.fn() })

      expect(focus).toHaveBeenCalledTimes(0)
      expect(handleFocus).toHaveBeenCalledTimes(1)
    })
  })

  describe('RUN COMPONENT METHODS', () => {
    test('handleFocus() - When a focus event is fired and it\'s not apart of any DOM elements in our container we will focus the container instead.', () => {
      const handleFocus = jest
        .spyOn(FontManager.prototype, 'handleFocus')
        .mockImplementation(() => jest.fn())
      const wrapper = shallow(<FontManager {...props} />)

      // Call handleFocus()
      wrapper.instance().handleFocus()

      expect(handleFocus).toHaveBeenCalledTimes(1)
    })
  })

  describe('RENDERS COMPONENT', () => {
    test('render <FontManager /> component', () => {
      const component = findByTestAttr(wrapper, 'component-FontManager')

      expect(component.length).toBe(1)
    })

    test('render <FontManagerHeader /> component', () => {
      expect(wrapper.find('FontManagerHeader').length).toBe(1)
    })

    test('render <FontManagerBody /> component', () => {
      expect(wrapper.find('Connect(FontManagerBody)').length).toBe(1)
    })
  })
})
