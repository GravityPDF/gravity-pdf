import React, { Component } from 'react'
import FontFiles from './FontFiles'

export class AddFontName extends Component {
  render () {
    return (
      <div className='add-font'>
        <h1>Add Font</h1>
        <p>Install new fonts for use in your PDF documents.</p>

        <h2>Font Name <span className='required'>*</span></h2>
        <p>The font name must be unique and only contain alphanumeric characters.</p>
        <input type='text' />

        <FontFiles />
      </div>
    )
  }
}

export default AddFontName
