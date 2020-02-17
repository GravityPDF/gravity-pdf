import React from 'react'
import PropTypes from 'prop-types'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (c) 2020, Blue Liquid Designs

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
 * Displays an individual result
 *
 * @param item (object)
 * @returns {*}
 *
 * @since 5.2
 */
const DisplayResultItem = ({ item }) => {
  return (
    <li className='resultExist'>
      <a href={item.link}>
        <div dangerouslySetInnerHTML={{ __html: item.title.rendered }} />
      </a>
      <div dangerouslySetInnerHTML={{ __html: item.excerpt.rendered }} />
    </li>
  )
}

/**
 *  @since 5.2
 */
DisplayResultItem.propTypes = {
  item: PropTypes.object
}

export default DisplayResultItem
