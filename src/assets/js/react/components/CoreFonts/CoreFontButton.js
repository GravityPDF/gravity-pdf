import React from 'react'
import PropTypes from 'prop-types'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Displays the button that initialises the Core Font download
 *
 * @param string className
 * @param func callback
 * @param string text
 * @param boolean disable
 *
 * @since 5.0
 */
const CoreFontButton = ({ className, callback, text, disable }) => (
  <button className={className} type='button' onClick={callback} disabled={disable}>
    {text}
  </button>
)

/**
 *  @since 5.0
 */
CoreFontButton.defaultProps = {
  disable: false
}

/**
 *  @since 5.0
 */
CoreFontButton.propTypes = {
  className: PropTypes.string,
  callback: PropTypes.func,
  text: PropTypes.string,
  disable: PropTypes.bool
}

export default CoreFontButton
