import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import { FontListItems } from '../../../../../src/assets/js/react/components/FontManager/FontListItems'
import * as utilities from '../../../../../src/assets/js/react/utilities/FontManager/toggleUpdateFont'

describe('FontManager - FontListItems.js', () => {

  // Mock component props
  const props = {
    history: {
      location: { pathname: 'abc' },
      push: jest.fn()
    },
    clearAddFontMsg: jest.fn(),
    msg: { success: { addFont: 'success' } },
    deleteFont: jest.fn(),
    selectFont: jest.fn(),
    moveSelectedFontToTop: jest.fn(),
    fontList: [
      {
        font_name: 'Fira Sans Light',
        id: 'firasanslight',
        regular: 'FiraSans-Light.ttf',
        italics: 'FiraSans-LightItalic.ttf',
        bold: 'FiraSans-Medium.ttf',
        bolditalics: 'FiraSans-MediumItalic.ttf'
      }
    ],
    searchResult: null,
    selectedFont: 'roboto',
    loading: true
  }
  const wrapper = shallow(<FontListItems {...props} />)

  describe('RUN LIFECYCLE METHODS', () => {
    test('componentDidMount() - Call the method handleDisableSelectFields()', () => {
      // Mock font manager select box DOM
      document.body.innerHTML =
        '<select id="gfpdf_settings[font]" name="gfpdf_settings[font]">' +
        ' <optgroup label="User-Defined Fonts">' +
        '   <option value="z" />' +
        '   <option value="c" />' +
        ' </optgroup>' +
        '</select>'

      const instance = wrapper.instance()
      const handleDisableSelectFields = jest.spyOn(instance, 'handleDisableSelectFields')

      instance.componentDidMount()

      expect(handleDisableSelectFields).toHaveBeenCalledTimes(1)
    })

    test('componentDidMount() - Call the method handleMoveSelectedFontAtTheTopOfTheList()', () => {
      // Mock font manager select box DOM
      document.body.innerHTML =
        '<select id="gfpdf_settings[font]" name="gfpdf_settings[font]">' +
        ' <optgroup label="User-Defined Fonts">' +
        '   <option value="z" />' +
        '   <option value="c" />' +
        ' </optgroup>' +
        '</select>'

      const wrapper = shallow(<FontListItems {...props} selectedFont='roboto' />)
      const instance = wrapper.instance()
      const handleMoveSelectedFontAtTheTopOfTheList = jest.spyOn(instance, 'handleMoveSelectedFontAtTheTopOfTheList')

      instance.componentDidMount()

      expect(handleMoveSelectedFontAtTheTopOfTheList).toHaveBeenCalledTimes(1)
      expect(props.moveSelectedFontToTop).toHaveBeenCalledTimes(1)
    })

    test('componentDidUpdate() - Call the method handleResetLoadingState() and handleMoveSelectedFontAtTheTopOfTheList()', () => {
      // Mock update font panel DOM
      document.body.innerHTML =
        '<div class="update-font show">' +
        '</div>'

      const wrapper = shallow(<FontListItems {...props} loading={false} />)
      const instance = wrapper.instance()
      const toggleUpdateFont = jest.spyOn(utilities, 'toggleUpdateFont')
      const handleResetLoadingState = jest.spyOn(instance, 'handleResetLoadingState')
      const handleMoveSelectedFontAtTheTopOfTheList = jest.spyOn(instance, 'handleMoveSelectedFontAtTheTopOfTheList')
      const prevProps = {
        fontList: [
          {
            font_name: 'Arial',
            id: 'arial',
            regular: 'Arial.ttf',
            italics: 'Arial-Italic.ttf',
            bold: 'Arial-Bold.ttf',
            bolditalics: 'Arial-BoldItalics.ttf'
          }
        ],
        loading: true,
        selectedFont: ''
      }

      instance.componentDidUpdate(prevProps)

      expect(handleResetLoadingState).toHaveBeenCalledTimes(1)
      expect(toggleUpdateFont).toHaveBeenCalledTimes(1)
      expect(handleMoveSelectedFontAtTheTopOfTheList).toHaveBeenCalledTimes(1)
    })
  })

  describe('RUN COMPONENT METHODS', () => {
    test('handleDisableSelectFields() - Disable select font name functionality (radio button)', () => {
      delete window.location
      window.location = { search: '?page=gf_settings&subview=PDF&tab=tools' }

      const instance = wrapper.instance()

      instance.handleDisableSelectFields()

      expect(wrapper.state('disableSelectFontName')).toBe(true)
    })

    test('handleSetSelectedFontNameValue() - If location is under global or general settings', () => {
      // Mock font manager select box DOM
      document.body.innerHTML =
        '<select id="gfpdf_settings[default_font]" name="gfpdf_settings[default_font]">' +
        ' <optgroup label="User-Defined Fonts">' +
        '   <option value="z" />' +
        '   <option value="c" />' +
        ' </optgroup>' +
        '</select>'

      const instance = wrapper.instance()
      const handleCheckSelectBoxValue = jest.spyOn(instance, 'handleCheckSelectBoxValue')

      instance.handleSetSelectedFontNameValue('general')

      expect(handleCheckSelectBoxValue).toHaveBeenCalledTimes(1)
      expect(props.selectFont).toHaveBeenCalledTimes(1)
    })

    test('handleSetSelectedFontNameValue() - If location is not under global or general settings', () => {
      // Mock font manager select box DOM
      document.body.innerHTML =
        '<select id="gfpdf_settings[font]" name="gfpdf_settings[font]">' +
        ' <optgroup label="User-Defined Fonts">' +
        '   <option value="z" />' +
        '   <option value="c" />' +
        ' </optgroup>' +
        '</select>'

      const instance = wrapper.instance()
      const handleCheckSelectBoxValue = jest.spyOn(instance, 'handleCheckSelectBoxValue')

      instance.handleSetSelectedFontNameValue('435')

      expect(handleCheckSelectBoxValue).toHaveBeenCalledTimes(1)
      expect(props.selectFont).toHaveBeenCalledTimes(1)
    })

    test('handleCheckSelectBoxValue() - Check if font manager default selected font type is listed on custom font list (true)', () => {
      const fontList = [{ id: 'z' }, { id: 'c' }]
      const value = 'z'
      const instance = wrapper.instance()

      expect(instance.handleCheckSelectBoxValue(fontList, value)).toBe(true)
    })

    test('handleCheckSelectBoxValue() - Check if font manager default selected font type is listed on custom font list (false)', () => {
      const fontList = [{ id: 'z' }, { id: 'c' }]
      const value = 'a'
      const instance = wrapper.instance()

      expect(instance.handleCheckSelectBoxValue(fontList, value)).toBe(false)
    })

    test('handleResetLoadingState() - Reset deleteId state', () => {
      const instance = wrapper.instance()

      instance.handleResetLoadingState()

      expect(wrapper.state('deleteId')).toBe('')
    })

    test('handleMoveSelectedFontAtTheTopOfTheList() - Move selected font at the very top of the font list', () => {
      const instance = wrapper.instance()

      instance.handleMoveSelectedFontAtTheTopOfTheList('roboto')

      expect(props.moveSelectedFontToTop).toHaveBeenCalledTimes(1)
    })

    test('handleFontClick() - Display or hide update font panel', () => {
      // Mock update font panel DOM
      document.body.innerHTML =
        '<div class="update-font show">' +
        '</div>'

      const toggleUpdateFont = jest.spyOn(utilities, 'toggleUpdateFont')
      const instance = wrapper.instance()

      instance.handleFontClick('arial')

      expect(props.clearAddFontMsg).toHaveBeenCalledTimes(1)
      expect(toggleUpdateFont).toHaveBeenCalledTimes(1)
    })

    test('handleFontClickKeypress() - Listen to an \'enter\' keyboard event on font list item', () => {
      // Mock update font panel DOM
      document.body.innerHTML =
        '<div class="update-font show">' +
        '</div>'

      const instance = wrapper.instance()
      const handleFontClick = jest.spyOn(instance, 'handleFontClick')
      const e = { keyCode: 13 }

      instance.handleFontClickKeypress(e, 'arial')

      expect(handleFontClick).toHaveBeenCalledTimes(1)
    })

    test('handleDeleteFont() - Handle request of font deletion', () => {
      global.confirm = () => true

      const instance = wrapper.instance()
      const e = { stopPropagation: jest.fn() }

      instance.handleDeleteFont(e, 'arial')

      expect(wrapper.state('deleteId')).toBe('arial')
      expect(props.deleteFont).toHaveBeenCalledTimes(1)
    })

    test('handleDeleteFontKeypress() - Listen to an \'enter\' keyboard event for font deletion', () => {
      global.confirm = () => true

      const instance = wrapper.instance()
      const handleDeleteFont = jest.spyOn(instance, 'handleDeleteFont')
      const e = {
        keyCode: 13,
        stopPropagation: jest.fn()
      }

      instance.handleDeleteFontKeypress(e, 'arial')

      expect(handleDeleteFont).toHaveBeenCalledTimes(1)
    })

    test('handleSelectFont() - Handle the process of selecting and deselecting of font type/name (radio button)', () => {
      const instance = wrapper.instance()
      const e = { target: { value: 'arial' } }

      instance.handleSelectFont(e, 'click')

      expect(props.selectFont).toHaveBeenCalledTimes(1)
    })

    test('handleSelectFontKeypress() - Listen to an \'enter\' or \'space\' keyboard event for selecting a font type/name (radio button)', () => {
      const instance = wrapper.instance()
      const handleSelectFont = jest.spyOn(instance, 'handleSelectFont')
      const e = {
        keyCode: 13,
        preventDefault: jest.fn(),
        stopPropagation: jest.fn(),
        target: { value: 'arial' }
      }

      instance.handleSelectFontKeypress(e)

      expect(handleSelectFont).toHaveBeenCalledTimes(1)
    })
  })

  describe('RENDERS COMPONENT', () => {
    test('render <FontListItems /> component', () => {
      const component = findByTestAttr(wrapper, 'component-FontListItems')

      expect(component.length).toBe(1)
    })

    test('render delete trash icon', () => {
      expect(wrapper.find('span.dashicons-trash').length).toBe(1)
    })

    test('render radio button for select font name', () => {
      const wrapper = shallow(<FontListItems {...props} />)

      expect(wrapper.find('input#selectFontName').length).toBe(1)
    })

    test('render font name', () => {
      expect(wrapper.find('span.font-name').text()).toBe('Fira Sans Light')
    })

    test('render <FontListIcon /> component', () => {
      expect(wrapper.find('FontListIcon')).toHaveLength(4)
    })
  })
})
