import React from 'react'
import PropTypes from 'prop-types'
import { sprintf } from 'sprintf-js'

const FontVariantLabel = ({ label, font }) => (
  <div htmlFor={'gfpdf-font-variant-' + label}>
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

FontVariantLabel.propTypes = {
  label: PropTypes.string.isRequired,
  font: PropTypes.string
}

export default FontVariantLabel
