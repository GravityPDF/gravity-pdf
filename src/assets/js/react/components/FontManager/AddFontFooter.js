/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'
import { sprintf } from 'sprintf-js'
/* Components */
import Spinner from '../Spinner'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
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
const AddFontFooter = ({
  state,
  id,
  disabled,
  onHandleCancelEditFont,
  onHandleCancelEditFontKeypress,
  msg: { success, error },
  loading,
  tabIndex
}) => {
  const cancelButton = document.querySelector('.footer button.cancel')
  const errorFontList = error && error.fontList
  const successAddFont = success && success.addFont
  const showSuccessAddFont = (successAddFont && errorFontList) || (successAddFont && !state)
  const errorAddFont = (error && error.addFont) && error.addFont
  const errorFontValidation = (errorAddFont && error.fontValidationError) && error.fontValidationError
  const fontFileMissing = sprintf(GFPDF.fontFileMissing, '<strong>', '</strong>')
  /* Display error message for uploading invalid font file */
  const displayInvalidFileErrorMessage = errorAddFont && errorFontValidation
  /* Display generic error messages including missing font file */
  const displayGenericErrorMessage = errorAddFont && !errorFontValidation

  return (
    <footer className={'footer' + (cancelButton ? ' cancel' : '')}>
      {id && (
        <div
          className='button gfpdf-button primary cancel'
          onClick={onHandleCancelEditFont}
          onKeyDown={onHandleCancelEditFontKeypress}
          tabIndex={tabIndex}
        >
          {GFPDF.fontManagerCancelButtonText}
        </div>
      )}

      <button
        className='button gfpdf-button primary'
        tabIndex={tabIndex}
        disabled={disabled}
      >
        {id ? GFPDF.fontManagerUpdateTitle + ' →' : GFPDF.fontManagerAddTitle + ' →'}
      </button>

      {loading && <Spinner style='add-update-font' />}

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
    </footer>
  )
}

/**
 * PropTypes
 *
 * @since 6.0
 */
AddFontFooter.propTypes = {
  state: PropTypes.string,
  id: PropTypes.string,
  disabled: PropTypes.bool,
  onHandleCancelEditFont: PropTypes.func,
  onHandleCancelEditFontKeypress: PropTypes.func,
  msg: PropTypes.object.isRequired,
  loading: PropTypes.bool.isRequired,
  tabIndex: PropTypes.string.isRequired
}

export default AddFontFooter
