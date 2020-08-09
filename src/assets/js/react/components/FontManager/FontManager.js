import React from 'react'
import Header from './Header'
import Body from './Body'

const FontManager = props => (
  <div>
    <div className='backdrop theme-backdrop' />
    <div className='container theme-wrap font-manager'>
      <Header />
      <Body {...props} />
    </div>
  </div>
)

export default FontManager
