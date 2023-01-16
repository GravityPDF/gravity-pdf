/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
/* Redux actions */
import {
  getCustomFontList,
  addFont,
  editFont,
  validationError,
  deleteVariantError,
  selectFont,
  clearAddFontMsg,
  clearDropzoneError
} from '../../actions/fontManager'
/* Components */
import Alert from '../Alert/Alert'
import SearchBox from './SearchBox'
import FontList from './FontList'
import AddFont from './AddFont'
import UpdateFont from './UpdateFont'
import initialState from './InitialAddUpdateState'
/* Utilities */
import { adjustFontListHeight } from '../../utilities/FontManager/adjustFontListHeight'
import { toggleUpdateFont, addClass } from '../../utilities/FontManager/toggleUpdateFont'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * FontManagerBody component
 *
 * @since 6.0
 */
export class FontManagerBody extends Component {
  /**
   * PropTypes
   *
   * @since 6.0
   */
  static propTypes = {
    getCustomFontList: PropTypes.func.isRequired,
    id: PropTypes.string,
    loading: PropTypes.bool.isRequired,
    fontList: PropTypes.arrayOf(PropTypes.object).isRequired,
    msg: PropTypes.object.isRequired,
    clearDropzoneError: PropTypes.func.isRequired,
    clearAddFontMsg: PropTypes.func.isRequired,
    editFont: PropTypes.func.isRequired,
    validationError: PropTypes.func.isRequired,
    deleteVariantError: PropTypes.func.isRequired,
    selectFont: PropTypes.func.isRequired,
    addFont: PropTypes.func.isRequired,
    history: PropTypes.object.isRequired
  }

  /**
   * Initialize component state
   *
   * @type {{
   * addFont: {
   *  fontStyles: { italics: string, bold: string, bolditalics: string, regular: string },
   *  disableUpdateButton: boolean,
   *  id: string,
   *  label: string,
   *  validateLabel: boolean,
   *  validateRegular: boolean
   * },
   * updateFont: {
   *  fontStyles: { italics: string, bold: string, bolditalics: string, regular: string },
   *  disableUpdateButton: boolean,
   *  id: string,
   *  label: string,
   *  validateLabel: boolean,
   *  validateRegular: boolean
   * }}}
   *
   * @since 6.0
   */
  state = {
    addFont: initialState,
    updateFont: initialState
  }

  /**
   * On mount, Request custom font list by calling redux action getCustomFontList()
   *
   * @since 6.0
   */
  componentDidMount () {
    const { getCustomFontList, id, history } = this.props

    getCustomFontList()

    /* Auto slide 'update font' panel if refreshed */
    if (id) {
      addClass(document.querySelector('.update-font'), history, id)
    }
  }

  /**
   * If component did update and new props are received,
   * fires appropriate action based on redux store data
   *
   * @param prevProps: object
   *
   * @since 6.0
   */
  componentDidUpdate (prevProps) {
    const { id, fontList, msg, history } = this.props

    /* If font name is selected call the method handleRequestFontDetails() */
    if (prevProps.id !== id && id) {
      /* Perform check if the accessed ID is valid, if not prevent fatal error event */
      if (!this.handleCheckValidId(fontList, id)) {
        return history.push('/fontmanager/')
      }

      this.handleRequestFontDetails()
    }

    /* If font list did update, call the method handleRequestFontDetails() */
    if (prevProps.fontList !== fontList && fontList && id) {
      /* Perform check if the accessed ID is valid, if not prevent fatal error event */
      if (!this.handleCheckValidId(fontList, id)) {
        return history.push('/fontmanager/')
      }

      this.handleRequestFontDetails()
    }

    /* If font is successfully installed, auto select the new added font and slide update font panel */
    if (prevProps.msg !== msg && msg.success && !id) {
      /* Check if there's a response message error for fontList */
      if (msg.error && msg.error.fontList) {
        return this.handleSetDefaultState()
      }

      /* Auto select new added font after a successful submission */
      this.handleAutoSelectNewAddedFont(history, fontList)
    }
  }

