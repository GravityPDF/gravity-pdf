import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../testUtils'
import ShowMessage from '../../../../src/assets/js/react/components/ShowMessage'

describe('Components - ShowMessage.js', () => {

  let wrapper
  let component

  describe('RUN LIFECYCLE METHODS', () => {
    test('componentDidMount() - On mount, maybe set dismissable timer', () => {
      wrapper = shallow(<ShowMessage text='text' dismissable={true} />)
      const shouldSetTimer = jest.spyOn(wrapper.instance(), 'shouldSetTimer')
      const setTimer = jest.spyOn(wrapper.instance(), 'setTimer')
      wrapper.instance().componentDidMount()

      expect(shouldSetTimer).toHaveBeenCalledTimes(1)
      expect(setTimer).toHaveBeenCalledTimes(1)
    })

    test('componentDidUpdate() - Resets our state and timer when new props received', () => {
      const prevState = { visible: false }
      wrapper = shallow(<ShowMessage text='text' />)
      const shouldSetTimer = jest.spyOn(wrapper.instance(), 'shouldSetTimer')
      wrapper.instance().componentDidUpdate('', prevState)

      expect(shouldSetTimer).toHaveBeenCalledTimes(1)
      expect(wrapper.state('visible')).toBe(true)
    })

    test('componentWillUnmount() - Clear timeout on unmount', () => {
      wrapper = shallow(<ShowMessage text='text' dismissable={true} />)

      const clearTimeout = jest.spyOn(window, 'clearTimeout')

      wrapper.instance().componentWillUnmount()

      expect(clearTimeout).toHaveBeenCalledTimes(1)
    })
  })

  describe('RUN COMPONENT METHODS', () => {
    test('shouldSetTimer() - Check if we should make the message auto-dismissable', () => {
      wrapper = shallow(<ShowMessage text='text' dismissable={true} />)
      const setTimer = jest.spyOn(wrapper.instance(), 'setTimer')
      wrapper.instance().shouldSetTimer()

      expect(setTimer).toHaveBeenCalledTimes(1)
    })

    test('resetState() - Resets our state and timer', () => {
      wrapper = shallow(<ShowMessage text='text' dismissable={true} />)

      const instance = wrapper.instance()
      const shouldSetTimer = jest.spyOn(instance, 'shouldSetTimer')

      instance.resetState()

      expect(wrapper.state('visible')).toBe(true)
      expect(shouldSetTimer).toHaveBeenCalledTimes(1)
    })
  })

  describe('RENDERS COMPONENT', () => {
    test('renders <ShowMessage /> component', () => {
      wrapper = shallow(<ShowMessage text='text' error={true} />)
      component = findByTestAttr(wrapper, 'component-showMessage')

      expect(component.length).toBe(1)
      expect(component.text()).toBe('text')
      expect(component.hasClass('notice inline error')).toEqual(true)
    })

    test('renders <ShowMessage /> component with an empty <div />', () => {
      wrapper = shallow(<ShowMessage text='text' />)
      wrapper.setState({ visible: false })

      expect(wrapper.html()).toEqual('<div></div>')
    })
  })
})
