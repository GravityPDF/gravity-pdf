import React from 'react'
import { shallow, mount } from 'enzyme'
import { storeFactory, findByTestAttr } from '../../testUtils'
import { MemoryRouter } from 'react-router-dom'
import ConnectedTemplateListItem, { TemplateListItem } from '../../../../../src/assets/js/react/components/Template/TemplateListItem'
import { mapDispatchToProps } from '../../../../../src/assets/js/react/components/Template/TemplateListItem'

describe('Template - TemplateListItem.js', () => {

  let wrapper
  let component
  const historyMock = { push: jest.fn() }
  const updateTemplateParamMock = jest.fn()

  describe('Check for redux properties', () => {

    const props = { template: { id: 'blank-slate', template: 'Blank Slate' } }
    const setup = (state = {}) => {
      const store = storeFactory(state)
      wrapper = mount(<MemoryRouter><ConnectedTemplateListItem store={store} {...props} /></MemoryRouter>)

      return wrapper.find('TemplateListItem')
    }
    const dispatch = jest.fn()

    setup()

    test('has access to `activeTemplate` state', () => {
      wrapper = setup({ template: { activeTemplate: 'blank-slate' } })
      const activeTemplateProp = wrapper.instance().props.activeTemplate

      expect(activeTemplateProp).toBe('blank-slate')
    })

    test('check for mapDispatchToProps updateTemplateParam()', () => {
      mapDispatchToProps(dispatch).updateTemplateParam()

      expect(dispatch.mock.calls[0][0]).toEqual({ type: 'UPDATE_TEMPLATE_PARAM' })
    })
  })

  describe('Component functions', () => {

    test('handleMaybeShowDetailedTemplate() - Check if the Enter key is pressed and not focused on a button then display the template details page', () => {
      const props = { template: { id: 'blank-slate', template: 'Blank Slate' } }
      const e = {
        keyCode: 13,
        target: { className: '' }
      }
      wrapper = shallow(<TemplateListItem {...props} history={historyMock} />)
      const showDetailedTemplate = jest.spyOn(wrapper.instance(), 'handleShowDetailedTemplate')
      wrapper.instance().handleMaybeShowDetailedTemplate(e)

      expect(showDetailedTemplate).toHaveBeenCalledTimes(1)
      expect(historyMock.push.mock.calls.length).toBe(1)
    })

    test('handleShowDetailedTemplate() - Update the URL to show the PDF template details page', () => {
      const props = { template: { id: 'blank-slate', template: 'Blank Slate' } }
      wrapper = shallow(<TemplateListItem {...props} history={historyMock} />)
      wrapper.instance().handleShowDetailedTemplate()

      expect(historyMock.push.mock.calls.length).toBe(1)
    })

    test('removeMessage() - Call Redux action to remove any stored messages for this template', () => {
      const props = { template: { id: 'blank-slate', template: 'Blank Slate' } }
      wrapper = shallow(<TemplateListItem {...props} updateTemplateParam={updateTemplateParamMock} />)
      wrapper.instance().removeMessage()

      expect(updateTemplateParamMock.mock.calls.length).toBe(1)
    })
  })

  test('renders <TemplateListItem /> component', () => {
    const props = { template: { id: 'blank-slate', template: 'Blank Slate' } }
    wrapper = shallow(<TemplateListItem {...props} />)
    component = findByTestAttr(wrapper, 'component-templateListItem')

    expect(component.length).toBe(1)
  })

  test('renders <TemplateScreenshot /> component', () => {
    const props = { template: { id: 'blank-slate', template: 'Blank Slate' } }
    wrapper = shallow(<TemplateListItem {...props} />)
    component = findByTestAttr(wrapper, 'component-templateScreenshot')

    expect(component.length).toBe(1)
  })

  test('renders <ShowMessage /> component if template error is found', () => {
    const props = {
      template: {
        id: 'blank-slate',
        template: 'Blank Slate',
        error: 'text'
      }
    }
    wrapper = shallow(<TemplateListItem {...props} />)
    component = findByTestAttr(wrapper, 'component-showMessage')

    expect(component.length).toBe(1)
  })

  test('renders <ShowMessage /> component if template message is found', () => {
    const props = {
      template: {
        id: 'blank-slate',
        template: 'Blank Slate',
        message: 'text'
      }
    }
    wrapper = shallow(<TemplateListItem {...props} />)
    component = findByTestAttr(wrapper, 'component-showMessage')

    expect(component.length).toBe(1)
  })

  test('renders <TemplateDetails /> component', () => {
    const props = { template: { id: 'blank-slate', template: 'Blank Slate' } }
    wrapper = shallow(<TemplateListItem {...props} />)
    component = findByTestAttr(wrapper, 'component-templateDetails')

    expect(component.length).toBe(1)
  })

  test('renders <Group /> component', () => {
    const props = { template: { id: 'blank-slate', template: 'Blank Slate' } }
    wrapper = shallow(<TemplateListItem {...props} />)
    component = findByTestAttr(wrapper, 'component-group')

    expect(component.length).toBe(1)
  })

  test('renders <Name /> component', () => {
    const props = { template: { id: 'blank-slate', template: 'Blank Slate' } }
    wrapper = shallow(<TemplateListItem {...props} />)
    component = findByTestAttr(wrapper, 'component-name')

    expect(component.length).toBe(1)
  })

  test('renders <TemplateActivateButton /> component', () => {
    const props = {
      template: {
        id: 'blank-slate',
        template: 'Blank Slate',
        compatible: true
      },
      activeTemplate: 'rubix'
    }
    wrapper = shallow(<TemplateListItem {...props} />)
    component = findByTestAttr(wrapper, 'component-templateActivateButton')

    expect(component.length).toBe(1)
  })
})
