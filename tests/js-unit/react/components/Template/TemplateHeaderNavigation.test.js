import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import { TemplateHeaderNavigation } from '../../../../../src/assets/js/react/components/Template/TemplateHeaderNavigation'

describe('Template - TemplateHeaderNavigation.js', () => {

  const props = {
    templateIndex: 2,
    templates: [
      { id: 'blank-slate', template: 'Blank Slate' },
      { id: 'focus-gravity', template: 'Focus Gravity' },
      { id: 'rubix', template: 'Rubix' },
      { id: 'zadani', template: 'Zadani' }
    ],
    showPreviousTemplateText: 'Show previous',
    showNextTemplateText: 'Show next template'
  }
  const historyMock = { push: jest.fn() }

  describe('Component functions', () => {

    let wrapper

    test('handlePreviousTemplate() - Attempt to get the previous template in our list and update the URL', () => {
      wrapper = shallow(<TemplateHeaderNavigation {...props} history={historyMock} />)
      const instance = wrapper.instance()
      instance.handlePreviousTemplate({ preventDefault () {}, stopPropagation () {} })

      expect(historyMock.push.mock.calls.length).toBe(1)
    })

    test('handleNextTemplate() - Attempt to get the next template in our list and update the URL', () => {
      wrapper = shallow(<TemplateHeaderNavigation {...props} history={historyMock} />)
      const instance = wrapper.instance()
      instance.handleNextTemplate({ preventDefault () {}, stopPropagation () {} })

      expect(historyMock.push.mock.calls.length).toBe(1)
    })

    test('handleKeyPress() - Checks if the Left arrow keys are pressed and fire appropriate function', () => {
      const e = {
        keyCode: 37,
        preventDefault () {},
        stopPropagation () {}
      }

      wrapper = shallow(<TemplateHeaderNavigation {...props} history={historyMock} />)
      const instance = wrapper.instance()
      instance.handleKeyPress(e)

      expect(historyMock.push.mock.calls.length).toBe(1)
    })

    test('handleKeyPress() - Checks if the Right arrow keys are pressed and fire appropriate function', () => {
      const e = {
        keyCode: 39,
        preventDefault () {},
        stopPropagation () {}
      }

      wrapper = shallow(<TemplateHeaderNavigation {...props} history={historyMock} />)
      const instance = wrapper.instance()
      instance.handleKeyPress(e)

      expect(historyMock.push.mock.calls.length).toBe(1)
    })
  })

  describe('Run Lifecycle methods', () => {

    let wrapper

    test('componentDidMount() - Add window event listeners', () => {
      const map = {}
      window.addEventListener = jest.fn((event, cb) => {
        map[event] = cb
      })
      wrapper = shallow(<TemplateHeaderNavigation {...props} history={historyMock} />)
      const handleKeyPress = jest.spyOn(wrapper.instance(), 'handleKeyPress')
      wrapper.instance().componentDidMount()
      // simulate event
      map.keydown({ keyCode: 37, preventDefault () {}, stopPropagation () {} })

      expect(handleKeyPress).toHaveBeenCalledTimes(1)
    })

    test('componentWillUnmount() - Cleanup window event listeners', () => {
      const map = {}
      window.removeEventListener = jest.fn((event, cb) => {
        map[event] = cb
      })
      wrapper = shallow(<TemplateHeaderNavigation {...props} history={historyMock} />)
      const handleKeyPress = jest.spyOn(wrapper.instance(), 'handleKeyPress')
      wrapper.instance().componentWillUnmount()
      // simulate event
      map.keydown({ keyCode: 37, preventDefault () {}, stopPropagation () {} })

      expect(handleKeyPress).toHaveBeenCalledTimes(1)
    })
  })

  const wrapper = shallow(<TemplateHeaderNavigation {...props} />)

  test('renders <TemplateHeaderNavigation /> component', () => {
    const component = findByTestAttr(wrapper, 'component-templateHeaderNavigation')

    expect(component.length).toBe(1)
  })

  test('renders `show previous` and `show next` template buttons', () => {
    const previousButton = findByTestAttr(wrapper, 'component-showPreviousTemplateButton')
    const nextButton = findByTestAttr(wrapper, 'component-showNextTemplateButton')

    expect(previousButton.length).toBe(1)
    expect(nextButton.length).toBe(1)
  })

  test('renders screen reader text for `show previous` and `show next` template buttons', () => {
    expect(findByTestAttr(wrapper, 'component-showPreviousTemplateButton').text()).toBe('Show previous')
    expect(findByTestAttr(wrapper, 'component-showNextTemplateButton').text()).toBe('Show next template')
  })
})