  /**
   * Handle check if the current accessed ID is valid/active or not
   *
   * @param fontList: array of object
   * @param id: string
   *
   * @since 6.0
   */
  handleCheckValidId = (fontList, id) => {
    const checkValidId = fontList.filter(font => font.id === id)[0]

    if (!checkValidId) {
      return false
    }

    return true
  }

  /**
   * Map current font details from our redux store to component state (updateFont)
   *
   * @since 6.0
   */
  handleRequestFontDetails = () => {
    const { fontList, id } = this.props
    const font = fontList.filter(font => font.id === id)[0]

    this.setState({
      addFont: initialState,
      updateFont: {
        id: font.id,
        label: font.font_name,
        fontStyles: {
          regular: font.regular,
          italics: font.italics,
          bold: font.bold,
          bolditalics: font.bolditalics
        },
        validateLabel: true,
        validateRegular: true,
        disableUpdateButton: true
      }
    })

    setTimeout(() => adjustFontListHeight(), 100)
  }

  /**
   * Set component state back to its default state
   *
   * @since 6.0
   */
  handleSetDefaultState = () => {
    this.setState({
      addFont: initialState,
      updateFont: initialState
    })
  }

  /**
   * Auto select new added font and slide update font panel
   *
   * @param history: object
   * @param fontList: array of object
   *
   * @since 6.0
   */
  handleAutoSelectNewAddedFont = (history, fontList) => {
    const newFontIndex = Object.keys(fontList).slice(-1).pop()
    const newFont = fontList[newFontIndex]

    this.props.selectFont(newFont.id)
    toggleUpdateFont(history, newFont.id)
  }

  /**
   * Return current active state (addFont or updateFont)
   *
   * @param column: string
   *
   * @returns {{ state: object }}
   *
   * @since 6.0
   */
  handleGetCurrentColumnState = column => {
    const state = (column === 'addFont') ? this.state.addFont : this.state.updateFont

    return state
  }

  /**
   * Handle deletion process of a font variant (font files drop box)
   *
   * @param e: class
   * @param key: string
   * @param state: string
   *
   * @since 6.0
   */
  handleDeleteFontStyle = (e, key, state) => {
    e.preventDefault()

    const { msg: { error }, clearDropzoneError } = this.props

    /* Remove addFont error */
    if (error && error.addFont) {
      const forValue = `gfpdf-font-variant-${key}`
      const dropZone = document.querySelector(`div[for=${forValue}]`)

      dropZone.classList.remove('error')
      /* Call redux action clearDropzoneError() */
      clearDropzoneError(key)
    }

    this.handleGetCurrentColumnState(state).fontStyles[key] = ''
    this.setState({
      [state]: {
        ...this.handleGetCurrentColumnState(state),
        validateRegular: true
      }
    })
    this.forceUpdate()
    this.handleUpdateFontState()
  }

  /**
   * Listen to font name input box field change
   *
   * @param e: object
   * @param state: string
   *
   * @since 6.0
   */
  handleInputChange = (e, state) => {
    const { addFont, updateFont } = this.state
    const defaultState = (state === 'addFont') ? addFont : updateFont

    this.setState({
      [state]: {
        ...defaultState,
        label: e.target.value
      }
    }, () => this.handleUpdateFontState())
  }

  /**
   * Handle process for uploading font variant
   *
   * @param fontVariant: string
   * @param file: file
   * @param state: string
   *
   * @since 6.0
   */
  handleUpload = (fontVariant, file, state) => {
    const { msg: { error } } = this.props
    const fontFileMissing = (error && typeof error.addFont === 'object') && error.addFont

    /* If error exist delete it first to enable dropping */
    fontFileMissing && Object.entries(fontFileMissing).map(([key]) => {
      if (fontVariant === key) {
        /* Call redux action deleteVariantError() */
        return this.props.deleteVariantError(fontVariant)
      }

      return false
    })

    /* Safeguard file */
    const checkFile = !file ? '' : file

    this.setState({
      [state]: {
        ...this.handleGetCurrentColumnState(state),
        fontStyles: {
          ...this.handleGetCurrentColumnState(state).fontStyles,
          [fontVariant]: checkFile
        }
      }
    }, () => this.handleUpdateFontState())
  }

