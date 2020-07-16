import React from 'react'
import { shallow, mount } from 'enzyme'
import { Provider } from 'react-redux'
import { findByTestAttr } from '../../testUtils'
import { MemoryRouter } from 'react-router-dom'
import configureMockStore from 'redux-mock-store'
import TemplateFooterActions from '../../../../../src/assets/js/react/components/Template/TemplateFooterActions'

describe('Template - TemplateFooterActions.js', () => {

  let wrapper

  describe('Component functions', () => {

    test('notCoreTemplate() - Check if the current PDF template is a core template or not (i.e is shipped with Gravity PDF)', () => {
      const template = {
        compatible: false,
        path: ''
      }
      wrapper = shallow(<TemplateFooterActions template={template} />)
      const notCoreTemplate = jest.spyOn(wrapper.instance(), 'notCoreTemplate')
      wrapper.instance().notCoreTemplate(template)

      expect(notCoreTemplate).toHaveBeenCalledTimes(1)
    })
  })

  test('renders <TemplateFooterActions /> component', () => {
    const template = {
      compatible: false,
      path: ''
    }
    wrapper = shallow(<TemplateFooterActions template={template} />)
    const component = findByTestAttr(wrapper, 'component-templateFooterActions')

    expect(component.length).toBe(1)
  })

  test('renders <TemplateActivateButton /> component', () => {
    const template = {
      compatible: true,
      path: ''
    }
    wrapper = shallow(<TemplateFooterActions template={template} isActiveTemplate={false} />)

    expect(wrapper.find('withRouter(Connect(TemplateActivateButton))').length).toBe(1)
  })

  test('renders <TemplateDeleteButton /> component', () => {
    const mockStore = configureMockStore()
    const store = mockStore({ template: { templateProcessing: '' } })
    const template = {
      compatible: true,
      path: '/'
    }
    wrapper = mount(
      <Provider store={store}>
        <MemoryRouter>
          <TemplateFooterActions
            template={template}
            isActiveTemplate={false}
            pdfWorkingDirPath={'/'}
          />
        </MemoryRouter>
      </Provider>
    )

    expect(wrapper.find('TemplateDeleteButton').length).toBe(1)
  })
})
