import PropTypes from 'prop-types'
import React from 'react'

/**
 * Renders the Template Header Title
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @since 4.1
 */
const TemplateHeaderTitle = ({header}) => (
  <h1>{header}</h1>
)

/**
 * @since 4.1
 */
TemplateHeaderTitle.propTypes = {
  header: PropTypes.string
}

export default TemplateHeaderTitle