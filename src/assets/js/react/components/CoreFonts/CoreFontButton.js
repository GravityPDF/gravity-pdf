import React from 'react'
import PropTypes from 'prop-types'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (c) 2020, Blue Liquid Designs

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Found
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
