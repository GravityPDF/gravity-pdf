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
      id: 'firasanslight',
      useOTL: 255,
      useKashida: 75
    }],
    label: '',
    kashida: 75,
    onHandleInputChange: jest.fn(),
    onHandleUpload: jest.fn(),
    onHandleDeleteFontStyle: jest.fn(),
    onHandleCancelEditFont: jest.fn(),
    onHandleCancelEditFontKeypress: jest.fn(),
    onHandleKashidaChange: jest.fn(),
    onHandleSubmit: jest.fn(),
    validateLabel: false,
    validateRegular: false,
    disableUpdateButton: false,
    fontStyles: {},
    msg: {},
    loading: false,
    tabIndexFontName: '',
    tabIndexFontFiles: '',
    tabIndexKashida: '',
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

    test('render <Kashida /> component', () => {
      expect(wrapper.find('Kashida').length).toBe(1)
    })

    test('render <AddFontFooter /> component', () => {
      expect(wrapper.find('AddFontFooter').length).toBe(1)
    })
  })
})
