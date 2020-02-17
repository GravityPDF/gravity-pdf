import PropTypes from 'prop-types'
import React from 'react'

/**
 * Display the Template Screenshot for the List Items
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Stateless Component
 *
 * @since 4.1
 */
const TemplateScreenshot = ({ image }) => {
  const className = (image) ? 'theme-screenshot' : 'theme-screenshot blank'

  return (
    <div className={className}>
      {image ? <img src={image} alt='' /> : null}
    </div>
  )
}

TemplateScreenshot.propTypes = {
  image: PropTypes.string
}

export default TemplateScreenshot
