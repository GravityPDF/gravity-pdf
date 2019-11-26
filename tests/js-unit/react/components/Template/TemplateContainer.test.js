import React from 'react'
import { shallow, mount } from 'enzyme'
import { MemoryRouter } from 'react-router-dom'
import { findByTestAttr } from '../../testUtils'
import Container from '../../../../../src/assets/js/react/components/Template/TemplateContainer'

describe('Template - TemplateContainer.js', () => {

  describe('Component functions', () => {

    test('handleFocus() - When a focus event is fired and it\'s not apart of any DOM elements in our container we will focus the container instead.', () => {
      const handleFocus = jest.spyOn(Container.prototype, 'handleFocus').mockImplementation(() => jest.fn())
      const wrapper = shallow(<Container children={[]} />)
      wrapper.instance().handleFocus()

      expect(handleFocus).toHaveBeenCalledTimes(1)
    })
  })

  describe('Run Lifecycle methods', () => {

    test('componentDidMount() - Add focus event to document option on mount', () => {
      const map = {}
      document.addEventListener = jest.fn((event, cb) => {
        map[event] = cb
      })
      const stopPropagation = jest.fn()
      const handleFocus = jest.spyOn(Container.prototype, 'handleFocus')
      const wrapper = mount(
        <MemoryRouter>
          <Container children={[]} />
        </MemoryRouter>
      )
      const instance = wrapper.instance()
      instance.refs = {
        container: {
          getRenderedComponent: jest.fn(() => ({
            focus: jest.fn()
          }))
        }
      }
      // Simulate Mocked for Escape/close keyboard pressKey
      map.focus({ key: 'Escape', keyCode: 27, stopPropagation })

      expect(handleFocus).toHaveBeenCalledTimes(1)
    })

    test('componentWillUnmount() - Cleanup our document event listeners', () => {
      let map = {}
      document.addEventListener = jest.fn((event, cb) => {
        map[event] = cb
      })
      const stopPropagation = jest.fn()
      const handleFocus = jest.spyOn(Container.prototype, 'handleFocus')
      const wrapper = mount(
        <MemoryRouter>
          <Container children={[]} />
        </MemoryRouter>
      )
      let instance = wrapper.instance()
      instance.refs = {
        container: {
          getRenderedComponent: jest.fn(() => ({
            focus: jest.fn()
          }))
        }
      }
      // Simulate Mocked for Escape/close keyboard pressKey
      map.focus({ key: 'Escape', keyCode: 27, stopPropagation })
      expect(findByTestAttr(wrapper, 'component-templateContainer').length).toBe(1)

      // componentWillUnmount()
      wrapper.unmount()

      expect(findByTestAttr(wrapper, 'component-templateContainer').length).toBe(0)

      expect(handleFocus).toHaveBeenCalledTimes(1)
    })
  })

  const wrapper = shallow(<Container closeRoute={'/template'} children={[]} />)

  test('renders <Container /> component', () => {
    const component = findByTestAttr(wrapper, 'component-templateContainer')

    expect(component.length).toBe(1)
  })

  test('renders <TemplateCloseDialog /> component', () => {
    expect(wrapper.find('withRouter(TemplateCloseDialog)').length).toBe(1)
  })
})
