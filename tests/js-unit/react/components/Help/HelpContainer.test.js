import React from 'react'
import { shallow } from 'enzyme'
import { storeFactory, findByTestAttr } from '../../testUtils'
import ConnectedHelpContainer, { HelpContainer } from '../../../../../src/assets/js/react/components/Help/HelpContainer'

describe('Help - HelpContainer.js', () => {

  const getDataMock = jest.fn()
  const deleteResultMock = jest.fn()

  describe('Check for redux properties', () => {

    const setup = (state = {}) => {
      const store = storeFactory(state)
      const wrapper = shallow(<ConnectedHelpContainer store={store} />).dive().dive()

      return wrapper
    }

    test('has access to `loading` state', () => {
      const wrapper = setup()
      const loadingProp = wrapper.instance().props.loading

      expect(loadingProp).toBe(false)
    })

    test('has access to `results` state', () => {
      const results = [
        {
          id: 0,
          link: 'https://gravitypdf.com/documentation/v5/user-global-settings/',
          title: { rendered: 'Global Settings' },
          excerpt: { rendered: '<p>Gravity PDF is fully integrated into Gravity Forms. The PDF settings are located in a separate section in Gravity Forms own settings area. You can find it by navigating to&#8230;</p> ' }
        },
        {
          id: 1,
          link: 'https://gravitypdf.com/documentation/v5/user-setup-pdf/',
          title: { rendered: 'Setup PDF' },
          excerpt: { rendered: '<p>Creating a PDF for an individual Gravity Form is similar to creating your form&#8217;s notifications (and is found in the same location). There are a lot of options available to&#8230;</p> ' }
        }
      ]
      const wrapper = setup({ help: { results } })
      const resultsProp = wrapper.instance().props.helpResult

      expect(resultsProp).toBe(results)
      expect(resultsProp).toHaveLength(2)
    })

    test('has access to `error` state', () => {
      const error = 'An error occurred. Please try again'
      const wrapper = setup({ help: { error } })
      const errorProp = wrapper.instance().props.error

      expect(errorProp).toBe(error)
    })
  })

  describe('Component functions', () => {

    test('handleChange() - handle onChange Event for the Search Input', done => {
      const event = { target: { value: 'forms' } }
      const wrapper = shallow(<HelpContainer getData={getDataMock} />)
      const instance = wrapper.instance()
      instance.handleChange(event)

      expect(wrapper.state('searchInput')).toBe('forms')
      // Add timout since debounce is setup in actual component
      setTimeout(() => {
        expect(getDataMock.mock.calls.length).toBe(1)
        done()
      }, 500)
    })

    test('searchInputLength() - check for search input length that is > 3 and pass to redux action', done => {
      const wrapper = shallow(<HelpContainer getData={getDataMock} />)
      const instance = wrapper.instance()
      instance.searchInputLength('installation')

      // Add timout since debounce is setup in actual component
      setTimeout(() => {
        expect(getDataMock.mock.calls.length).toBe(1)
        done()
      }, 500)
    })

    test('searchInputLength() - check for search input length < = 3 and pass to redux action', done => {
      const wrapper = shallow(<HelpContainer deleteResult={deleteResultMock} />)
      const instance = wrapper.instance()
      instance.searchInputLength('ins')

      // Add timout since debounce is setup in actual component
      setTimeout(() => {
        expect(deleteResultMock.mock.calls.length).toBe(1)
        done()
      }, 500)
    })
  })

  const wrapper = shallow(<HelpContainer />)

  test('renders <HelpContainer /> component container', () => {
    const component = findByTestAttr(wrapper, 'component-help-container')

    expect(component.length).toBe(1)
  })

  test('renders input search box', () => {
    const component = findByTestAttr(wrapper, 'component-input')

    expect(component.length).toBe(1)
  })

  test('check input search box functionality', done => {
    const newWrapper = shallow(<HelpContainer getData={getDataMock} />)
    newWrapper.find('input').simulate('change', { target: { value: 'installation' } })

    expect(newWrapper.state('searchInput')).toBe('installation')
    // Add timout since debounce is setup in actual component
    setTimeout(() => {
      expect(getDataMock.mock.calls.length).toBe(1)
      done()
    }, 500)
  })

  test('renders <DisplayResultContainer /> component', () => {
    expect(wrapper.find('DisplayResultContainer').length).toEqual(1)
  })
})
