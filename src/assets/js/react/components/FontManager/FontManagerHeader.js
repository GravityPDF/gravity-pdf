/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'
/* Components */
import CloseDialog from '../Modal/CloseDialog'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display the header of font manager  UI
 *
 * @param id
 *
 * @since 6.0
 */
const FontManagerHeader = ({ id }) => (
  <div data-test='component-FontManagerHeader' className='theme-header'>
    <h1>Font Manager</h1>

    <CloseDialog id={id} />
  </div>
)

/**
 * PropTypes
 *
 * @since 6.0
 */
FontManagerHeader.propTypes = {
  id: PropTypes.string
}

export default FontManagerHeader
