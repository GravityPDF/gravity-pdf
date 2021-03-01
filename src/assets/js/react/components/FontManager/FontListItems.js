/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
/* Redux actions */
import { clearAddFontMsg, deleteFont, selectFont, moveSelectedFontToTop } from '../../actions/fontManager'
/* Components */
import FontListIcon from './FontListIcon'
import Spinner from '../Spinner'
/* Utilities */
import { toggleUpdateFont } from '../../utilities/FontManager/toggleUpdateFont'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * FontListItems component
 *
 * @since 6.0
 */
export class FontListItems extends Component {
  /**
   * PropTypes
   *
   * @since 6.0
   */
  static propTypes = {
    id: PropTypes.string,
    history: PropTypes.object.isRequired,
    clearAddFontMsg: PropTypes.func.isRequired,
    msg: PropTypes.object.isRequired,
    deleteFont: PropTypes.func.isRequired,
    selectFont: PropTypes.func.isRequired,
    moveSelectedFontToTop: PropTypes.func.isRequired,
    fontList: PropTypes.arrayOf(
      PropTypes.shape({
        font_name: PropTypes.string.isRequired,
        id: PropTypes.string.isRequired,
        regular: PropTypes.string.isRequired,
        italics: PropTypes.string.isRequired,
        bold: PropTypes.string.isRequired,
        bolditalics: PropTypes.string.isRequired
      })
    ).isRequired,
    searchResult: PropTypes.oneOfType([
      PropTypes.oneOf([null]).isRequired,
      PropTypes.arrayOf(
        PropTypes.shape({
          font_name: PropTypes.string.isRequired,
          id: PropTypes.string.isRequired,
          regular: PropTypes.string.isRequired,
          italics: PropTypes.string.isRequired,
          bold: PropTypes.string.isRequired,
          bolditalics: PropTypes.string.isRequired
        })
      ).isRequired
    ]),
    selectedFont: PropTypes.string.isRequired,
    loading: PropTypes.bool.isRequired
  }

  /**
   * Initialize component state
   *
   * @type {{ disableSelectFontName: boolean, deleteId: string }}
   *
   * @since 6.0
   */
  state = {
    disableSelectFontName: false,
    deleteId: ''
  }

  /**
   * On mount, Call the method handleDisableSelectFields()
   *
   * @since 6.0
   */
  componentDidMount () {
    const { selectedFont } = this.props

    this.handleDisableSelectFields()

    /* Move selected font at the top of the list */
    if (selectedFont) {
      this.handleMoveSelectedFontAtTheTopOfTheList(selectedFont)
    }
  }

  /**
   * If component did update and new props are received we'll check if the methods
   * handleResetLoadingState() and toggleUpdateFont() will be called
   *
   * @param prevProps: object
   *
   * @since 6.0
   */
  componentDidUpdate (prevProps) {
    const { history, loading, fontList } = this.props
    const updateFontVisible = document.querySelector('.update-font.show')

    /* Reset/Clear deleteId loading state */
    if (prevProps.loading !== loading && !loading) {
      this.handleResetLoadingState()
    }

    /* Remove update font panel after font is successfully deleted */
    if (prevProps.loading !== loading && prevProps.fontList !== fontList && updateFontVisible) {
      toggleUpdateFont(history)
    }
  }

  /**
   * Check URL to distinguish the current location. If current location is under
   * tools tab then disable select font name functionality (radio button)
   *
   * @since 6.0
   */
  handleDisableSelectFields = () => {
    const tabLocation = window.location.search.substr(window.location.search.lastIndexOf('=') + 1)

    if (tabLocation === 'tools') {
      return this.setState({ disableSelectFontName: true })
    }

    this.handleSetSelectedFontNameValue(tabLocation)
  }

  /**
   * Handle the functionality to select and set default font type to be used in PDFs (radio button)
   *
   * @param location: string
   *
   * @since 6.0
   */
  handleSetSelectedFontNameValue = (location) => {
    let fontManagerSelectBoxValue
    const { fontList, selectFont } = this.props

    /* If location not under Global/General settings */
    if (location !== 'PDF' && location !== 'general') {
      fontManagerSelectBoxValue = document.querySelector('#gfpdf_settings\\[font\\]').value
    } else {
      fontManagerSelectBoxValue = document.querySelector('#gfpdf_settings\\[default_font\\]').value
    }

    /* Do nothing if selected value doesn't exist on font manager fontList */
    if (!this.handleCheckSelectBoxValue(fontList, fontManagerSelectBoxValue)) {
      /* Call redux action selectFont() */
      return selectFont('')
    }

    /* Call redux action selectFont() */
    selectFont(fontManagerSelectBoxValue)
  }

  /**
   * Handle check if font manager default selected font type is listed on custom font list
   *
   * @param fontList: array
   * @param value: string
   *
   * @returns { boolean }
   *
   * @since 6.0
   */
  handleCheckSelectBoxValue = (fontList, value) => {
    const checkIfValueExist = fontList && fontList.filter(font => font.id === value)[0]

    if (!checkIfValueExist) {
      return false
    }

    return true
  }

  /**
   * Reset deleteId state. The state of deleteId is used to match the current delete id request and
   * trigger loading spinner
   *
   * @since 6.0
   */
  handleResetLoadingState = () => {
    this.setState({ deleteId: '' })
  }

  /**
   * Move selected font at the very top of the font list
   *
   * @param selectedFont: string
   *
   * @since 6.0
   */
  handleMoveSelectedFontAtTheTopOfTheList = selectedFont => {
    this.props.moveSelectedFontToTop(selectedFont)
  }

