/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * Display alert box UI
 *
 * @param msg: string
 *
 * @since 6.0
 */
export const Alert = ({ msg }) => (
  <div id='gf-admin-notices-wrapper'>
    <div
      className='notice notice-error gf-notice'
      dangerouslySetInnerHTML={{ __html: msg }}
    />
  </div>
)

/**
 * PropTypes
 *
 * @since 6.0
 */
Alert.propTypes = {
  msg: PropTypes.string.isRequired
}

export default Alert
