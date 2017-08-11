import PropTypes from 'prop-types';
import React from 'react'

/**
 * Display the Template Screenshot for the List Items
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

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
 * @since 4.1
 */
const TemplateScreenshot = ({ image }) => {
  const className = (image) ? 'theme-screenshot' : 'theme-screenshot blank'

  return (
    <div className={className}>
      {image ? <img src={image} alt=""/> : null}
    </div>
  )
}

TemplateScreenshot.propTypes = {
  image: PropTypes.string
}

export default TemplateScreenshot