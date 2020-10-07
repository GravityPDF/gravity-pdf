import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import AdvancedButton from '../../../../../src/assets/js/react/components/FontManager/AdvancedButton'

describe('FontManager - AdvancedButton.js', () => {

  // Mock component props data
  const props = { history: { push: jest.fn() } }
  const wrapper = shallow(<AdvancedButton {...props} />)

  describe('RUN COMPONENT METHODS', () => {
    test('handleClick() - Handle advanced button click and open the font manager modal', () => {
      const instance = wrapper.instance()
      const e = { preventDefault: jest.fn() }

      instance.handleClick(e)

      expect(props.history.push.mock.calls.length).toBe(1)
    })
  })

  describe('RENDERS COMPONENT', () => {
    test('render <AdvancedButton /> component- ', () => {
      const component = findByTestAttr(wrapper, 'component-AdvancedButton')

      expect(component.length).toBe(1)
      expect(component.find('button').text()).toBe('Advanced')
    })
  })
})
