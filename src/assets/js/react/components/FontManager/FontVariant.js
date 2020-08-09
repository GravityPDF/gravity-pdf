import React from 'react'

export const FontVariant = ({ fontStyles, onHandleUpload, onHandleDeleteFontStyle }) => {
  const fontStyleArray = Object.entries(fontStyles)
  const array = []
  const columns = 2
  let key = 0

  while (fontStyleArray.length > 0) {
    array.push(fontStyleArray.splice(0, columns))
  }

  return (
    <div>
      {
        array.map(variants => (
          <div key={key++} className='variants'>
            {variants.map(variant => (
              <label key={key++} className={'drop-zone' + (variant[1] ? ' active' : '')}>
                {variant[1] ? (
                  <input onClick={e => onHandleDeleteFontStyle(e, variant[0])} />
                ) : <input type='file' name={variant[0]} onChange={onHandleUpload} />}
                <span className={'dashicons dashicons-' + (variant[1] ? 'trash' : 'plus')} />

                <h3>
                  {variant[0]} <span className='required'>{variant[0] === 'regular' ? '*' : ''}</span>
                </h3>
              </label>
            ))}
          </div>
        ))
      }
    </div>
  )
}

export default FontVariant
