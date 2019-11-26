import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import { TemplateActivateButton } from '../../../../../src/assets/js/react/components/Template/TemplateActivateButton'
import { mapDispatchToProps } from '../../../../../src/assets/js/react/components/Template/TemplateActivateButton'

describe('Template - TemplateActivateButton.js', () => {

  const historyMock = { push: jest.fn() }
  const onTemplateSelectMock = jest.fn()

  describe('Check for redux properties', () => {

    const dispatch = jest.fn()

    test('check for mapDispatchToProps onTemplateSelect()', () => {
      mapDispatchToProps(dispatch).onTemplateSelect()

      expect(dispatch.mock.calls[0][0]).toEqual({ type: 'SELECT_TEMPLATE' })
    })
  })

  describe('Component functions', () => {

    test('handleSelectTemplate() - Update our route and trigger a Redux action to select the current template', () => {
      const wrapper = shallow(
        <TemplateActivateButton
          history={historyMock}
          onTemplateSelect={onTemplateSelectMock}
          template={{}}
        />
      )
      const instance = wrapper.instance()
      instance.handleSelectTemplate({ preventDefault() {}, stopPropagation() {} })

      expect(historyMock.push.mock.calls.length).toBe(1)
      expect(onTemplateSelectMock.mock.calls.length).toBe(1)
    })
  })

  test('renders <TemplateActivateButton /> component', () => {
    const wrapper = shallow(
      <TemplateActivateButton
        history={historyMock}
      />
    )
    const component = findByTestAttr(wrapper, 'component-templateActivateButton')

    expect(component.length).toBe(1)
  })

  test('renders button text', () => {
    const wrapper = shallow(
      <TemplateActivateButton
        history={historyMock}
        buttonText='Select'
      />
    )

    expect(wrapper.find('a').text()).toBe('Select')
  })

  test('check button click', () => {
    const wrapper = shallow(
      <TemplateActivateButton
        history={historyMock}
        onTemplateSelect={onTemplateSelectMock}
        template={{}}
      />
    )
    wrapper.simulate('click', { preventDefault() {}, stopPropagation() {} })

    expect(historyMock.push.mock.calls.length).toBe(1)
    expect(onTemplateSelectMock.mock.calls.length).toBe(1)
  })
})
