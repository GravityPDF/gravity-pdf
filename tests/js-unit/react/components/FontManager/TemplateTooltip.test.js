import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import { TemplateTooltip } from '../../../../../src/assets/js/react/components/FontManager/TemplateTooltip'

describe('FontManager - TemplateTooltip.js', () => {

  const props = { id: 'gotham' }
  const wrapper = shallow(<TemplateTooltip {...props} />)

  describe('RUN COMPONENT METHODS', () => {
    test('handleDisplayInfo() - Toggle state for template usage information box', () => {
      wrapper.instance().handleDisplayInfo()

      expect(wrapper.state('tooltip')).toBe(true)
    })

    test('handleContentHighlight() - Handle auto highlighting of the information box content once clicked', () => {
      const e = { target: { focus: jest.fn(), select: jest.fn() } }
      document.execCommand = jest.fn()

      wrapper.instance().handleContentHighlight(e)

      expect(e.target.focus).toHaveBeenCalledTimes(1)
      expect(e.target.select).toHaveBeenCalledTimes(1)
      expect(document.execCommand).toBeCalledWith('copy')
    })
  })

  describe('RENDERS COMPONENT', () => {
    test('render <TemplateTooltip /> component', () => {
      const component = findByTestAttr(wrapper, 'component-TemplateTooltip')

      expect(component.length).toBe(1)
    })

    test('render arrow right, arrow down and tooltip text link', () => {
      const component = shallow(<TemplateTooltip />)

      expect(component.find('.dashicons-arrow-right-alt2').length).toBe(1)

      component.setState({ tooltip: true })

      expect(component.find('.dashicons-arrow-down-alt2').length).toBe(1)
      expect(component.find('a').text()).toBe('View template usage')
      expect(component.find('textarea').length).toBe(1)
    })
  })
})
