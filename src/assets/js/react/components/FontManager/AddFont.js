/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'
import { sprintf } from 'sprintf-js'
/* Components */
import FontVariant from './FontVariant'
import AddUpdateFontFooter from './AddUpdateFontFooter'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display add font panel UI
 *
 * @param label
 * @param onHandleInputChange
 * @param onHandleUpload
 * @param onHandleDeleteFontStyle
 * @param onHandleSubmit
 * @param fontStyles
 * @param validateLabel
 * @param validateRegular
 * @param msg
 * @param loading
 * @param tabIndexFontName
 * @param tabIndexFontFiles
 * @param tabIndexFooterButtons
 *
 * @since 6.0
 */
export const AddFont = (
  {
    label,
    onHandleInputChange,
    onHandleUpload,
    onHandleDeleteFontStyle,
    onHandleSubmit,
    fontStyles,
    validateLabel,
    validateRegular,
    msg,
    loading,
    tabIndexFontName,
    tabIndexFontFiles,
    tabIndexFooterButtons
  }
) => {
  const fontNameLabel = sprintf(GFPDF.fontManagerFontNameLabel, "<span class='required'>", '</span>')

  return (
    <div data-test='component-AddFont' className='add-font'>
      <form onSubmit={onHandleSubmit}>
        <h2>{GFPDF.fontManagerAddTitle}</h2>

        <p>{GFPDF.fontManagerAddDesc}</p>

        <label htmlFor='gfpdf-font-name-input' dangerouslySetInnerHTML={{ __html: fontNameLabel }} />

        <p id='gfpdf-font-name-desc-add'>{GFPDF.fontManagerFontNameDesc}</p>

        <input
          type='text'
          id='gfpdf-add-font-name-input'
          className={!validateLabel ? 'input-label-validation-error' : ''}
          aria-describedby='gfpdf-font-name-desc-add'
          name='label'
          value={label}
          maxLength='60'
          onChange={e => onHandleInputChange(e, 'addFont')}
          tabIndex={tabIndexFontName}
        />

        <div aria-live='polite'>
          {!validateLabel && (
            <span className='required' role='alert'>
              <em>{GFPDF.fontManagerFontNameValidationError}</em>
            </span>
          )}
        </div>

        <label id='gfpdf-font-files-label-add' aria-labelledby='gfpdf-font-files-description-add'>{GFPDF.fontManagerFontFilesLabel}</label>

        <p id='gfpdf-font-files-description-add'>{GFPDF.fontManagerFontFilesDesc}</p>

        <FontVariant
          state='addFont'
          fontStyles={fontStyles}
          validateRegular={validateRegular}
          onHandleUpload={onHandleUpload}
          onHandleDeleteFontStyle={onHandleDeleteFontStyle}
          msg={msg}
          tabIndex={tabIndexFontFiles}
        />

        <AddUpdateFontFooter
          state='addFont'
          msg={msg}
          loading={loading}
          tabIndex={tabIndexFooterButtons}
        />
      </form>
    </div>
  )
}

/**
 * PropTypes
 *
 * @since 6.0
 */
AddFont.propTypes = {
  label: PropTypes.string.isRequired,
  onHandleInputChange: PropTypes.func.isRequired,
  onHandleUpload: PropTypes.func.isRequired,
  onHandleDeleteFontStyle: PropTypes.func.isRequired,
  onHandleSubmit: PropTypes.func.isRequired,
  validateLabel: PropTypes.bool.isRequired,
  validateRegular: PropTypes.bool.isRequired,
  fontStyles: PropTypes.object.isRequired,
  msg: PropTypes.object.isRequired,
  loading: PropTypes.bool.isRequired,
  tabIndexFontName: PropTypes.string.isRequired,
  tabIndexFontFiles: PropTypes.string.isRequired,
  tabIndexFooterButtons: PropTypes.string.isRequired
}

export default AddFont
