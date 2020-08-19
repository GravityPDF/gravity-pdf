import React from 'react'
import FontVariant from './FontVariant'

export const AddFontName = (
  {
    id,
    label,
    onHandleInputChange,
    onHandleUpload,
    onHandleDeleteFontStyle,
    onHandleSubmit,
    fontStyles
  }
) => (
  <form className='add-font' onSubmit={onHandleSubmit}>
    <h2>{id ? 'Update Font' : 'Add Font'}</h2>
    <p>
      {id ? 'Once saved, PDFs configured to use this font will have your changes applied automatically for newly-generated documents.' : 'Install new fonts for use in your PDF documents.'}
    </p>

    <label htmlFor='gfpdf-add-font-name-input'>Font Name <span className='required'>(required)</span></label>
    <p>The font name can only contain alphanumeric characters or spaces.</p>
    <input
      id='gfpdf-add-font-name-input'
      type='text'
      name='label'
      value={label}
      onChange={onHandleInputChange}
      required
    />

    <label>Font Files <span className='required'>(required: Regular)</span></label>
    <p>
    Select or drag and drop your .ttf font file for the variants below. Only the Regular type is required.
    </p>

    <FontVariant
      fontStyles={fontStyles}
      onHandleUpload={onHandleUpload}
      onHandleDeleteFontStyle={onHandleDeleteFontStyle}
    />

    <button className='button gfpdf-button primary'>{id ? 'Update Font  →' : 'Add Font  →'}</button>
  </form>
)

export default AddFontName
