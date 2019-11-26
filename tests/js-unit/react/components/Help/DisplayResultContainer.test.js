import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import DisplayResultContainer from '../../../../../src/assets/js/react/components/Help/DisplayResultContainer'

describe('Help - DisplayResultContainer.js', () => {

  test('renders <DisplayResultContainer /> component container', () => {
    const props = {
      searchInput: 'installation',
      loading: false,
      helpResult: [],
      error: ''
    }
    const wrapper = shallow(<DisplayResultContainer {...props} />)
    const component = findByTestAttr(wrapper, 'component-search-results')

    expect(component.length).toBe(1)
  })

  test('renders null if searchInput.length <= 3', () => {
    const props = {
      searchInput: 'ins',
      loading: false,
      helpResult: [],
      error: ''
    }
    const wrapper = shallow(<DisplayResultContainer {...props} />)

    expect(wrapper.type()).toEqual(null)
  })

  test('renders <Spinner /> component', () => {
    const props = {
      searchInput: 'installation',
      loading: true,
      helpResult: [],
      error: ''
    }
    const wrapper = shallow(<DisplayResultContainer {...props} />)

    expect(wrapper.find('Spinner').length).toEqual(1)
  })

  test('renders <DisplayResultEmpty /> component', () => {
    const props = {
      searchInput: 'installation',
      loading: false,
      helpResult: [],
      error: ''
    }
    const wrapper = shallow(<DisplayResultContainer {...props} />)

    expect(wrapper.find('DisplayResultEmpty').length).toEqual(1)
  })

  test('renders <DisplayResultItem /> component', () => {
    const props = {
      searchInput: 'installation',
      loading: false,
      helpResult: [
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
      ],
      error: ''
    }
    const wrapper = shallow(<DisplayResultContainer {...props} />)

    expect(wrapper.find('DisplayResultItem').length).toEqual(2)
  })

  test('renders <DisplayError /> component', () => {
    const props = {
      searchInput: 'installation',
      loading: false,
      helpResult: [],
      error: 'An error occurred. Please try again'
    }
    const wrapper = shallow(<DisplayResultContainer {...props} />)

    expect(wrapper.find('DisplayError').length).toEqual(1)
  })
})
