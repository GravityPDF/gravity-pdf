import React from 'react'
import Spinner from '../Spinner'
import DisplayResultItem from './DisplayResultItem'
import DisplayResultEmpty from './DisplayResultEmpty'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Found
 */

/**
 * Displays result based on Search Input value
 *
 * @param searchInput (string)
 * @param loading (boolean)
 * @param helpResult (object)
 *
 * @since 5.2
 */
const DisplayResultContainer = ({searchInput, loading, helpResult}) => {

  if (searchInput.length <= 3) {
    return null
  }

  const displayLoading = loading ? <div style={{float: 'right'}}><Spinner/></div> : null
  const showEmptyResults = helpResult.length === 0 && !loading
  const searchResults = helpResult.map((item, index) => (
    <DisplayResultItem item={item} key={index}/>
  ))

  return (
    <div id='search-results'>
      <div id='dashboard_primary' className='metabox-holder'>
        <div id='documentation-api' className='postbox' style={{display: 'block'}}>
          <h3 className='hndle'>
            <span>{GFPDF.searchResultHeadingText}</span>
            {displayLoading}
          </h3>

          <div className='inside rss-widget' style={{display: 'block'}}>
            <ul className='searchParseHTML'>
              {searchResults}
              {showEmptyResults && <DisplayResultEmpty/>}
            </ul>
          </div>
        </div>
      </div>
    </div>
  )
}

export default DisplayResultContainer