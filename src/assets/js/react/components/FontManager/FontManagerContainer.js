import React from 'react'
import Header from './Header'
import Body from './Body'

const FontManagerContainer = () => (
  <div>
    <div className='backdrop theme-backdrop' />
    <div className='container theme-wrap font-manager'>
      <Header />
      <Body />
    </div>
  </div>
)

export default FontManagerContainer
