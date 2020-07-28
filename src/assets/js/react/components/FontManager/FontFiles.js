import React from 'react'

const FontFiles = () => (
  <div className='font-files'>
    <h2>Font Files</h2>
    <p>
      Select or drag and drop your .ttf font file into one of the font variants.
      <br />
      Only the Regular variant is required.
    </p>

    <div className='variants'>
      <label className='drop-zone'>
        <input type='file' />
        <span className='dashicons dashicons-plus' />
        <h3>Regular <span className='required'>*</span></h3>
      </label>
      <label className='drop-zone'>
        <input type='file' />
        <span className='dashicons dashicons-trash' />
        <h3>Italic</h3>
      </label>
    </div>
    <div className='variants'>
      <label className='drop-zone'>
        <input type='file' />
        <span className='dashicons dashicons-plus' />
        <h3>Bold</h3>
      </label>
      <label className='drop-zone'>
        <input type='file' />
        <span className='dashicons dashicons-plus' />
        <h3>Bold Italic</h3>
      </label>
    </div>

    <button className='button gfpdf-button'>Save Font</button>
  </div>
)

export default FontFiles
