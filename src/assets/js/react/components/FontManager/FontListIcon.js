/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display 'x' or 'check' icon in font detail to indicate if font variant is installed or not
 *
 * @param font
 *
 * @since 6.0
 */
const FontListIcon = ({ font }) => (
  <div data-test='component-FontListIcon'>
    <span className={'dashicons dashicons-' + (font ? 'yes' : 'no-alt')} />
  </div>
)

/**
 * PropTypes
 *
 * @since 6.0
 */
FontListIcon.propTypes = {
  font: PropTypes.string.isRequired
}

export default FontListIcon
