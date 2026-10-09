/* Dependencies */
import React, { useContext } from 'react'
import PropTypes from 'prop-types'
/* Components */
import { TemplateUploaderContext } from './TemplateUploaderContext'

/**
 * The "Add New Template" tile, which opens the file picker owned by <TemplateUploader />
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.18.0
 */

/**
 * React Component
 *
 * @param {Object} props
 * @param {string} props.addTemplateText
 * @param {string} props.templateInstallInstructions
 *
 * @since 6.18.0
 */
const TemplateUploaderTile = ({ addTemplateText, templateInstallInstructions }) => {
  const { open, isUploading } = useContext(TemplateUploaderContext)

  const handleClick = (e) => {
    e.preventDefault()
    open()
  }

  return (
    <div data-test='component-templateUploaderTile' className='theme add-new-theme gfpdf-dropzone'>
      <a
        href='#/template'
        className={isUploading ? 'doing-ajax' : ''}
        onClick={handleClick}
        aria-labelledby='gfpdf-template-install-instructions'
      >
        <div className='theme-screenshot'><span /></div>

        <h2 className='theme-name'>{addTemplateText}</h2>
      </a>

      <div className='gfpdf-template-install-instructions' id='gfpdf-template-install-instructions'>
        {templateInstallInstructions}
      </div>
    </div>
  )
}

TemplateUploaderTile.propTypes = {
  addTemplateText: PropTypes.string,
  templateInstallInstructions: PropTypes.string
}

export default TemplateUploaderTile
