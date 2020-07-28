import React, { Component } from 'react'
import FontListHeader from './FontListHeader'
import FontListItem from './FontListItem'

export class FontList extends Component {
  state = {
    items: [
      1,
      2,
      3,
      4,
      5,
      6,
      7,
      8,
      9,
      10,
      11,
      12,
      13,
      14,
      15,
      16,
      17,
      18,
      19,
      20
    ]
  }

  render () {
    return (
      <div className='font-list'>
        <FontListHeader />

        <div className='font-list-items'>
          {
            this.state.items.map(item => <FontListItem key={item} />)
          }
        </div>
      </div>
    )
  }
}

export default FontList
