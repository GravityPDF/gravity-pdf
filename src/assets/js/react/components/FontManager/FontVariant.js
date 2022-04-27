/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'
import Dropzone from 'react-dropzone'
/* Components */
import FontVariantLabel from './FontVariantLabel'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display font variant drop box UI
 *
 * @param state
 * @param fontStyles
 * @param validateRegular
 * @param onHandleUpload
 * @param onHandleDeleteFontStyle
 * @param error
 * @param disabled
 *
 * @since 6.0
 */
export const FontVariant = ({
  state,
  fontStyles,
  validateRegular,
  onHandleUpload,
  onHandleDeleteFontStyle,
  msg: { error },
  disabled
}) => (
  <div data-test='component-FontVariant' id='gfpdf-font-files-setting'>
    {Object.entries(fontStyles).map(([key, font]) => {
      const id = 'gfpdf-font-variant-' + key + ' ' + state
      const ariaLabelledby = id + ' gfpdf-font-files-label'
      const ariaDescribedby = 'gfpdf-font-files-description'
      const currentUploadFontName = font !== '' && typeof font !== 'object'
      const fontName = currentUploadFontName ? font.substr(font.lastIndexOf('/') + 1) : font.name
      const fontFileMissing = (error && typeof error.addFont === 'object') && error.addFont[key]
      const regularFieldValidation = (key === 'regular' && !validateRegular) && fontStyles.regular === ''
      const dropZoneActive = font ? ' active' : ''
      const dropZoneError = fontFileMissing ? ' error' : ''
      const dropZoneRequiredRegular = regularFieldValidation ? ' required' : ''
      const dropZoneClassEnhancement = dropZoneActive + dropZoneError + dropZoneRequiredRegular
      const dropZoneIcon = font ? 'trash' : 'plus'
      const displayRequiredText = font ? 'true' : 'false'

      return (
        <Dropzone
          data-test='component-Dropzone'
          key={key}
          accept='.ttf'
          onDrop={acceptedFiles => onHandleUpload(key, acceptedFiles[0], state)}
          multiple={false}
          disabled={disabled}
        >
          {({ getRootProps, getInputProps }) => (
            <a
              className={'drop-zone' + dropZoneClassEnhancement}
              {...getRootProps()}
            >
              {font ? (
                <input
                  data-test='input-delete'
                  id={id}
                  aria-labelledby={ariaLabelledby}
                  aria-describedby={ariaDescribedby}
                  {...getInputProps({ onClick: e => onHandleDeleteFontStyle(e, key, state) })}
                />
              ) : (
                <input
                  data-test='input-add'
                  id={id}
                  aria-labelledby={ariaLabelledby}
                  aria-describedby={ariaDescribedby}
                  {...getInputProps()}
                />
              )}

              <span className='gfpdf-font-filename'>
                {regularFieldValidation && (
                  <span className='required'>{GFPDF.fontManagerFontFileRequiredRegular}</span>
                )}
                {!fontFileMissing ? fontName : fontFileMissing}
              </span>

              <span className={'dashicons dashicons-' + dropZoneIcon} />

              <FontVariantLabel label={key} font={displayRequiredText} />
            </a>
          )}
        </Dropzone>
      )
    })}
  </div>
)

/**
 * PropTypes
 *
 * @since 6.0
 */
FontVariant.propTypes = {
  state: PropTypes.string.isRequired,
  fontStyles: PropTypes.object.isRequired,
  validateRegular: PropTypes.bool.isRequired,
  onHandleUpload: PropTypes.func.isRequired,
  onHandleDeleteFontStyle: PropTypes.func.isRequired,
  msg: PropTypes.object.isRequired,
  disabled: PropTypes.bool
}

export default FontVariant
