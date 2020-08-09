import React from 'react'
import PropTypes from 'prop-types'
import CloseDialog from '../Modal/CloseDialog'

const FontManagerHeader = ({ id }) => (
  <div className='theme-header'>
    <h1>Font Manager</h1>

    <CloseDialog id={id} />
  </div>
)

FontManagerHeader.propTypes = {
  id: PropTypes.string
}

export default FontManagerHeader
