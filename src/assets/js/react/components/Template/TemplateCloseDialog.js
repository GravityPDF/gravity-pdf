import PropTypes from 'prop-types';
import React from 'react'
import { withRouter } from 'react-router-dom'

/**
 * Renders our close dialog element
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
 * React Component
 *
 * @since 4.1
 */
export class TemplateCloseDialog extends React.Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    closeRoute: PropTypes.string
  };

  /**
   * Assign keydown listener to document on mount
   *
   * @since 4.1
   */
  componentDidMount() {
    document.addEventListener('keydown', this.handleKeyPress, false)
  }

  /**
   * Remove keydown listener to document on mount
   *
   * @since 4.1
   */
  componentWillUnmount() {
    document.removeEventListener('keydown', this.handleKeyPress, false)
  }

  /**
   * Check if Escape key pressed and current event target isn't our search box,
   * or the search box is blank already
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  handleKeyPress = (e) => {
    /* Escape Key */
    if (e.keyCode === 27 && (e.target.className !== 'wp-filter-search' || e.target.value === '')) {
      this.closeDialog()
    }
  };

  /**
   * @since 4.1
   */
  closeDialog = () => {
    /* trigger router */
    this.props.history.push(this.props.closeRoute || '/')
  };

  /**
   * @since 4.1
   */
  render() {
    return (
      <button
        className="close dashicons dashicons-no"
        tabIndex="142"
        onClick={this.closeDialog}
        onKeyDown={this.handleKeyPress}
        aria-label="close">
        <span className="screen-reader-text">Close dialog</span>
      </button>
    )
  }
}

export default withRouter(TemplateCloseDialog)