  /**
   * Handle font click to display or hide update font panel
   *
   * @param fontId: string
   *
   * @since 6.0
   */
  handleFontClick = fontId => {
    const { id, history, clearAddFontMsg, msg: { success, error } } = this.props

    /* Remove previous msg */
    if ((success && success.addFont) || (error && error.addFont)) {
      /* Call redux action clearAddFontMsg */
      clearAddFontMsg()
    }

    if (id === fontId) {
      return toggleUpdateFont(history)
    }

    toggleUpdateFont(history, fontId)
  }

  /**
   * Listen to an 'enter' keyboard event on font list item
   *
   * @param e: object
   * @param fontId: string
   *
   * @since 6.0
   */
  handleFontClickKeypress = (e, fontId) => {
    /* Check if a keyboard keypress is 'enter' (13) and call the method handleFontClick() */
    if (e.keyCode === 13) {
      this.handleFontClick(fontId)
    }
  }

  /**
   * Handle request of font deletion
   *
   * @param e: object
   * @param fontId: string
   *
   * @since 6.0
   */
  handleDeleteFont = (e, fontId) => {
    e.stopPropagation()
    this.setState({ deleteId: fontId })

    /* Fire a native window alert box to confirm deletion request */
    if (window.confirm(GFPDF.fontManagerDeleteFontConfirmation)) {
      /* Call redux action deleteFont */
      this.props.deleteFont(fontId)
    }
  }

  /**
   * Listen to an 'enter' keyboard event for font deletion
   *
   * @param e: object
   * @param fontId: string
   *
   * @since 6.0
   */
  handleDeleteFontKeypress = (e, fontId) => {
    /* Check if a keyboard keypress is 'enter' (13) and call the method handleDeleteFont() */
    if (e.keyCode === 13) {
      this.handleDeleteFont(e, fontId)
    }
  }

  /**
   * Handle the process of selecting and deselecting of font type/name (radio button)
   *
   * @param e: object
   * @param click: string
   *
   * @since 6.0
   */
  handleSelectFont = (e, click) => {
    const { selectedFont, selectFont } = this.props

    if (click) {
      /* Call redux action selectFont */
      return selectFont(e.target.value)
    }

    if (selectedFont === '') {
      /* Call redux action selectFont */
      return selectFont(e.target.value)
    }

    /* Call redux action selectFont */
    selectFont('')
  }

  /**
   * Listen to an 'enter' or 'space' keyboard event for selecting a font type/name (radio button)
   *
   * @param e: object
   *
   * @since 6.0
   */
  handleSelectFontKeypress = e => {
    const enter = 13
    const space = 32

    /*
     * Check if a keyboard keypress is 'enter' (13) or 'space' (12) and call the method handleDeleteFont()
     */
    if (e.keyCode === enter || e.keyCode === space) {
      e.preventDefault()
      e.stopPropagation()

      this.handleSelectFont(e)
    }
  }

  /**
   * Display the font list items UI
   *
   * @since 6.0
   */
  render () {
    const { disableSelectFontName, deleteId } = this.state
    const { id, loading, fontList, searchResult, selectedFont } = this.props
    const list = !searchResult ? fontList : searchResult

    return (
      <div data-test='component-FontListItems' className='font-list-items'>
        {list && list.map(font => {
          return (
            <div
              key={font.id}
              className={'font-list-item' + (font.id === id ? ' active' : '')}
              onClick={() => this.handleFontClick(font.id)}
              onKeyDown={e => this.handleFontClickKeypress(e, font.id)}
              tabIndex='144'
            >

              {loading && (deleteId === font.id) ? <Spinner style='delete-font' /> : (
                <span
                  className='dashicons dashicons-trash'
                  onClick={e => this.handleDeleteFont(e, font.id)}
                  onKeyDown={e => this.handleDeleteFontKeypress(e, font.id)}
                  tabIndex='144'
                />
              )}

              <span className='font-name'>
                {!disableSelectFontName && (
                  <input
                    type='radio'
                    className='selectFontName'
                    name='selectFontName'
                    value={font.id}
                    onChange={e => this.handleSelectFont(e, 'click')}
                    onClick={e => e.stopPropagation()}
                    onKeyDown={e => this.handleSelectFontKeypress(e)}
                    checked={font.id === selectedFont}
                    tabIndex='144'
                  />
                )}
                {font.font_name}
              </span>

              <FontListIcon font={font.regular} />
              <FontListIcon font={font.italics} />
              <FontListIcon font={font.bold} />
              <FontListIcon font={font.bolditalics} />
            </div>
          )
        })}
      </div>
    )
  }
}

/**
 * Map redux state to props
 *
 * @param state: object
 *
 * @returns {{
 *   loading: boolean,
 *   fontList: array of object,
 *   searchResult: null || array of object,
 *   selectedFont: string,
 *   msg: object,
 * }}
 *
 * @since 6.0
 */
const mapStateToProps = state => ({
  loading: state.fontManager.deleteFontLoading,
  fontList: state.fontManager.fontList,
  searchResult: state.fontManager.searchResult,
  selectedFont: state.fontManager.selectedFont,
  msg: state.fontManager.msg
})

/**
 * Connect and dispatch redux actions as props
 *
 * @since 6.0
 */
export default connect(mapStateToProps, {
  clearAddFontMsg,
  deleteFont,
  selectFont,
  moveSelectedFontToTop
})(FontListItems)
