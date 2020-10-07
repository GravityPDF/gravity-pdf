/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display update font panel 'kashida' field UI
 *
 * @param kashida
 * @param onHandleKashidaChange
 * @param tabIndex
 *
 * @since 6.0
 */
export const Kashida = ({ kashida, onHandleKashidaChange, tabIndex }) => (
  <div data-test='component-Kashida' className='kashida'>
    <label htmlFor='gfpdf-kashida-input'>{GFPDF.fontManagerKashidaLabel}</label>

    <p id='gfpdf-kashida-input-desc'>{GFPDF.fontManagerKashidaDesc}</p>

    <input
      type='number'
      className='kashida-input'
      aria-describedby='gfpdf-kashida-input-desc'
      min='0'
      max='100'
      name='gfpdf-kashida-input'
      value={kashida}
      onChange={onHandleKashidaChange}
      tabIndex={tabIndex}
    />
  </div>
)

/**
 * PropTypes
 *
 * @since 6.0
 */
Kashida.propTypes = {
  kashida: PropTypes.number.isRequired,
  onHandleKashidaChange: PropTypes.func.isRequired,
  tabIndex: PropTypes.string.isRequired
}

export default Kashida
