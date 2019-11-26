import React from 'react'
import { shallow, mount } from 'enzyme'
import { Provider } from 'react-redux'
import { storeFactory, findByTestAttr } from '../../testUtils'
import { MemoryRouter } from 'react-router-dom'
import configureMockStore from 'redux-mock-store'
import ConnectedTemplateSingle, { TemplateSingle } from '../../../../../src/assets/js/react/components/Template/TemplateSingle'

describe('Template - TemplateSingle.js', () => {

  let wrapper
  let component
  let props

  describe('Check for redux properties', () => {
    const setup = (state = {}) => {

      props = { match: { params: { id: 'rubix' } } }

      const store = storeFactory(state)
      const wrapper = shallow(<ConnectedTemplateSingle store={store} {...props} />).dive().dive()

      return wrapper
    }

    setup()

    test('has access to `list` state', () => {
      wrapper = setup()
      const listProp = wrapper.instance().props.templates

      expect(listProp).toBeInstanceOf(Array)
    })

    test('has access to `activeTemplate` state', () => {
      const templates = [
        { id: 'zadani', compatible: true },
        { id: 'rubix', compatible: true },
        { id: 'focus-gravity', compatible: true }
       ]
      wrapper = setup({ template: { list: templates, activeTemplate: 'focus-gravity' } })
      const activeTemplateProp = wrapper.instance().props.activeTemplate

      expect(activeTemplateProp).toBe('focus-gravity')
    })
  })

  describe('Run Lifecycle methods', () => {

    test('shouldComponentUpdate() - Ensure the component doesn\'t try and re-render when a template isn\'t found', () => {
      props = { template: { template: 'Rubix' } }
      let nextProps
      let shouldComponentUpdate

      nextProps = {
        template: null
      }

      wrapper = shallow(<TemplateSingle {...props} />)
      shouldComponentUpdate = wrapper.instance().shouldComponentUpdate(nextProps)

      expect(shouldComponentUpdate).toBe(false)

      nextProps = {
        template: 'rubix'
      }

      shouldComponentUpdate = wrapper.instance().shouldComponentUpdate(nextProps)

      expect(shouldComponentUpdate).toBe(true)
    })
  })

  describe('<TemplateHeaderNavigation /> and <TemplateFooterActions /> components', () => {

    const setup = () => {
      const mockStore = configureMockStore()
      const store = mockStore({})

      props = {
        templates: [{ id: 3, template: 'Rubix' }],
        template: { id: 3, template: 'Rubix', path: '/rubix',  },
        templateIndex: 3
      }

      wrapper = mount(
        <Provider store={store} >
          <MemoryRouter>
            <TemplateSingle {...props} />
          </MemoryRouter>
        </Provider>
      )

      return wrapper
    }

    component = setup()

    test('renders <TemplateHeaderNavigation /> component', () => {
      expect(component.find('TemplateHeaderNavigation').length).toBe(1)
    })

    test('renders <TemplateFooterActions /> component', () => {
      expect(component.find('TemplateFooterActions').length).toBe(1)
    })
  })

  props = { template: { template: 'Rubix' } }
  wrapper = shallow(<TemplateSingle {...props} />)

  test('renders <TemplateSingle /> component', () => {
    component = findByTestAttr(wrapper, 'component-templateSingle')

    expect(component.length).toBe(1)
  })

  test('renders <TemplateScreenshots /> component', () => {
    expect(wrapper.find('TemplateScreenshots').length).toBe(1)
  })

  test('renders <CurrentTemplate /> component', () => {
    expect(wrapper.find('CurrentTemplate').length).toBe(1)
  })

  test('renders <Name /> component', () => {
    expect(wrapper.find('Name').length).toBe(1)
  })

  test('renders <Author /> component', () => {
    expect(wrapper.find('Author').length).toBe(1)
  })

  test('renders <Group /> component', () => {
    expect(wrapper.find('Group').length).toBe(1)
  })

  test('renders <ShowMessage /> component if long_message is found', () => {
    wrapper = shallow(<TemplateSingle template={{ template: 'Rubix', long_message: 'text' }} />)
    component = findByTestAttr(wrapper, 'component-showMessageLong_message')

    expect(component.length).toBe(1)
  })

  test('renders <ShowMessage /> component if long_error is found', () => {
    wrapper = shallow(<TemplateSingle template={{ template: 'Rubix', long_error: 'text' }} />)
    component = findByTestAttr(wrapper, 'component-showMessageLong_error')

    expect(component.length).toBe(1)
  })

  test('renders <Description /> component', () => {
    expect(wrapper.find('Description').length).toBe(1)
  })

  test('renders <Tags /> component', () => {
    expect(wrapper.find('Tags').length).toBe(1)
  })
})
