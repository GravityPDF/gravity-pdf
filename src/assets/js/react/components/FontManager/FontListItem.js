import React from 'react'

const FontListItem = () => (
  <div className='font-list-item'>
    <div className='installed-font'>
      <span className='dashicons dashicons-trash' />
      <span className='font-name'>Roboto</span>
    </div>
    <div className='variants regular'>
      <span className='dashicons dashicons-yes' />
    </div>
    <div className='variants italics'>
      <span className='dashicons dashicons-yes' />
    </div>
    <div className='variants bold'>
      <span className='dashicons dashicons-no-alt' />
    </div>
    <div className='variants bold-italics'>
      <span className='dashicons dashicons-no-alt' />
    </div>
  </div>
)

export default FontListItem
