import React from 'react'

export const FontList = ({ onHandleFontClick, onHandleDeleteFont, match, fontList }) => (
  <div className='font-list'>
    <div className='font-list-header'>
      <div />
      <div className='font-name'>Installed Fonts</div>
      <div>Regular</div>
      <div>Italics</div>
      <div>Bold</div>
      <div>Bold Italics</div>
    </div>

    <div className='font-list-items'>
      {fontList && fontList.map(font => (
        <div
          key={font.id}
          className={'font-list-item' + (font.id === match.params.id ? ' active' : '')}
          onClick={() => onHandleFontClick(font.id)}
          tabIndex='0'
        >
          <div>
            <span
              className='dashicons dashicons-trash'
              onClick={() => onHandleDeleteFont(font.id)}
              tabIndex='0'
            />
          </div>

          <span className='font-name'>{font.font_name}</span>

          <div>
            <span className={'dashicons dashicons-' + (font.regular ? 'yes' : 'no-alt')} />
          </div>
          <div>
            <span className={'dashicons dashicons-' + (font.italics ? 'yes' : 'no-alt')} />
          </div>
          <div>
            <span className={'dashicons dashicons-' + (font.bold ? 'yes' : 'no-alt')} />
          </div>
          <div>
            <span className={'dashicons dashicons-' + (font.bolditalics ? 'yes' : 'no-alt')} />
          </div>
        </div>
      ))}
    </div>
  </div>
)

export default FontList
