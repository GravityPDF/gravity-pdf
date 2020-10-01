import React from 'react'
import PropTypes from 'prop-types'

const FontListIcon = ({ font }) => (
  <div>
    <span className={'dashicons dashicons-' + (font ? 'yes' : 'no-alt')} />
  </div>
)

FontListIcon.propTypes = {
  font: PropTypes.string.isRequired
}

export default FontListIcon
