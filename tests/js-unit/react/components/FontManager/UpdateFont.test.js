import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import UpdateFont from '../../../../../src/assets/js/react/components/FontManager/UpdateFont'

describe('FontManager - UpdateFont.js', () => {

  // Mock component props
  const props = {
    id: 'firasanslight',
    fontList: [{
      font_name: 'Fira Sans Light',
      id: 'firasanslight'
    }],
    label: '',
    onHandleInputChange: jest.fn(),
    onHandleUpload: jest.fn(),
    onHandleDeleteFontStyle: jest.fn(),
    onHandleCancelEditFont: jest.fn(),
    onHandleCancelEditFontKeypress: jest.fn(),
    onHandleSubmit: jest.fn(),
    validateLabel: false,
    validateRegular: false,
    disableUpdateButton: false,
    fontStyles: {},
    msg: {},
    loading: false,
    tabIndexFontName: '',
    tabIndexFontFiles: '',
    tabIndexFooterButtons: ''
  }
  const wrapper = shallow(<UpdateFont {...props} />)

  describe('RENDERS COMPONENT', () => {
    test('render <UpdateFont /> component', () => {
      const component = findByTestAttr(wrapper, 'component-UpdateFont')

      expect(component.length).toBe(1)
    })

    test('render font name input box', () => {
      expect(wrapper.find('input#gfpdf-update-font-name-input').length).toBe(1)
    })

    test('render font name validation error', () => {
      expect(wrapper.find('span.required').length).toBe(1)
    })

    test('render <FontVariant /> component', () => {
      expect(wrapper.find('FontVariant').length).toBe(1)
    })

    test('render <AddUpdateFontFooter /> component', () => {
      expect(wrapper.find('Connect(AddUpdateFontFooter)').length).toBe(1)
    })
  })
})
