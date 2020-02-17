import React from 'react'
import PropTypes from 'prop-types'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Display error
 *
 * @param displayError (string)
 * @returns {*}
 *
 * @since 5.2
 */
const DisplayError = ({ displayError }) => {
  return (
    <li className='error'>{displayError}</li>
  )
}

/**
 *  @since 5.2
 */
DisplayError.propTypes = {
  displayError: PropTypes.string
}

export default DisplayError
