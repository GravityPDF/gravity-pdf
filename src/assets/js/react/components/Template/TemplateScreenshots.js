/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'

/**
 * Display the Template Screenshot for the individual templates (uses different markup - out of our control)
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Stateless Component
 *
 * @since 4.1
 */
const TemplateScreenshots = ({ image }) => {
  const className = (image) ? 'screenshot' : 'screenshot blank'

  return (
    <div data-test='component-templateScreenshots' className='theme-screenshots'>
      <div className={className}>
        {image ? <img src={image} alt='' /> : null}
      </div>
    </div>
  )
}

TemplateScreenshots.propTypes = {
  image: PropTypes.string
}

export default TemplateScreenshots
