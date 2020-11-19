import React from 'react'
import { shallow } from 'enzyme'
import { storeFactory, findByTestAttr } from '../../testUtils'
import ConnectedTemplateSearch, { TemplateSearch } from '../../../../../src/assets/js/react/components/Template/TemplateSearch'
import { mapDispatchToProps } from '../../../../../src/assets/js/react/components/Template/TemplateSearch'

describe('Template - TemplateSearch.js', () => {

  let wrapper
  let component
  const onSearchMock = jest.fn()

  describe('Check for redux properties', () => {

    const setup = (state = {}) => {
      const store = storeFactory(state)
      wrapper = shallow(<ConnectedTemplateSearch store={store} />).dive().dive()

      return wrapper
    }
    const dispatch = jest.fn()

    test('has access to `search` state', () => {
      wrapper = setup({ template: { search: 'test' } })
      const searchProp = wrapper.instance().props.search

      expect(searchProp).toBe('test')
    })

    test('check for mapDispatchToProps onSearch()', () => {
      mapDispatchToProps(dispatch).onSearch()

      expect(dispatch.mock.calls[0][0]).toEqual({ type: 'SEARCH_TEMPLATES' })
    })
  })

  describe('Component functions', () => {

    const e = { target: { value: 'rubix' }, persist() {} }

    test('handleSearch() - Handles our search event', done => {
      wrapper = shallow(<TemplateSearch onSearch={onSearchMock} />)
      wrapper.instance().handleSearch(e)

      // Add timout since debounce is setup in actual component
      setTimeout(() => {
        expect(onSearchMock.mock.calls.length).toBe(1)
        done()
      }, 300)
    })

    test('runSearch() - Update our Redux store with the search value', done => {
      wrapper = shallow(<TemplateSearch onSearch={onSearchMock} />)
      wrapper.instance().runSearch(e)

      // Add timout since debounce is setup in actual component
      setTimeout(() => {
        expect(onSearchMock.mock.calls.length).toBe(1)
        done()
      }, 300)
    })
  })

  describe('Run Lifecycle methods', () => {

    test('componentDidMount() - On mount, add focus to the search box', () => {
      const mockRef = jest.fn()
      wrapper = shallow(<TemplateSearch />)
      wrapper.instance().input = {
        focus: mockRef
      }
      wrapper.instance().componentDidMount()

      expect(mockRef).toHaveBeenCalledTimes(1)
    })
  })

  test('renders <TemplateSearch /> component and search input box', () => {
    wrapper = shallow(<TemplateSearch />)
    component = findByTestAttr(wrapper, 'component-templateSearch')

    expect(component.length).toBe(1)
    expect(wrapper.find('input').length).toBe(1)
  })
})
