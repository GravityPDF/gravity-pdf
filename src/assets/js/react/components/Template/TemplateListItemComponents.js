import React from 'react'
import PropTypes from 'prop-types'

/**
 * Contains stateless React components for our Template List Items
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
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
 * React Stateless Component
 *
 * Display the Template Details hover
 *
 * @since 4.1
 */
export const TemplateDetails = ({ label }) => (
  <span className='more-details'>{label}</span>
)

/**
 *
 * @since 4.1
 */
TemplateDetails.propTypes = {
  label: PropTypes.string
}

/**
 * React Stateless Component
 *
 * Display the template group
 *
 * @since 4.1
 */
export const Group = ({ group }) => (
  <p className='theme-author'>{group}</p>
)

/**
 *
 * @since 4.1
 */
Group.propTypes = {
  group: PropTypes.string
}
