import React from 'react'
import { shallow, mount } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import FontVariant from '../../../../../src/assets/js/react/components/FontManager/FontVariant'

describe('FontManager - FontVariant.js', () => {

  const props = {
    state: 'addFont',
    fontStyles: {
      regular: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/FiraSans-Regular.ttf',
      italics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/FiraSans-Italic.ttf',
      bold: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/FiraSans-SemiBold.ttf',
      bolditalics: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/FiraSans-SemiBoldItalic.ttf'
    },
    validateRegular: true,
    onHandleUpload: jest.fn(),
    onHandleDeleteFontStyle: jest.fn(),
    msg: {},
    tabIndex: '146'
  }
  const wrapper = shallow(<FontVariant {...props} />)

  describe('RENDERS COMPONENT', () => {
    test('render <FontVariant /> component', () => {
      const component = findByTestAttr(wrapper, 'component-FontVariant')

      expect(component.length).toBe(1)
    })

    test('render <Dropzone /> component', () => {
      const component = findByTestAttr(wrapper, 'component-Dropzone')

      expect(component.length).toBe(4)
    })

    test('render add input field', () => {
      const wrapper = mount(<FontVariant {...props} fontStyles={{ regular: '' }} />)
      const component = findByTestAttr(wrapper, 'input-add')

      expect(component.length).toBe(1)
    })

    test('render delete input field', () => {
      const wrapper = mount(<FontVariant {...props} />)
      const component = findByTestAttr(wrapper, 'input-delete')

      expect(component.length).toBe(4)
    })

    test('render <FontVariantLabel /> component', () => {
      const wrapper = mount(<FontVariant {...props} />)

      expect(wrapper.find('FontVariantLabel').length).toBe(4)
    })
  })
})
