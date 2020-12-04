import React from 'react'
import { shallow } from 'enzyme'
import { storeFactory, findByTestAttr } from '../../testUtils'
import ConnectedTemplateSingle, { TemplateSingle } from '../../../../../src/assets/js/react/components/Template/TemplateSingle'

describe('Template - TemplateSingle.js', () => {

  describe('CHECK FOR REDUX PROPERTIES', () => {
    const setup = (state = {}) => {
      const props = { match: { params: { id: 'rubix' } } }
      const store = storeFactory(state)
      const wrapper = shallow(<ConnectedTemplateSingle store={store} {...props} />).dive().dive()

      return wrapper
    }

    setup()

    test('has access to `list` state', () => {
      const wrapper = setup()
      const listProp = wrapper.instance().props.templates

      expect(listProp).toBeInstanceOf(Array)
    })

    test('has access to `activeTemplate` state', () => {
      const templates = [
        {
          id: 'zadani',
          compatible: true
        },
        {
          id: 'rubix',
          compatible: true
        },
        {
          id: 'focus-gravity',
          compatible: true
        }
      ]
      const wrapper = setup({
        template: {
          list: templates,
          activeTemplate: 'focus-gravity'
        }
      })
      const activeTemplateProp = wrapper.instance().props.activeTemplate

      expect(activeTemplateProp).toBe('focus-gravity')
    })
  })

  describe('RUN LIFECYCLE METHODS', () => {
    test('shouldComponentUpdate() - Ensure the component doesn\'t try and re-render when a template isn\'t found', () => {
      const props = { template: { template: 'Rubix' } }
      let nextProps
      let shouldComponentUpdate

      nextProps = { template: null }
      const wrapper = shallow(<TemplateSingle {...props} />)
      shouldComponentUpdate = wrapper.instance().shouldComponentUpdate(nextProps)

      expect(shouldComponentUpdate).toBe(false)

      nextProps = { template: 'rubix' }
      shouldComponentUpdate = wrapper.instance().shouldComponentUpdate(nextProps)

      expect(shouldComponentUpdate).toBe(true)
    })
  })

  describe('RENDERS COMPONENT', () => {
    describe('<TemplateHeaderNavigation /> and <TemplateFooterActions /> components', () => {
      const props = {
        template: {
          id: 3,
          template: 'Rubix',
          path: '/rubix'
        }
      }
      const wrapper = shallow(<TemplateSingle {...props} />).dive()

      test('renders <TemplateHeaderNavigation /> component', () => {
        expect(wrapper.find('withRouter(Connect(TemplateHeaderNavigation))').length).toBe(1)
      })

      test('renders <TemplateFooterActions /> component', () => {
        expect(wrapper.find('TemplateFooterActions').length).toBe(1)
      })
    })

    // Mock props
    const props = { template: { template: 'Rubix' } }

    test('render <TemplateSingle /> component', () => {
      const wrapper = shallow(<TemplateSingle {...props} />)
      const component = findByTestAttr(wrapper, 'component-templateSingle')

      expect(component.length).toBe(1)
    })

    test('render <TemplateScreenshots /> component', () => {
      const wrapper = shallow(<TemplateSingle {...props} />)

      expect(wrapper.find('TemplateScreenshots').length).toBe(1)
    })

    test('render <CurrentTemplate /> component', () => {
      const wrapper = shallow(<TemplateSingle {...props} />)

      expect(wrapper.find('CurrentTemplate').length).toBe(1)
    })

    test('render <Name /> component', () => {
      const wrapper = shallow(<TemplateSingle {...props} />)

      expect(wrapper.find('Name').length).toBe(1)
    })

    test('render <Author /> component', () => {
      const wrapper = shallow(<TemplateSingle {...props} />)

      expect(wrapper.find('Author').length).toBe(1)
    })

    test('render <Group /> component', () => {
      const wrapper = shallow(<TemplateSingle {...props} />)

      expect(wrapper.find('Group').length).toBe(1)
    })

    test('render <ShowMessage /> component if long_message is found', () => {
      const wrapper = shallow(
        <TemplateSingle template={{
          template: 'Rubix',
          long_message: 'text'
        }} />
      )
      const component = findByTestAttr(wrapper, 'component-showMessageLong_message')

      expect(component.length).toBe(1)
    })

    test('render <ShowMessage /> component if long_error is found', () => {
      const wrapper = shallow(<TemplateSingle template={{
        template: 'Rubix',
        long_error: 'text'
      }} />)
      const component = findByTestAttr(wrapper, 'component-showMessageLong_error')

      expect(component.length).toBe(1)
    })

    test('render <Description /> component', () => {
      const wrapper = shallow(<TemplateSingle {...props} />)

      expect(wrapper.find('Description').length).toBe(1)
    })

    test('render <Tags /> component', () => {
      const wrapper = shallow(<TemplateSingle {...props} />)

      expect(wrapper.find('Tags').length).toBe(1)
    })
  })
})
