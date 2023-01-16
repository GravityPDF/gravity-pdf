/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import { sprintf } from 'sprintf-js'
/* Components */
import Spinner from '../Spinner'
/* Redux actions */
import { selectFont, deleteFont } from '../../actions/fontManager'
import TemplateTooltip from './TemplateTooltip'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display footer of add font panel UI
 *
 * @param state
 * @param id
 * @param disabled
 * @param onHandleCancelEditFont
 * @param onHandleCancelEditFontKeypress
 * @param success
 * @param error
 * @param loading
 * @param tabIndex
 *
 * @since 6.0
 */
export class AddUpdateFontFooter extends Component {
  /**
   * PropTypes
   *
   * @since 6.0
   */
  static propTypes = {
    state: PropTypes.string,
    id: PropTypes.string,
    disabled: PropTypes.bool,
    onHandleCancelEditFont: PropTypes.func,
    onHandleCancelEditFontKeypress: PropTypes.func,
    selectedFont: PropTypes.string.isRequired,
    selectFont: PropTypes.func.isRequired,
    deleteFont: PropTypes.func,
    msg: PropTypes.object.isRequired,
    loading: PropTypes.bool.isRequired,
    tabIndex: PropTypes.string.isRequired
  }

  /**
   * Handle the functionality to select and set default font type to be used in PDFs
   * (Under 'update font' panel)
   *
   * @param fontId: string
   * @param selectedFont: string
   *
   * @since 6.0
   */
  handleSelectFont = (fontId, selectedFont) => {
    const { selectFont } = this.props

    if (fontId === selectedFont) {
      return selectFont('')
    }

    selectFont(fontId)
  }

  /**
   * Handle the functionality to select and set default font type to be used in PDFs
   * (Under 'update font' panel - Keyboard press)
   *
   * @param e: object
   * @param fontId: string
   * @param selectedFont: string
   *
   * @since 6.0
   */
  handleSelectFontKeypress = (e, fontId, selectedFont) => {
    const enter = 13
    const space = 32
    const { selectFont } = this.props

    if (e.keyCode === enter || e.keyCode === space) {
      if (fontId === selectedFont) {
        return selectFont('')
      }

      selectFont(fontId)
    }
  }

  /**
   * Handle request of font deletion (Under 'update font' panel)
   *
   * @param fontId: string
   *
   * @since 6.0
   */
  handleDeleteFont = fontId => {
    /* Fire a native window alert box to confirm deletion request */
    if (window.confirm(GFPDF.fontManagerDeleteFontConfirmation)) {
      /* Call redux action deleteFont */
      this.props.deleteFont(fontId)
    }
  }

  /**
   * Handle request of font deletion (Under 'update font' panel - Keyboard press)
   *
   * @param e: object
   * @param fontId: string
   *
   * @since 6.0
   */
  handleDeleteFontKeypress = (e, fontId) => {
    const enter = 13
    const space = 32

    if (e.keyCode === enter || e.keyCode === space) {
      /* Fire a native window alert box to confirm deletion request */
      if (window.confirm(GFPDF.fontManagerDeleteFontConfirmation)) {
        /* Call redux action deleteFont */
        this.props.deleteFont(fontId)
      }
    }
  }

  render () {
    const {
      state,
      id,
      disabled,
      onHandleCancelEditFont,
      onHandleCancelEditFontKeypress,
      selectedFont,
      msg: { success, error },
      loading,
      tabIndex
    } = this.props
    const cancelButton = document.querySelector('.footer button.cancel')
    const errorFontList = error && error.fontList
    const successAddFont = success && success.addFont
    const showSuccessAddFont = (successAddFont && errorFontList) || (successAddFont && !state)
    const errorAddFont = (error && error.addFont) && error.addFont
    const errorFontValidation = (errorAddFont && error.fontValidationError) && error.fontValidationError
    const fontFileMissing = sprintf(GFPDF.fontFileMissing, '<strong>', '</strong>')
    const selectedBoxStyle = (id !== '') && (id === selectedFont) ? ' checked' : ' uncheck'
    /* Display error message for uploading invalid font file */
    const displayInvalidFileErrorMessage = errorAddFont && errorFontValidation
    /* Display generic error messages including missing font file */
    const displayGenericErrorMessage = errorAddFont && !errorFontValidation

    return (
      <footer
        data-test='component-AddFontFooter'
        className={'footer' + (cancelButton ? ' cancel' : '')}
      >
        <div className='buttons-icons-container'>
          <div>
            {id && (
              <button
                className='button gfpdf-button primary cancel'
                onClick={onHandleCancelEditFont}
                onKeyDown={onHandleCancelEditFontKeypress}
                type='button'
                tabIndex={tabIndex}
                aria-label={GFPDF.cancel}
              >
                {GFPDF.fontManagerCancelButtonText}
              </button>
            )}

            <button
              className='button gfpdf-button primary'
              tabIndex={tabIndex}
              disabled={disabled}
              aria-label={GFPDF.fontManagerUpdateFontAriaLabel}
            >
              {id ? GFPDF.fontManagerUpdateTitle + ' →' : GFPDF.fontManagerAddTitle + ' →'}
            </button>

            {loading && <Spinner style='add-update-font' />}
          </div>

          <div className='select-delete-icons-container'>
            {id && (
              <button
                className={'dashicons dashicons-yes' + selectedBoxStyle}
                onClick={() => this.handleSelectFont(id, selectedFont)}
                onKeyDown={e => this.handleSelectFontKeypress(e, id, selectedFont)}
                type='button'
                tabIndex={tabIndex}
                aria-label={GFPDF.fontManagerSelectFontAriaLabel}
              />
            )}

            {id && (
              <button
                className='dashicons dashicons-trash'
                onClick={() => this.handleDeleteFont(id)}
                onKeyDown={e => this.handleDeleteFontKeypress(e, id)}
                type='button'
                tabIndex={tabIndex}
                aria-label={GFPDF.fontManagerDeleteFontAriaLabel}
              />
            )}
          </div>
        </div>

        {showSuccessAddFont && (
          <span className='msg success' dangerouslySetInnerHTML={{ __html: success.addFont }} />
        )}

        {displayInvalidFileErrorMessage && (
          <span
            className='msg error'
            dangerouslySetInnerHTML={{ __html: errorFontValidation }}
          />
        )}

        {displayGenericErrorMessage && (
          <span
            className='msg error'
            dangerouslySetInnerHTML={{
              __html: typeof error.addFont === 'object' ? fontFileMissing : error.addFont
            }}
          />
        )}

        {id && <TemplateTooltip id={id} />}
      </footer>
    )
  }
}

/**
 * Map redux state to props
 *
 * @param state: object
 *
 * @returns {{ selectedFont: string }}
 *
 * @since 6.0
 */
const mapStateToProps = state => ({
  selectedFont: state.fontManager.selectedFont
})

/**
 * Connect and dispatch redux actions as props
 *
 * @since 6.0
 */
export default connect(mapStateToProps, { selectFont, deleteFont })(AddUpdateFontFooter)
