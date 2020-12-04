import React from 'react'
import { shallow, mount } from 'enzyme'
import { storeFactory, findByTestAttr } from '../../testUtils'
import { MemoryRouter } from 'react-router-dom'
import ConnectedTemplateDeleteButton, {
  TemplateDeleteButton, mapDispatchToProps
} from '../../../../../src/assets/js/react/components/Template/TemplateDeleteButton'

describe('Template - TemplateDeleteButton.js', () => {
  const historyMock = { push: jest.fn(), pathname: '/' }
  const templateProcessingMock = jest.fn()
  const onTemplateDeleteMock = jest.fn()
  const addTemplateMock = jest.fn()
  const clearTemplateProcessingMock = jest.fn()

  describe('Check for redux properties', () => {
    const setup = (state = {}) => {
      const store = storeFactory(state)
      const wrapper = mount(
        <MemoryRouter>
          <ConnectedTemplateDeleteButton
            store={store}
          />
        </MemoryRouter>
      )

      return wrapper.find('TemplateDeleteButton')
    }
    const dispatch = jest.fn()

    test('has access to `templateProcessing` state', () => {
      const wrapper = setup({ template: { templateProcessing: 'success' } })
      const getTemplateProcessingProp = wrapper.instance().props.getTemplateProcessing

      expect(getTemplateProcessingProp).toBe('success')
    })

    test('check for mapDispatchToProps addTemplate()', () => {
      mapDispatchToProps(dispatch).addTemplate()

      expect(dispatch.mock.calls[0][0]).toEqual({ type: 'ADD_TEMPLATE' })
    })

    test('check for mapDispatchToProps onTemplateDelete()', () => {
      mapDispatchToProps(dispatch).onTemplateDelete()

      expect(dispatch.mock.calls[0][0]).toEqual({ type: 'DELETE_TEMPLATE' })
    })

    test('check for mapDispatchToProps templateProcessing()', () => {
      mapDispatchToProps(dispatch).templateProcessing()

      expect(dispatch.mock.calls[0][0]).toEqual({ type: 'TEMPLATE_PROCESSING' })
    })

    test('check for mapDispatchToProps clearTemplateProcessing()', () => {
      mapDispatchToProps(dispatch).clearTemplateProcessing()

      expect(dispatch.mock.calls[0][0]).toEqual({ type: 'CLEAR_TEMPLATE_PROCESSING' })
    })
  })

  describe('Component methods', () => {

    const wrapper = shallow(
      <TemplateDeleteButton
        template={{}}
        templateProcessing={templateProcessingMock}
        getTemplateProcessing='success'
        history={historyMock}
        onTemplateDelete={onTemplateDeleteMock}
        addTemplate={addTemplateMock}
        clearTemplateProcessing={clearTemplateProcessingMock}
      />
    )
    const instance = wrapper.instance()

    test('deleteTemplate() - Display a confirmation window asking user to verify they want template deleted. Once verified, we make an AJAX call to the server requesting template to be deleted.', () => {
      window.confirm = jest.fn().mockImplementation(() => true)
      const e = {preventDefault() {}, stopPropagation() {}}
      instance.deleteTemplate(e)

      expect(templateProcessingMock.mock.calls.length).toBe(1)
      expect(historyMock.push.mock.calls.length).toBe(1)
      expect(onTemplateDeleteMock.mock.calls.length).toBe(1)
    })

    test('ajaxFailed() - If the server cannot delete the template we re-add the template to our list and display an appropriate inline error message', () => {
      instance.ajaxFailed()

      expect(addTemplateMock.mock.calls.length).toBe(1)
      expect(historyMock.push.mock.calls.length).toBe(1)
      expect(clearTemplateProcessingMock.mock.calls.length).toBe(1)
    })
  })

  describe('Run lifecycle methods', () => {

    test('componentDidUpdate() - Fires appropriate action based on (success) Redux store data', () => {
      const props = { getTemplateProcessing: 'success' }
      const wrapper = shallow(
        <TemplateDeleteButton
          history={historyMock}
          {...props}
        />
      )
      wrapper.instance().componentDidUpdate()

      expect(historyMock.push.mock.calls.length).toBe(1)
    })

    test('componentDidUpdate() - Fires appropriate action based on (failed) Redux store data', () => {
      const props = {
        getTemplateProcessing: 'failed'
      }
      const wrapper = shallow(
        <TemplateDeleteButton
          addTemplate={addTemplateMock}
          history={historyMock}
          clearTemplateProcessing={clearTemplateProcessingMock}
          {...props}
        />
      )
      const ajaxFailed = jest.spyOn(wrapper.instance(), 'ajaxFailed')
      wrapper.instance().componentDidUpdate()

      expect(ajaxFailed).toHaveBeenCalledTimes(1)
      expect(addTemplateMock.mock.calls.length).toBe(1)
      expect(historyMock.push.mock.calls.length).toBe(1)
      expect(clearTemplateProcessingMock.mock.calls.length).toBe(1)
    })
  })

  describe('Renders component', () => {
    let wrapper = shallow(<TemplateDeleteButton buttonText={'Delete'} />)

    test('renders <TemplateDeleteButton /> component', () => {
      const component = findByTestAttr(wrapper, 'component-templateDeleteButton')

      expect(component.length).toBe(1)
    })

    test('display button text', () => {
      expect(wrapper.find('a').text()).toBe('Delete')
    })

    test('check button click', () => {
      window.confirm = jest.fn().mockImplementation(() => true)
      wrapper = shallow(
        <TemplateDeleteButton
          template={{}}
          templateProcessing={templateProcessingMock}
          getTemplateProcessing='success'
          history={historyMock}
          onTemplateDelete={onTemplateDeleteMock}
        />
      )
      const button = findByTestAttr(wrapper, 'component-templateDeleteButton')
      button.simulate('click', { preventDefault() {}, stopPropagation() {} })

      expect(templateProcessingMock.mock.calls.length).toBe(1)
      expect(historyMock.push.mock.calls.length).toBe(1)
      expect(onTemplateDeleteMock.mock.calls.length).toBe(1)
    })
  })
})
