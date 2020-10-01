import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import { clearAddFontMsg, deleteFont, selectFont } from '../../actions/fontManager'
import { toggleUpdateFont } from '../../utilities/toggleUpdateFont'
import FontListIcon from './FontListIcon'
import Spinner from '../Spinner'

export class FontListItems extends Component {
  static propTypes = {
    id: PropTypes.string,
    history: PropTypes.object.isRequired,
    clearAddFontMsg: PropTypes.func.isRequired,
    msg: PropTypes.object.isRequired,
    deleteFont: PropTypes.func.isRequired,
    selectFont: PropTypes.func.isRequired,
    fontList: PropTypes.arrayOf(
      PropTypes.shape({
        font_name: PropTypes.string.isRequired,
        id: PropTypes.string.isRequired,
        useOTL: PropTypes.number.isRequired,
        useKashida: PropTypes.number.isRequired,
        regular: PropTypes.string.isRequired,
        italics: PropTypes.string.isRequired,
        bold: PropTypes.string.isRequired,
        bolditalics: PropTypes.string.isRequired
      })
    ).isRequired,
    searchResult: PropTypes.arrayOf(
      PropTypes.shape({
        font_name: PropTypes.string.isRequired,
        id: PropTypes.string.isRequired,
        useOTL: PropTypes.number.isRequired,
        useKashida: PropTypes.number.isRequired,
        regular: PropTypes.string.isRequired,
        italics: PropTypes.string.isRequired,
        bold: PropTypes.string.isRequired,
        bolditalics: PropTypes.string.isRequired
      })
    ),
    selectedFont: PropTypes.string.isRequired,
    loading: PropTypes.bool.isRequired
  }

  state = {
    disableSelectFontName: false,
    deleteId: ''
  }

  componentDidMount () {
    this.handleDisableSelectFields()
  }

  componentDidUpdate (prevProps, prevState) {
    const { loading } = this.props

    /* Reset/Clear deleteId loading state */
    if (prevProps.loading !== loading && !loading) {
      this.handleResetLoadingState()
    }
  }

  handleDisableSelectFields = () => {
    const tabLocation = window.location.search.substr(window.location.search.lastIndexOf('=') + 1)

    if (tabLocation === 'tools') {
      return this.setState({ disableSelectFontName: true })
    }

    this.handleSetSelectedFontNameValue(tabLocation)
  }

  handleSetSelectedFontNameValue = (location) => {
    let fontManagerSelectBoxValue
    const { fontList, selectFont } = this.props

    /* If location not under Global/General settings */
    if (location !== 'PDF' && location !== 'general') {
      fontManagerSelectBoxValue = document.querySelector('#gfpdf_settings\\[font\\]').value

      /* Check if select box selected value exist in font manager fontList */
      if (!this.handleCheckSelectBoxValue(fontList, fontManagerSelectBoxValue)) {
        return selectFont('')
      }

      return selectFont(fontManagerSelectBoxValue)
    }

    fontManagerSelectBoxValue = document.querySelector('#gfpdf_settings\\[default_font\\]').value

    /* Check if select box selected value exist in font manager fontList */
    if (!this.handleCheckSelectBoxValue(fontList, fontManagerSelectBoxValue)) {
      return selectFont('')
    }

    selectFont(fontManagerSelectBoxValue)
  }

  handleCheckSelectBoxValue = (fontList, value) => {
    const checkIfValueExist = fontList && fontList.filter(font => font.id === value)[0]

    if (!checkIfValueExist) {
      return false
    }

    return true
  }

  handleResetLoadingState = () => {
    this.setState({ deleteId: '' })
  }

  handleFontClick = fontId => {
    const { id, history, clearAddFontMsg, msg: { success, error } } = this.props

    /* Remove previous msg */
    if ((success && success.addFont) || (error && error.addFont)) {
      clearAddFontMsg()
    }

    if (id === fontId) {
      return toggleUpdateFont(history)
    }

    toggleUpdateFont(history, fontId)
  }

  handleFontClickKeypress = (e, fontId) => {
    if (e.keyCode === 13) {
      this.handleFontClick(fontId)
    }
  }

  handleDeleteFont = (e, fontId) => {
    e.stopPropagation()
    this.setState({ deleteId: fontId })

    const { deleteFont, history } = this.props

    if (window.confirm(GFPDF.fontManagerDeleteFontConfirmation)) {
      deleteFont(fontId)
      toggleUpdateFont(history)
    }
  }

  handleDeleteFontKeypress = (e, fontId) => {
    if (e.keyCode === 13) {
      this.handleDeleteFont(e, fontId)
    }
  }

  handleSelectFont = (e, click) => {
    const { selectedFont, selectFont } = this.props

    if (click) {
      return selectFont(e.target.value)
    }

    if (selectedFont === '') {
      return selectFont(e.target.value)
    }

    this.props.selectFont('')
  }

  handleSelectFontKeypress = e => {
    const enter = 13
    const space = 32

    if (e.keyCode === enter || e.keyCode === space) {
      e.preventDefault()
      e.stopPropagation()

      this.handleSelectFont(e)
    }
  }

  render () {
    const { disableSelectFontName, deleteId } = this.state
    const { id, loading, fontList, searchResult, selectedFont } = this.props
    const list = !searchResult ? fontList : searchResult

    return (
      <div className='font-list-items'>
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
                    id='selectFontName'
                    name={font.id}
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

const mapStateToProps = state => ({
  loading: state.fontManager.deleteFontLoading,
  fontList: state.fontManager.fontList,
  searchResult: state.fontManager.searchResult,
  selectedFont: state.fontManager.selectedFont,
  msg: state.fontManager.msg
})

export default connect(mapStateToProps, {
  clearAddFontMsg,
  deleteFont,
  selectFont
})(FontListItems)
