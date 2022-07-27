import React from 'react'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Display the empty search results
 *
 * @returns {*}
 *
 * @since 5.2
 */
const DisplayResultEmpty = () => {
  return (
    <li className='noResult'>{GFPDF.noResultText}</li>
  )
}

export default DisplayResultEmpty