  /**
   * Check validation for font name input box field and font files drop box field
   *
   * @param state: string
   * @param label: string
   * @param regular: string
   *
   * @returns { boolean }
   *
   * @since 6.0
   */
  handleValidateInputFields = (state, label, regular) => {
    const defaultState = (state === 'addFont') ? this.state.addFont : this.state.updateFont
    const validate = {
      validateLabel: true,
      validateRegular: true
    }
    let labelField = false
    let regularField = false

    /* Regex will allow only a-z, A-Z, and 0-9 */
    const checkSpecialCharRegex = /^[0-9a-zA-Z ]*$/

    if (!checkSpecialCharRegex.test(label) || label === '') {
      labelField = false
      validate.validateLabel = false
    }

    if (checkSpecialCharRegex.test(label) && label !== '') {
      labelField = true
      validate.validateLabel = true
    }

    if (!regular) {
      regularField = false
      validate.validateRegular = false
    }

    if (regular) {
      regularField = true
      validate.validateRegular = true
    }

    if (labelField && regularField) {
      this.setState({ [state]: { ...defaultState, ...validate } })
      return true
    }

    this.setState({ [state]: { ...defaultState, ...validate } })
    /* Call redux action validationError() */
    this.props.validationError()
    return false
  }

  /**
   * Check the update font panel state and disable or enable the update button based on
   * new field change
   *
   * @since 6.0
   */
  handleUpdateFontState = () => {
    const { fontList, id } = this.props

    if (id) {
      const { label, fontStyles } = this.state.updateFont
      const activeFont = fontList.filter(font => font.id === id)[0]

      if (
        activeFont.font_name === label &&
        activeFont.regular === fontStyles.regular &&
        activeFont.italics === fontStyles.italics &&
        activeFont.bold === fontStyles.bold &&
        activeFont.bolditalics === fontStyles.bolditalics
      ) {
        return this.setState({
          updateFont: {
            ...this.state.updateFont,
            disableUpdateButton: true
          }
        })
      }

      this.setState({
        updateFont: {
          ...this.state.updateFont,
          disableUpdateButton: false
        }
      })
    }
  }

  /**
   * Handle our add font process and call our addFont redux action
   *
   * @since 6.0
   */
  handleAddFont = () => {
    const { label, fontStyles } = this.state.addFont

    /* Check if all fields are valid */
    if (!this.handleValidateInputFields('addFont', label, fontStyles.regular)) {
      return
    }

    /* Call redux action addFont() */
    this.props.addFont({ label, ...fontStyles })
  }

  /**
   * Handle our edit font process and call our editFont redux action
   *
   * @param id: string
   *
   * @since 6.0
   */
  handleEditFont = id => {
    const { label, fontStyles } = this.state.updateFont
    const { fontList, editFont, clearAddFontMsg } = this.props
    const data = {}

    /* Check if all fields are valid */
    if (!this.handleValidateInputFields('updateFont', label, fontStyles.regular)) {
      return
    }

    /* Construct the data to be submitted */
    Object.keys(fontStyles).forEach(key => {
      if (typeof fontStyles[key] === 'object' || fontStyles[key] === '') {
        data[key] = fontStyles[key]
      }
    })

    const currentFont = fontList.filter(font => font.id === id)[0]
    const currentFontStyles = {
      regular: currentFont.regular,
      italics: currentFont.italics,
      bold: currentFont.bold,
      bolditalics: currentFont.bolditalics
    }

    /* Check if there's no changes in current font data */
    if (
      label === currentFont.font_name &&
      JSON.stringify(fontStyles) === JSON.stringify(currentFontStyles)
    ) {
      /* Call redux action clearAddFontMsg() */
      return clearAddFontMsg()
    }

    /* Call redux action editFont() */
    editFont({
      id,
      font: { label, ...data }
    })
  }

