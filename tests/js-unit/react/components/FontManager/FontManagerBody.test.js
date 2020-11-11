import React from 'react'
import { shallow } from 'enzyme'
import { findByTestAttr } from '../../testUtils'
import { FontManagerBody } from '../../../../../src/assets/js/react/components/FontManager/FontManagerBody'
import * as utilitiesA from '../../../../../src/assets/js/react/utilities/FontManager/adjustFontListHeight'
import * as utilitiesB from '../../../../../src/assets/js/react/utilities/FontManager/toggleUpdateFont'
import initialState from '../../../../../src/assets/js/react/components/FontManager/InitialAddUpdateState'

describe('FontManager - FontManagerBody.js', () => {

  const props = {
    getCustomFontList: jest.fn(),
    id: 'firasanslight',
    loading: true,
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
    msg: {
      success: { addFont: 'success' },
      error: { deleteFont: 'error' }
    },
    clearDropzoneError: jest.fn(),
    clearAddFontMsg: jest.fn(),
    editFont: jest.fn(),
    validationError: jest.fn(),
    deleteVariantError: jest.fn(),
    addFont: jest.fn(),
    history: {
      push: jest.fn(),
      location: { pathname: '\'/fontmanager/' }
    }
  }
  const wrapper = shallow(<FontManagerBody {...props} />)

  describe('RUN LIFECYCLE METHODS', () => {
    test('componentDidMount() - Call redux action getCustomFontList()', () => {
      const wrapper = shallow(<FontManagerBody {...props} id='' />)

      // Call componentDidMount()
      wrapper.instance().componentDidMount()

      expect(props.getCustomFontList).toHaveBeenCalledTimes(1)
    })

    test('componentDidMount() - Auto slide \'update font\' panel if refreshed', () => {
      const wrapper = shallow(<FontManagerBody {...props} />)
      const addClass = jest.spyOn(utilitiesB, 'addClass').mockImplementation(() => true)

      // Call componentDidMount()
      wrapper.instance().componentDidMount()

      expect(addClass).toHaveBeenCalledTimes(1)
    })

    test('componentDidUpdate() - Prevent fatal error event', () => {
      const wrapper = shallow(<FontManagerBody {...props} id='arial' />)
      const instance = wrapper.instance()
      const handleCheckValidId = jest.spyOn(instance, 'handleCheckValidId')
      const prevPops = { id: 'roboto' }

      instance.componentDidUpdate(prevPops)

      expect(handleCheckValidId).toHaveBeenCalledTimes(1)
      expect(props.history.push.mock.calls.length).toBe(1)
    })

    test('componentDidUpdate() - If font name is selected call the method handleRequestFontDetails()', () => {
      // Mock font list column container DOM
      document.body.innerHTML =
        '<div>' +
        ' <div class="font-list-column container" style="height: 100px" />' +
        ' <div class="update-font show" style="height: 220px" />' +
        '</div>'

      const wrapper = shallow(<FontManagerBody {...props} />)
      const instance = wrapper.instance()
      const handleRequestFontDetails = jest.spyOn(instance, 'handleRequestFontDetails')
      const prevPops = { id: 'roboto' }

      instance.componentDidUpdate(prevPops)

      expect(handleRequestFontDetails).toHaveBeenCalled()
    })

    test('componentDidUpdate() - If font list did update and font name is selected, call the method handleRequestFontDetails()', () => {
      const wrapper = shallow(<FontManagerBody {...props} />)
      const instance = wrapper.instance()
      const handleRequestFontDetails = jest.spyOn(instance, 'handleRequestFontDetails')
      const prevPops = {
        fontList: [
          {
            font_name: 'Roboto',
            id: 'roboto',
            regular: 'Roboto.ttf',
            italics: 'Roboto-Italic.ttf',
            bold: 'Roboto-Bold.ttf',
            bolditalics: 'Roboto-BoldItalic.ttf'
          }
        ]
      }

      instance.componentDidUpdate(prevPops)

      expect(handleRequestFontDetails).toHaveBeenCalled()
    })

    test('componentDidUpdate() - Check if there\'s a response message error for fontList', () => {
      const msg = {
        success: { addFont: 'success' },
        error: { fontList: 'error' }
      }
      const wrapper = shallow(<FontManagerBody {...props} id='' msg={msg} />)
      const instance = wrapper.instance()
      const handleSetDefaultState = jest.spyOn(instance, 'handleSetDefaultState')
      const prevProps = { msg: { success: { fontList: 'success' } } }

      instance.componentDidUpdate(prevProps)

      expect(handleSetDefaultState).toHaveBeenCalled()
    })

    test('componentDidUpdate() - Auto select new added font after a successful submission', () => {
      const wrapper = shallow(<FontManagerBody {...props} id='' />)
      const instance = wrapper.instance()
      const handleAutoSelectNewAddedFont = jest.spyOn(instance, 'handleAutoSelectNewAddedFont')
      const prevProps = { msg: { success: { fontList: 'success' } } }

      instance.componentDidUpdate(prevProps)

      expect(handleAutoSelectNewAddedFont).toHaveBeenCalledTimes(1)
    })
  })

  describe('RUN COMPONENT METHODS', () => {
    test('handleCheckValidId() - Handle check if the current accessed ID is valid/active or not (true)', () => {
      expect(wrapper.instance().handleCheckValidId(props.fontList, props.id)).toBe(true)
    })

    test('handleCheckValidId() - Handle check if the current accessed ID is valid/active or not (false)', () => {
      expect(wrapper.instance().handleCheckValidId(props.fontList, 'arial')).toBe(false)
    })

    test('handleRequestFontDetails() - Map current font details from our redux store to component state (updateFont)', async () => {
      const {
        id,
        font_name,
        regular,
        italics,
        bold,
        bolditalics
      } = props.fontList[0]
      const wrapper = shallow(<FontManagerBody {...props} />)
      const adjustFontListHeight = jest.spyOn(utilitiesA, 'adjustFontListHeight')

      wrapper.instance().handleRequestFontDetails()

      // Resolve setTimeout event
      await new Promise(resolve => setTimeout(() => resolve(), 100))

      expect(wrapper.state('addFont')).toBe(initialState)
      expect(wrapper.state('updateFont')).toEqual({
        id: id,
        label: font_name,
        fontStyles: {
          regular: regular,
          italics: italics,
          bold: bold,
          bolditalics: bolditalics
        },
        validateLabel: true,
        validateRegular: true,
        disableUpdateButton: true
      })
      expect(adjustFontListHeight).toHaveBeenCalled()
    })

    test('handleSetDefaultState() - Set component state back to its default state', () => {
      wrapper.instance().handleSetDefaultState()

      expect(wrapper.state('addFont')).toBe(initialState)
      expect(wrapper.state('updateFont')).toBe(initialState)
    })

    test('handleAutoSelectNewAddedFont() - Auto select/open update font panel', () => {
      // Mock update font panel DOM
      document.body.innerHTML =
        '<div class="update-font">' +
        '</div>'

      const toggleUpdateFont = jest.spyOn(utilitiesB, 'toggleUpdateFont')

      wrapper.instance().handleAutoSelectNewAddedFont(props.history, props.fontList)

      expect(toggleUpdateFont).toHaveBeenCalledTimes(1)
    })

    test('handleGetCurrentColumnState() - Return current active state (addFont or updateFont state)', () => {
      expect(wrapper.instance().handleGetCurrentColumnState('addFont')).toBe(initialState)
    })

    test('handleDeleteFontStyle() - Handle deletion process of a font variant (font files drop box)', () => {
      const instance = wrapper.instance()
      const handleGetCurrentColumnState = jest.spyOn(instance, 'handleGetCurrentColumnState')
      const handleUpdateFontState = jest.spyOn(instance, 'handleUpdateFontState')
      const e = { preventDefault: jest.fn() }
      const key = 'italics'
      const state = 'updateFont'

      instance.handleDeleteFontStyle(e, key, state)

      expect(handleGetCurrentColumnState).toHaveBeenCalledTimes(2)
      expect(handleUpdateFontState).toHaveBeenCalledTimes(1)
    })

    test('handleInputChange() - Listen to font name input box field change', () => {
      const instance = wrapper.instance()
      const handleUpdateFontState = jest.spyOn(instance, 'handleUpdateFontState')
      const e = { target: { value: 'Fira Sans Light' } }

      instance.handleInputChange(e, 'addFont')

      expect(handleUpdateFontState).toHaveBeenCalledTimes(1)
    })

    test('handleUpload() - If error exist delete it first to enable dropping', () => {
      const msg = { error: { addFont: { italics: 'Cannot find Roboto-RegularItalic.ttf.' } } }
      const wrapper = shallow(<FontManagerBody {...props} msg={msg} />)
      const instance = wrapper.instance()

      instance.handleUpload('italics', '', 'addFont')

      expect(props.deleteVariantError).toHaveBeenCalledTimes(1)
    })

    test('handleUpload() - Handle process for uploading font variant', () => {
      const instance = wrapper.instance()
      const handleGetCurrentColumnState = jest.spyOn(instance, 'handleGetCurrentColumnState')
      const handleUpdateFontState = jest.spyOn(instance, 'handleUpdateFontState')

      instance.handleUpload('regular', '', 'addFont')

      expect(handleGetCurrentColumnState).toHaveBeenCalledTimes(2)
      expect(handleUpdateFontState).toHaveBeenCalledTimes(1)
    })

    test('handleValidateInputFields() - Validation for font name input box field and font files drop box field (true)', () => {
      const instance = wrapper.instance()

      expect(instance.handleValidateInputFields(
        'updateFont',
        'Arial',
        'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/Roboto-Regular.ttf'
      )).toBe(true)
    })

    test('handleValidateInputFields() - Validation for font name input box field and font files drop box field (false)', () => {
      const instance = wrapper.instance()

      instance.handleValidateInputFields(
        'updateFont',
        '',
        'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/Roboto-Regular.ttf'
      )

      expect(props.validationError).toHaveBeenCalledTimes(1)

      expect(instance.handleValidateInputFields(
        'updateFont',
        '',
        'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/Roboto-Regular.ttf'
      )).toBe(false)
    })

    test('handleUpdateFontState() - Disable or enable the update button based on new field change (true)', () => {
      const {
        font_name,
        id,
        regular,
        italics,
        bold,
        bolditalics
      } = props.fontList[0]

      wrapper.setState({
        updateFont: {
          id: id,
          label: font_name,
          fontStyles: {
            regular: regular,
            italics: italics,
            bold: bold,
            bolditalics: bolditalics
          },
          validateLabel: true,
          validateRegular: true,
          disableUpdateButton: false
        }
      })

      wrapper.instance().handleUpdateFontState()

      expect(wrapper.state('updateFont').disableUpdateButton).toBe(true)
    })

    test('handleUpdateFontState() - Disable or enable the update button based on new field change (false)', () => {
      const {
        id,
        regular,
        italics,
        bold,
        bolditalics
      } = props.fontList[0]

      wrapper.setState({
        updateFont: {
          id: id,
          label: 'test',
          fontStyles: {
            regular: regular,
            italics: italics,
            bold: bold,
            bolditalics: bolditalics
          },
          validateLabel: true,
          validateRegular: true,
          disableUpdateButton: false
        }
      })

      wrapper.instance().handleUpdateFontState()

      expect(wrapper.state('updateFont').disableUpdateButton).toBe(false)
    })

    test('handleEditFont() - Check if all fields are valid', () => {
      const {
        regular,
        italics,
        bold,
        bolditalics
      } = props.fontList[0]

      wrapper.setState({
        updateFont: {
          label: '',
          fontStyles: {
            regular: regular,
            italics: italics,
            bold: bold,
            bolditalics: bolditalics
          },
          validateLabel: true,
          validateRegular: true,
          disableUpdateButton: false
        }
      })

      expect(wrapper.instance().handleEditFont()).toBe(undefined)
    })

    test('handleEditFont() - Check if there\'s no changes in current font data', () => {
      const {
        id,
        font_name,
        regular,
        italics,
        bold,
        bolditalics
      } = props.fontList[0]

      wrapper.setState({
        updateFont: {
          id: id,
          label: font_name,
          fontStyles: {
            regular: regular,
            italics: italics,
            bold: bold,
            bolditalics: bolditalics
          },
          validateLabel: true,
          validateRegular: true,
          disableUpdateButton: false
        }
      })

      wrapper.instance().handleEditFont(id)

      expect(props.clearAddFontMsg).toHaveBeenCalledTimes(1)
    })

    test('handleEditFont() - Handle our edit font process and call our editFont redux action - ', () => {
      const {
        id,
        regular,
        italics,
        bold,
        bolditalics
      } = props.fontList[0]

      wrapper.setState({
        updateFont: {
          id: id,
          label: 'test',
          fontStyles: {
            regular: regular,
            italics: italics,
            bold: bold,
            bolditalics: bolditalics
          },
          validateLabel: true,
          validateRegular: true,
          disableUpdateButton: false
        }
      })

      wrapper.instance().handleEditFont(id)

      expect(props.clearAddFontMsg).toHaveBeenCalledTimes(0)
      expect(props.editFont).toHaveBeenCalledTimes(1)
    })

    test('handleCancelEditFont() - Listen to cancel button click event', () => {
      // Mock update font panel DOM
      document.body.innerHTML =
        '<div class="update-font">' +
        '</div>'

      const toggleUpdateFont = jest.spyOn(utilitiesB, 'toggleUpdateFont')

      wrapper.instance().handleCancelEditFont()

      expect(toggleUpdateFont).toHaveBeenCalledTimes(1)
      expect(props.clearAddFontMsg).toHaveBeenCalledTimes(1)
    })

    test('handleCancelEditFontKeypress() - Listen to cancel button keyboard press event (space and enter)', () => {
      // Mock update font panel DOM
      document.body.innerHTML =
        '<div class="update-font">' +
        '</div>'

      const toggleUpdateFont = jest.spyOn(utilitiesB, 'toggleUpdateFont')
      const e = { keyCode: 13 }

      wrapper.instance().handleCancelEditFontKeypress(e)

      expect(toggleUpdateFont).toHaveBeenCalledTimes(1)
      expect(props.clearAddFontMsg).toHaveBeenCalledTimes(1)
    })

    test('handleSubmit() - Listen to form submit event and distinguish if it\'s add or edit request (add)', () => {
      const wrapper = shallow(<FontManagerBody {...props} id='' />)
      const instance = wrapper.instance()
      const handleAddFont = jest.spyOn(instance, 'handleAddFont')
      const e = { preventDefault: jest.fn() }

      instance.handleSubmit(e)

      expect(handleAddFont).toHaveBeenCalledTimes(1)
    })

    test('handleSubmit() - Listen to form submit event and distinguish if it\'s add or edit request (edit)', () => {
      const wrapper = shallow(<FontManagerBody {...props} />)
      const instance = wrapper.instance()
      const handleEditFont = jest.spyOn(instance, 'handleEditFont')
      const e = { preventDefault: jest.fn() }

      instance.handleSubmit(e)

      expect(handleEditFont).toHaveBeenCalledTimes(1)
    })
  })

  describe('RENDERS COMPONENT', () => {
    test('render <FontManagerBody /> component', () => {
      const component = findByTestAttr(wrapper, 'component-FontManagerBody')

      expect(component.length).toBe(1)
    })

    test('render <SearchBox /> component', () => {
      expect(wrapper.find('Connect(SearchBox)').length).toBe(1)
    })

    test('render <Alert /> component', () => {
      expect(wrapper.find('Alert').length).toBe(1)
    })

    test('render <FontList /> component', () => {
      expect(wrapper.find('Connect(FontList)').length).toBe(1)
    })

    test('render <AddFont /> component', () => {
      expect(wrapper.find('AddFont').length).toBe(1)
    })

    test('render <UpdateFont /> component', () => {
      expect(wrapper.find('UpdateFont').length).toBe(1)
    })
  })
})
