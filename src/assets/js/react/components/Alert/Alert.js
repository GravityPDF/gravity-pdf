import React from 'react'
import PropTypes from 'prop-types'

export const Alert = ({ msg }) => (
  <div id='gf-admin-notices-wrapper'>
    <div
      className='notice notice-error gf-notice'
      dangerouslySetInnerHTML={{ __html: msg }}
    />
  </div>
)

Alert.propTypes = {
  msg: PropTypes.string.isRequired
}

export default Alert
