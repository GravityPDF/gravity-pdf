/* Dependencies */
import React from 'react'
import PropTypes from 'prop-types'

/**
 * Renders the Template Header Title
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @since 4.1
 */
const TemplateHeaderTitle = ({ header }) => (
  <h1 data-test='component-templateHeaderTitle'>{header}</h1>
)

/**
 * @since 4.1
 */
TemplateHeaderTitle.propTypes = {
  header: PropTypes.string
}

export default TemplateHeaderTitle
