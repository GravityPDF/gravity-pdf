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
    <h1>Add Font</h1>
    <p>Install new fonts for use in your PDF documents.</p>

    <h2>Font Name <span className='required'>*</span></h2>
    <p>The font name must be unique and only contain alphanumeric characters.</p>
    <input
      type='text'
      name='label'
      value={label}
      onChange={onHandleInputChange}
      required
    />

    <div className='font-files'>
      <h2>Font Files</h2>
      <p>
        Select or drag and drop your .ttf font file into one of the font variants.
        <br />
        Only the Regular variant is required.
      </p>

      <FontVariant
        fontStyles={fontStyles}
        onHandleUpload={onHandleUpload}
        onHandleDeleteFontStyle={onHandleDeleteFontStyle}
      />

      <input
        className='button gfpdf-button'
        type='submit'
        value={id ? 'Update Font' : 'Save Font'}
      />
    </div>
  </form>
)

export default AddFontName
