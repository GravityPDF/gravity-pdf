import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import AddFont from '../../../../../src/assets/js/react/components/FontManager/AddFont'

describe('FontManager - AddFont.js', () => {

  // Mock component props
  const props = {
    label: '',
    onHandleInputChange: jest.fn(),
    onHandleUpload: jest.fn(),
    onHandleDeleteFontStyle: jest.fn(),
    onHandleSubmit: jest.fn(),
    validateLabel: false,
    validateRegular: false,
    fontStyles: {},
    msg: {},
    loading: false,
    tabIndexFontName: '',
    tabIndexFontFiles: '',
    tabIndexFooterButtons: ''
  }
  const wrapper = shallow(<AddFont {...props} />)

  describe('RENDERS COMPONENT', () => {
    test('render <AddFont /> component', () => {
      const component = findByTestAttr(wrapper, 'component-AddFont')

      expect(component.length).toBe(1)
    })

    test('render font name input box', () => {
      expect(wrapper.find('input#gfpdf-add-font-name-input').length).toBe(1)
    })

    test('render font name validation error', () => {
      expect(wrapper.find('span.required').length).toBe(1)
    })

    test('hide font name validation error', () => {
      const validateLabel = true
      const wrapper = shallow(<AddFont {...props} validateLabel={validateLabel} />)

      expect(wrapper.find('span.required').length).toBe(0)
    })

    test('render <FontVariant /> component', () => {
      expect(wrapper.find('FontVariant').length).toBe(1)
    })

    test('render <AddFontFooter /> component', () => {
      expect(wrapper.find('AddFontFooter').length).toBe(1)
    })
  })
})
