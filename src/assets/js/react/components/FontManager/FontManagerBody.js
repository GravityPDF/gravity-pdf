import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import SearchBox from './SearchBox'
import FontList from './FontList'
import AddFont from './AddFont'
import UpdateFont from './UpdateFont'
import {
  getCustomFontList,
  addFont,
  editFont,
  validationError,
  deleteVariantError,
  clearAddFontMsg,
  clearDropzoneError
} from '../../actions/fontManager'
import Alert from '../Alert/Alert'
import { toggleUpdateFont } from '../../utilities/toggleUpdateFont'
import initialState from './AddUpdateState'

export class FontManagerBody extends Component {
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
    addFont: PropTypes.func.isRequired,
    history: PropTypes.object.isRequired
  }

  state = {
    addFont: initialState,
    updateFont: initialState
  }

  componentDidMount () {
    this.props.getCustomFontList()
  }

  componentDidUpdate (prevProps, prevState, snapshot) {
    const { id, fontList, msg, history } = this.props

    /* If component did update and ID is active call the method handleRequestFontDetails() */
    if (prevProps.id !== id && id) {
      const checkValidId = fontList.filter(font => font.id === id)[0]

      /* Handle fatal error event */
      if (!checkValidId) {
        return history.push('/fontmanager/')
      }

      this.handleRequestFontDetails()
      setTimeout(() => this.handleAdjustFontListHeight(), 100)
    }

    /* If component did update and ID & fontList is active call the method handleRequestFontDetails() */
    if (prevProps.fontList !== fontList && id && fontList) {
      const checkValidId = fontList.filter(font => font.id === id)[0]

      /* Handle fatal error event */
      if (!checkValidId) {
        return history.push('/fontmanager/')
      }

      /* Auto slide the 'Update Font' form */
      document.querySelector('.update-font').classList.add('show')
      this.handleRequestFontDetails()
    }

    if (prevProps.msg !== msg && msg.success && !id) {
      /* Check if there's a response message error for fontList */
      if (msg.error && msg.error.fontList) {
        return this.handleSetDefaultState()
      }

      /* Auto select new added font after a successful submission */
      this.handleAutoSelectNewAddedFont(history, fontList)
    }
  }

  handleAdjustFontListHeight = () => {
    const fontListColumn = document.querySelector('.font-list-column')
    const updateFont = document.querySelector('.update-font.show')

    fontListColumn.style.height = window.getComputedStyle(updateFont).height
  }

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
        kashida: font.useKashida,
        validateLabel: true,
        validateRegular: true,
        disableUpdateButton: true
      }
    })
  }

  handleSetDefaultState = () => {
    this.setState({ addFont: initialState, updateFont: initialState })
  }

  handleAutoSelectNewAddedFont = (history, fontList) => {
    const newFontIndex = Object.keys(fontList).slice(-1).pop()
    const newFont = fontList[newFontIndex]

    toggleUpdateFont(history, newFont.id)
  }

  handleGetCurrentColumnState = column => {
    const state = column === 'addFont' ? this.state.addFont : this.state.updateFont

    return state
  }

  handleDeleteFontStyle = (e, key, state) => {
    e.preventDefault()

    const { msg: { error }, clearDropzoneError } = this.props

    /* Remove addFont error */
    if (error && error.addFont) {
      const forValue = `gfpdf-font-variant-${key}`
      const dropZone = document.querySelector(`div[for=${forValue}]`)

      dropZone.classList.remove('error')
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

  handleInputChange = (e, state) => {
    const { addFont, updateFont } = this.state
    const defaultState = state === 'addFont' ? addFont : updateFont

    this.setState({
      [state]: {
        ...defaultState,
        label: e.target.value
      }
    }, () => this.handleUpdateFontState())
  }

  handleKashidaChange = e => {
    this.setState({
      updateFont: {
        ...this.state.updateFont,
        kashida: Number(e.target.value)
      }
    }, () => this.handleUpdateFontState())
  }

  handleUpload = (fontVariant, file, state) => {
    const { msg: { error } } = this.props
    const fontFileMissing = (error && typeof error.addFont === 'object') && error.addFont

    /* If error exist delete it first to enable dropping */
    fontFileMissing && Object.entries(fontFileMissing).map(([key]) => {
      if (fontVariant === key) {
        this.props.deleteVariantError(fontVariant)
      }
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

  handleValidateInputFields = (state, label, regular) => {
    const defaultState = state === 'addFont' ? this.state.addFont : this.state.updateFont
    const validate = { validateLabel: true, validateRegular: true }
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
    this.props.validationError()
    return false
  }

  handleUpdateFontState = () => {
    const { fontList, id } = this.props

    if (id) {
      const { label, fontStyles, kashida } = this.state.updateFont
      const activeFont = fontList.filter(font => font.id === id)[0]

      if (
        activeFont.font_name === label &&
        activeFont.regular === fontStyles.regular &&
        activeFont.italics === fontStyles.italics &&
        activeFont.bold === fontStyles.bold &&
        activeFont.bolditalics === fontStyles.bolditalics &&
        activeFont.useKashida === kashida
      ) {
        return this.setState({ updateFont: { ...this.state.updateFont, disableUpdateButton: true } })
      }

      this.setState({ updateFont: { ...this.state.updateFont, disableUpdateButton: false } })
    }
  }

  handleAddFont = () => {
    const { label, fontStyles } = this.state.addFont

    if (!this.handleValidateInputFields('addFont', label, fontStyles.regular)) {
      return
    }

    this.props.addFont({ label, ...fontStyles })
  }

  handleEditFont = id => {
    const { label, fontStyles, kashida } = this.state.updateFont
    const { fontList, editFont, clearAddFontMsg } = this.props
    const data = {}

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
    const currentKashida = currentFont.useKashida
    const currentFontStyles = {
      regular: currentFont.regular,
      italics: currentFont.italics,
      bold: currentFont.bold,
      bolditalics: currentFont.bolditalics
    }

    /* Check if there's no changes in current font data */
    if (
      label === currentFont.font_name &&
      kashida === currentKashida &&
      JSON.stringify(fontStyles) === JSON.stringify(currentFontStyles)
    ) {
      return clearAddFontMsg()
    }

    editFont({
      id,
      font: {
        label,
        useKashida: kashida,
        ...data
      }
    })
  }

  handleCancelEditFont = () => {
    const { history, clearAddFontMsg } = this.props

    toggleUpdateFont(history)
    clearAddFontMsg()
  }

  handleSubmit = e => {
    e.preventDefault()
    const { id } = this.props

    if (id) {
      return this.handleEditFont(id)
    }

    this.handleAddFont()
  }

  render () {
    const updateFontVisible = document.querySelector('.update-font.show')
    const { id, fontList, msg, loading, history } = this.props

    return (
      <div id='gfpdf-font-manager-container' className='wp-clearfix theme-about'>
        <div className='font-list-column container'>
          <SearchBox id={id} />

          {msg.error && msg.error.deleteFont && <Alert msg={msg.error.deleteFont} />}

          <FontList id={id} history={history} />
        </div>

        <div className='add-font-column container'>
          <AddFont
            onHandleInputChange={this.handleInputChange}
            onHandleUpload={this.handleUpload}
            onHandleDeleteFontStyle={this.handleDeleteFontStyle}
            onHandleSubmit={this.handleSubmit}
            msg={msg}
            loading={loading}
            tabIndexFontName={!updateFontVisible ? '145' : '0'}
            tabIndexFontFiles={!updateFontVisible ? '146' : '0'}
            tabIndexFooterButtons={!updateFontVisible ? '148' : '0'}
            {...this.state.addFont}
          />

          <UpdateFont
            onHandleInputChange={this.handleInputChange}
            onHandleKashidaChange={this.handleKashidaChange}
            onHandleUpload={this.handleUpload}
            onHandleDeleteFontStyle={this.handleDeleteFontStyle}
            onHandleCancelEditFont={this.handleCancelEditFont}
            onHandleSubmit={this.handleSubmit}
            fontList={fontList}
            msg={msg}
            loading={loading}
            tabIndexFontName={updateFontVisible ? '145' : '0'}
            tabIndexFontFiles={updateFontVisible ? '146' : '0'}
            tabIndexKashida={updateFontVisible ? '147' : '0'}
            tabIndexFooterButtons={updateFontVisible ? '148' : '0'}
            {...this.state.updateFont}
          />
        </div>
      </div>
    )
  }
}

const mapStateToProps = state => ({
  loading: state.fontManager.addFontLoading,
  fontList: state.fontManager.fontList,
  searchResult: state.fontManager.searchResult,
  msg: state.fontManager.msg
})

export default connect(mapStateToProps, {
  getCustomFontList,
  addFont,
  editFont,
  validationError,
  deleteVariantError,
  clearAddFontMsg,
  clearDropzoneError
})(FontManagerBody)
