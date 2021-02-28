import React from 'react'
import PropTypes from 'prop-types'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
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
