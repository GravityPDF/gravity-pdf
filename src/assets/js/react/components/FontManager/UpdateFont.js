import React from 'react'
import PropTypes from 'prop-types'
import FontVariant from './FontVariant'
import Kashida from './Kashida'
import AddFontFooter from './AddFontFooter'
import { sprintf } from 'sprintf-js'

export const UpdateFont = (
  {
    id,
    fontList,
    label,
    onHandleInputChange,
    onHandleKashidaChange,
    onHandleUpload,
    onHandleDeleteFontStyle,
    onHandleCancelEditFont,
    onHandleSubmit,
    fontStyles,
    kashida,
    validateLabel,
    validateRegular,
    disableUpdateButton,
    msg,
    loading,
    tabIndexFontName,
    tabIndexFontFiles,
    tabIndexKashida,
    tabIndexFooterButtons
  }
) => {
  const font = fontList && fontList.filter(font => font.id === id)[0]
  const useOTL = font && font.useOTL
  const fontNameLabel = sprintf(GFPDF.fontManagerFontNameLabel, "<span class='required'>", '</span>')

  return (
    <div className='update-font'>
      <form onSubmit={onHandleSubmit}>
        <h2>{GFPDF.fontManagerUpdateTitle}</h2>

        <p>{GFPDF.fontManagerUpdateDesc}</p>

        <label htmlFor='gfpdf-font-name-input' dangerouslySetInnerHTML={{ __html: fontNameLabel }} />

        <p id='gfpdf-font-name-desc'>{GFPDF.fontManagerFontNameDesc}</p>

        <input
          type='text'
          id='gfpdf-font-name-input'
          className={!validateLabel ? 'input-label-validation-error' : ''}
          aria-describedby='gfpdf-font-name-desc'
          name='label'
          value={label}
          maxLength='60'
          onChange={e => onHandleInputChange(e, 'updateFont')}
          tabIndex={tabIndexFontName}
        />

        {!validateLabel && (
          <span className='required'>
            <em>{GFPDF.fontManagerFontNameValidationError}</em>
          </span>
        )}

        <label id='gfpdf-font-files-label'>{GFPDF.fontManagerFontFilesLabel}</label>

        <p id='gfpdf-font-files-description'>{GFPDF.fontManagerFontFilesDesc}</p>

        <FontVariant
          state='updateFont'
          fontStyles={fontStyles}
          validateRegular={validateRegular}
          onHandleUpload={onHandleUpload}
          onHandleDeleteFontStyle={onHandleDeleteFontStyle}
          msg={msg}
          tabIndex={tabIndexFontFiles}
        />

        {useOTL > 0 && (
          <Kashida
            kashida={kashida}
            onHandleKashidaChange={onHandleKashidaChange}
            tabIndex={tabIndexKashida}
          />
        )}

        <AddFontFooter
          id={id}
          disabled={disableUpdateButton}
          onHandleCancelEditFont={onHandleCancelEditFont}
          msg={msg}
          loading={loading}
          tabIndex={tabIndexFooterButtons}
        />
      </form>
    </div>
  )
}

UpdateFont.propTypes = {
  id: PropTypes.string,
  fontList: PropTypes.arrayOf(PropTypes.object),
  label: PropTypes.string.isRequired,
  onHandleInputChange: PropTypes.func.isRequired,
  onHandleKashidaChange: PropTypes.func,
  onHandleUpload: PropTypes.func.isRequired,
  onHandleDeleteFontStyle: PropTypes.func.isRequired,
  onHandleCancelEditFont: PropTypes.func.isRequired,
  onHandleSubmit: PropTypes.func.isRequired,
  validateLabel: PropTypes.bool.isRequired,
  validateRegular: PropTypes.bool.isRequired,
  disableUpdateButton: PropTypes.bool.isRequired,
  fontStyles: PropTypes.object.isRequired,
  kashida: PropTypes.number,
  msg: PropTypes.object.isRequired,
  loading: PropTypes.bool.isRequired,
  tabIndexFontName: PropTypes.string.isRequired,
  tabIndexFontFiles: PropTypes.string.isRequired,
  tabIndexKashida: PropTypes.string.isRequired,
  tabIndexFooterButtons: PropTypes.string.isRequired
}

export default UpdateFont
