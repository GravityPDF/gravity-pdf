import React, { Component } from 'react'
import SearchBox from './SearchBox'
import FontList from './FontList'
import AddFontName from './AddFontName'

export class Body extends Component {
  render () {
    return (
      <div
        id='gfpdf-font-manager-container'
        className='wp-clearfix theme-browser font-manager-body'
      >
        <div className='two-columns'>
          <SearchBox />
          <FontList />
        </div>

        <div className='two-columns'>
          <AddFontName />
        </div>
      </div>
    )
  }
}

export default Body
