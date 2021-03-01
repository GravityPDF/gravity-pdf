/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'
import { sprintf } from 'sprintf-js'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display information for drop box font variant label
 *
 * @param label
 * @param font
 *
 * @since 6.0
 */
const FontVariantLabel = ({ label, font }) => (
  <div data-test='component-FontVariantLabel' htmlFor={'gfpdf-font-variant-' + label}>
    {(label === 'regular' && font === 'false') && (
      <span
        dangerouslySetInnerHTML={{
          __html: sprintf(GFPDF.fontListRegularRequired, '' + "<span class='required'>", '</span>')
        }}
      />
    )}
    {(label === 'regular' && font === 'true') && GFPDF.fontListRegular}
    {label === 'italics' && GFPDF.fontListItalics}
    {label === 'bold' && GFPDF.fontListBold}
    {label === 'bolditalics' && GFPDF.fontListBoldItalics}
  </div>
)

/**
 * PropTypes
 *
 * @since 6.0
 */
FontVariantLabel.propTypes = {
  label: PropTypes.string.isRequired,
  font: PropTypes.string
}

export default FontVariantLabel
