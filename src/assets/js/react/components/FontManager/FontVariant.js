import React from 'react'

export const FontVariant = ({ fontStyles, onHandleUpload, onHandleDeleteFontStyle }) => {
  const fontStyleArray = Object.entries(fontStyles)

  return (
    <div id='gfpdf-font-files-setting'>
      {fontStyleArray.map(([key, font]) => (
        <label tabIndex='0' key={key} htmlFor={'gfpdf-font-variant-' + key} className={'drop-zone' + (font ? ' active' : '')}>
          {
            font
              ? <input id={'gfpdf-font-variant-' + key} onClick={e => onHandleDeleteFontStyle(e, key)} />
              : <input id={'gfpdf-font-variant-' + key} type='file' name={key} onChange={onHandleUpload} />
          }

          <span className={'dashicons dashicons-' + (font ? 'trash' : 'plus')} />

          <label htmlFor={'gfpdf-font-variant-' + key}>{key}</label>
        </label>
      ))}
    </div>
  )
}

export default FontVariant
