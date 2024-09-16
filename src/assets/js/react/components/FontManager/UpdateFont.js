/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'
import { sprintf } from 'sprintf-js'
/* Components */
import FontVariant from './FontVariant'
import AddUpdateFontFooter from './AddUpdateFontFooter'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2024, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display update font panel UI
 *
 * @param id
 * @param fontList
 * @param label
 * @param onHandleInputChange
 * @param onHandleUpload
 * @param onHandleDeleteFontStyle
 * @param onHandleCancelEditFont
 * @param onHandleCancelEditFontKeypress
 * @param onHandleSubmit
 * @param fontStyles
 * @param validateLabel
 * @param validateRegular
 * @param disableUpdateButton
 * @param msg
 * @param loading
 * @param isUpdating
 *
 * @since 6.0
 */
export const UpdateFont = (
  {
    id,
    label,
    onHandleInputChange,
    onHandleUpload,
    onHandleDeleteFontStyle,
    onHandleCancelEditFont,
    onHandleCancelEditFontKeypress,
    onHandleSubmit,
    fontStyles,
    validateLabel,
    validateRegular,
    disableUpdateButton,
    msg,
    loading,
    isUpdating
  }
) => {
  const fontNameLabel = sprintf(GFPDF.fontManagerFontNameLabel, '<span class=\'required\'>', '</span>')

  return (
    <div data-test='component-UpdateFont' className='update-font'>
      <form onSubmit={onHandleSubmit}>
        <h2>{GFPDF.fontManagerUpdateTitle}</h2>

        <p>{GFPDF.fontManagerUpdateDesc}</p>

        <label htmlFor='gfpdf-font-name-input' dangerouslySetInnerHTML={{ __html: fontNameLabel }} />

        <p id='gfpdf-font-name-desc-update'>{GFPDF.fontManagerFontNameDesc}</p>

        <input
          type='text'
          id='gfpdf-update-font-name-input'
          className={!validateLabel ? 'input-label-validation-error' : ''}
          aria-describedby='gfpdf-font-name-desc-update'
          name='label'
          value={label}
          maxLength='60'
          onChange={e => onHandleInputChange(e, 'updateFont')}
          disabled={!isUpdating}
        />

        <div aria-live='polite'>
          {!validateLabel && (
            <span className='required' role='alert'>
              <em>{GFPDF.fontManagerFontNameValidationError}</em>
            </span>
          )}
        </div>

        <label id='gfpdf-font-files-label-update' aria-labelledby='gfpdf-font-files-description-update'>{GFPDF.fontManagerFontFilesLabel}</label>

        <p id='gfpdf-font-files-description-update'>{GFPDF.fontManagerFontFilesDesc}</p>

        <FontVariant
          state='updateFont'
          fontStyles={fontStyles}
          validateRegular={validateRegular}
          onHandleUpload={onHandleUpload}
          onHandleDeleteFontStyle={onHandleDeleteFontStyle}
          msg={msg}
          disabled={!isUpdating}
        />

        <AddUpdateFontFooter
          id={id}
          label={label}
          disabled={disableUpdateButton || !isUpdating}
          onHandleCancelEditFont={onHandleCancelEditFont}
          onHandleCancelEditFontKeypress={onHandleCancelEditFontKeypress}
          msg={msg}
          loading={loading}
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
UpdateFont.propTypes = {
  id: PropTypes.string,
  label: PropTypes.string.isRequired,
  onHandleInputChange: PropTypes.func.isRequired,
  onHandleUpload: PropTypes.func.isRequired,
  onHandleDeleteFontStyle: PropTypes.func.isRequired,
  onHandleCancelEditFont: PropTypes.func.isRequired,
  onHandleCancelEditFontKeypress: PropTypes.func.isRequired,
  onHandleSubmit: PropTypes.func.isRequired,
  validateLabel: PropTypes.bool.isRequired,
  validateRegular: PropTypes.bool.isRequired,
  disableUpdateButton: PropTypes.bool.isRequired,
  fontStyles: PropTypes.object.isRequired,
  msg: PropTypes.object.isRequired,
  loading: PropTypes.bool.isRequired,
  isUpdating: PropTypes.bool.isRequired
}

export default UpdateFont
