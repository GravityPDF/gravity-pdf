import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import TemplateContainer from '../../../../../src/assets/js/react/components/Template/TemplateContainer'

describe('Template - TemplateContainer.js', () => {

  describe('RUN LIFECYCLE METHODS', () => {
    test('componentDidMount() - Add focus event to document option on mount', () => {
      TemplateContainer.prototype.container = { focus: jest.fn() }

      const map = {}

      document.addEventListener = jest.fn((event, cb) => {
        map[event] = cb
      })

      const focus = jest.spyOn(TemplateContainer.prototype.container, 'focus')
      const handleFocus = jest
        .spyOn(TemplateContainer.prototype, 'handleFocus')
        .mockImplementation(() => jest.fn())
      const wrapper = shallow(<TemplateContainer children={[]} />)

      // Call componentDidMount()
      wrapper.instance().componentDidMount()
      // Simulate 'tab' keyboard press
      map.focus({ keyCode: 9, stopPropagation: jest.fn() })

      expect(focus).toHaveBeenCalledTimes(1)
      expect(handleFocus).toHaveBeenCalledTimes(1)
    })

    test('componentWillUnmount() - Cleanup our document event listeners', () => {
      TemplateContainer.prototype.container = { focus: jest.fn() }

      const map = {}

      document.removeEventListener = jest.fn((event, cb) => {
        map[event] = cb
      })

      const focus = jest.spyOn(TemplateContainer.prototype.container, 'focus')
      const handleFocus = jest
        .spyOn(TemplateContainer.prototype, 'handleFocus')
        .mockImplementation(() => jest.fn())
      const wrapper = shallow(<TemplateContainer children={[]} />)

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
        .spyOn(TemplateContainer.prototype, 'handleFocus')
        .mockImplementation(() => jest.fn())
      const wrapper = shallow(<TemplateContainer children={[]} />)

      // Call handleFocus()
      wrapper.instance().handleFocus()

      expect(handleFocus).toHaveBeenCalledTimes(1)
    })
  })

  describe('RENDERS COMPONENT', () => {
    const wrapper = shallow(<TemplateContainer children={[]} />)

    test('render <Container /> component', () => {
      const component = findByTestAttr(wrapper, 'component-templateContainer')

      expect(component.length).toBe(1)
    })

    test('render <CloseDialog /> component', () => {
      expect(wrapper.find('withRouter(Connect(CloseDialog))').length).toBe(1)
    })
  })
})
