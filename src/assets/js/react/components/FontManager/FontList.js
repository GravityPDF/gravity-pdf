import React from 'react'
import PropTypes from 'prop-types'

export const FontList = ({ onHandleFontClick, onHandleDeleteFont, match, fontList }) => (
  <div className='font-list'>
    <div className='font-list-header'>
      <div className='installed-fonts'>Installed Fonts</div>
      <div className='variants'>Regular</div>
      <div className='variants'>Italics</div>
      <div className='variants'>Bold</div>
      <div className='variants'>Bold Italics</div>
    </div>

    <div className='font-list-items'>
      {fontList && fontList.map(font => (
        <div
          key={font.id}
          className={'font-list-item' + (font.id === match.params.id ? ' active' : '')}
        >
          <div className='column1'>
            <span
              className='dashicons dashicons-trash'
              onClick={() => onHandleDeleteFont(font.id)}
            />
          </div>
          <div
            className='column2'
            onClick={() => onHandleFontClick(font.id)}
          >
            <span className='font-name'>{font.font_name}</span>
            <div className='variants regular'>
              <span className={'dashicons dashicons-' + (font.regular ? 'yes' : 'no-alt')} />
            </div>
            <div className='variants italics'>
              <span className={'dashicons dashicons-' + (font.italics ? 'yes' : 'no-alt')} />
            </div>
            <div className='variants bold'>
              <span className={'dashicons dashicons-' + (font.bold ? 'yes' : 'no-alt')} />
            </div>
            <div className='variants bold-italics'>
              <span className={'dashicons dashicons-' + (font.bolditalics ? 'yes' : 'no-alt')} />
            </div>
          </div>
        </div>
      ))}
    </div>
  </div>
)

export default FontList
