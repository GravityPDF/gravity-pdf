import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import { SearchBox } from '../../../../../src/assets/js/react/components/FontManager/SearchBox'

describe('FontManager - SearchBox.js', () => {

  const props = {
    id: 'arial',
    msg: { success: {} },
    searchResult: null,
    resetSearchResult: jest.fn(),
    searchFontList: jest.fn()
  }
  const wrapper = shallow(<SearchBox {...props} />)

  describe('RUN LIFECYCLE METHODS', () => {
    test('componentDidMount() - Add focus event to document on mount', () => {
      const mockRef = jest.fn()

      wrapper.instance().input = { focus: mockRef }
      wrapper.instance().componentDidMount()

      expect(mockRef).toHaveBeenCalledTimes(1)
    })

    test('componentDidUpdate() - Fires appropriate action based on redux store data', () => {
      const instance = wrapper.instance()
      const resetSearchState = jest.spyOn(instance, 'resetSearchState')
      const prevProps = { searchResult: [{}] }

      instance.componentDidUpdate(prevProps)

      expect(resetSearchState).toHaveBeenCalledTimes(2)
    })

    test('componentWillUnmount() - Call our redux action resetSearchResult() (true)', () => {
      wrapper.setState({ searchInput: 'arial' })
      wrapper.instance().componentWillUnmount()

      expect(props.resetSearchResult).toHaveBeenCalledTimes(1)
    })

    test('componentWillUnmount() - Call our redux action resetSearchResult() (false)', () => {
      wrapper.setState({ searchInput: '' })
      wrapper.instance().componentWillUnmount()

      expect(props.resetSearchResult).toHaveBeenCalledTimes(0)
    })
  })

  describe('RUN COMPONENT METHODS', () => {
    test('handleSearch() - Listen to search box input field change', () => {
      const e = { target: { value: 'arial' } }

      wrapper.instance().handleSearch(e)

      expect(wrapper.state('searchInput')).toBe('arial')
      expect(props.searchFontList).toHaveBeenCalledTimes(1)
    })

    test('resetSearchState() - Reset component searchInput state', () => {
      wrapper.instance().resetSearchState()

      expect(wrapper.state('searchInput')).toBe('')
    })
  })

  describe('RENDERS COMPONENT', () => {
    test('render <SearchBox /> component', () => {
      const component = findByTestAttr(wrapper, 'component-SearchBox')

      expect(component.length).toBe(1)
    })
  })
})
