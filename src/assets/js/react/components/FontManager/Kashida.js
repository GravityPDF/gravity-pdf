import React from 'react'
import PropTypes from 'prop-types'

export const Kashida = ({ kashida, onHandleKashidaChange, tabIndex }) => (
  <div className='kashida'>
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

Kashida.propTypes = {
  kashida: PropTypes.number.isRequired,
  onHandleKashidaChange: PropTypes.func.isRequired,
  tabIndex: PropTypes.string.isRequired
}

export default Kashida
