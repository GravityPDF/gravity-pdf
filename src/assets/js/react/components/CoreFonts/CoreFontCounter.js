import React from 'react'
import PropTypes from 'prop-types'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Display an inline counter
 *
 * @param queue
 * @param text
 *
 * @since 5.0
 */
const CoreFontCounter = ({ queue, text }) => (
  <span className='gfpdf-core-font-counter'>{text} {queue}</span>
)

/**
 *
 * @since 5.0
 */
CoreFontCounter.propTypes = {
  queue: PropTypes.number,
  text: PropTypes.string
}

export default CoreFontCounter