  /**
   * Listen to cancel button click event
   *
   * @since 6.0
   */
  handleCancelEditFont = () => {
    const { history, clearAddFontMsg } = this.props

    toggleUpdateFont(history)
    /* Call redux action clearAddFontMsg() */
    clearAddFontMsg()
  }

  /**
   * Listen to cancel button keyboard press event (space and enter)
   *
   * @param e: class
   *
   * @since 6.0
   */
  handleCancelEditFontKeypress = e => {
    const enter = 13
    const space = 32

    if (e.keyCode === enter || e.keyCode === space) {
      const { history, clearAddFontMsg } = this.props

      toggleUpdateFont(history)
      /* Call redux action clearAddFontMsg() */
      clearAddFontMsg()
    }
  }

  /**
   * Listen to form submit event and distinguish if it's add or edit request
   *
   * @param e: object
   *
   * @returns { component method }
   *
   * @since 6.0
   */
  handleSubmit = e => {
    e.preventDefault()
    const { id } = this.props

    if (id) {
      return this.handleEditFont(id)
    }

    this.handleAddFont()
  }

  /**
   * Display the font manager body UI
   *
   * @since 6.0
   */
  render () {
    const updateFontVisible = document.querySelector('.update-font.show')
    const { id, fontList, msg, loading, history } = this.props

    return (
      <div
        data-test='component-FontManagerBody'
        id='gfpdf-font-manager-container'
        className='wp-clearfix theme-about'
      >
        <div className='font-list-column container'>
          <SearchBox id={id} />

          {msg.error && msg.error.deleteFont && <Alert msg={msg.error.deleteFont} />}

          <FontList id={id} history={history} />
        </div>

        <div className='add-update-font-column container'>
          <AddFont
            onHandleInputChange={this.handleInputChange}
            onHandleUpload={this.handleUpload}
            onHandleDeleteFontStyle={this.handleDeleteFontStyle}
            onHandleSubmit={this.handleSubmit}
            msg={msg}
            loading={loading}
            tabIndexFontName={!updateFontVisible ? '145' : '-1'}
            tabIndexFontFiles={!updateFontVisible ? '146' : '-1'}
            tabIndexFooterButtons={!updateFontVisible ? '147' : '-1'}
            {...this.state.addFont}
          />

          <UpdateFont
            onHandleInputChange={this.handleInputChange}
            onHandleUpload={this.handleUpload}
            onHandleDeleteFontStyle={this.handleDeleteFontStyle}
            onHandleCancelEditFont={this.handleCancelEditFont}
            onHandleCancelEditFontKeypress={this.handleCancelEditFontKeypress}
            onHandleSubmit={this.handleSubmit}
            fontList={fontList}
            msg={msg}
            loading={loading}
            tabIndexFontName={updateFontVisible ? '145' : '-1'}
            tabIndexFontFiles={updateFontVisible ? '146' : '-1'}
            tabIndexFooterButtons={updateFontVisible ? '147' : '-1'}
            {...this.state.updateFont}
          />
        </div>
      </div>
    )
  }
}

/**
 * Map redux state to props
 *
 * @param state: object
 *
 * @returns {{ loading: boolean, fontList: array of object, msg: object }}
 *
 * @since 6.0
 */
const mapStateToProps = state => ({
  loading: state.fontManager.addFontLoading,
  fontList: state.fontManager.fontList,
  msg: state.fontManager.msg
})

/**
 * Connect and dispatch redux actions as props
 *
 * @since 6.0
 */
export default connect(mapStateToProps, {
  getCustomFontList,
  addFont,
  editFont,
  validationError,
  deleteVariantError,
  selectFont,
  clearAddFontMsg,
  clearDropzoneError
})(FontManagerBody)
