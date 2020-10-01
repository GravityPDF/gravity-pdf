import React from 'react'
import PropTypes from 'prop-types'
import { sprintf } from 'sprintf-js'
import Spinner from '../Spinner'

const AddFontFooter = ({
  state,
  id,
  disabled,
  onHandleCancelEditFont,
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

  return (
    <footer className={'footer' + (cancelButton ? ' cancel' : '')}>
      {id && (
        <div
          className='button gfpdf-button primary cancel'
          onClick={onHandleCancelEditFont}
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

      {/* Display error message for uploading invalid font file */}
      {(errorAddFont && errorFontValidation) && (
        <span
          className='msg error'
          dangerouslySetInnerHTML={{
            __html: errorFontValidation
          }}
        />
      )}

      {/* Display generic error messages including missing font file */}
      {(errorAddFont && !errorFontValidation) && (
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

AddFontFooter.propTypes = {
  state: PropTypes.string,
  id: PropTypes.string,
  disabled: PropTypes.bool,
  onHandleCancelEditFont: PropTypes.func,
  msg: PropTypes.object.isRequired,
  loading: PropTypes.bool.isRequired,
  tabIndex: PropTypes.string.isRequired
}

export default AddFontFooter
