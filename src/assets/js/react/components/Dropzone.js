import React from 'react'
import ReactDropzone from 'react-dropzone'

/**
 * Our Drag and Drop File upload Component which is a wrapper
 * for react-dropzone
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2016, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (C) 2016, Blue Liquid Designs

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
 * React Component
 *
 * @since 4.1
 */
const Dropzone = React.createClass({

  /**
   * @since 4.1
   */
  propTypes: {
    children: React.PropTypes.node.isRequired,
    onDrop: React.PropTypes.func.isRequired,
    multiple: React.PropTypes.bool,
    className: React.PropTypes.string,
    activeClassName: React.PropTypes.string
  },

  /**
   * @since 4.1
   */
  getDefaultProps() {
    return {
      multiple: false,
      maxSize: Infinity,
      className: 'gfpdf-dropzone',
      activeClassName: 'gfpdf-dropzone-active'
    }
  },

  /**
   * @since 4.1
   */
  render() {
    return (
      <ReactDropzone
        onDrop={this.props.onDrop}
        multiple={this.props.multiple}
        disablePreview={true}
        className={this.props.className}
        activeClassName={this.props.activeClassName}>
        {this.props.children}
      </ReactDropzone>
    )
  },
})

export default Dropzone