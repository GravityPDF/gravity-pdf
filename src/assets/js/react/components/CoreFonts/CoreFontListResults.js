import React from 'react'
import PropTypes from 'prop-types'
import ListSpacer from './CoreFontListSpacer'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
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
 * Displays the Console output for our Core Font Downloader
 *
 * @since 5.0
 */
export default class CoreFontListResults extends React.Component {

  /**
   *
   * @since 5.0
   */
  static propTypes = {
    console: PropTypes.object,
    retry: PropTypes.array,
    history: PropTypes.object,
    retryText: PropTypes.string
  }

  /**
   * @returns {*}
   *
   * @since 5.0
   */
  render () {
    const console = this.props.console
    const lines = Object.keys(console).reverse()
    const retry = this.props.retry.length > 0

    return (!lines.length) ? null : (
      <div className="gfpdf-core-font-container">
        {lines.map((key) =>
          <div key={key} className={'gfpdf-core-font-status-' + console[key].status}>
            {console[key].message}
            {' '}
            {key === 'completed' && retry && <Retry history={this.props.history} retryText={this.props.retryText} />}
            {key === 'completed' && <ListSpacer />}
          </div>
        )}
      </div>
    )
  }
}

/**
 * @since 5.0
 */
class Retry extends React.Component {

  /**
   *
   * @since 5.0
   */
  static propTypes = {
    history: PropTypes.object,
    retryText: PropTypes.string
  }

  /**
   * Update the navigation history when the retry link is selected
   *
   * @param e
   *
   * @since 5.0
   */
  triggerRetryFontDownload = (e) => {
    e.preventDefault()
    this.props.history.replace('retryDownloadCoreFonts')
  }

  /**
   * Display a "retry" download link
   *
   * @returns {*}
   *
   * @since 5.0
   */
  render () {
    return (
      <a href="#" onClick={this.triggerRetryFontDownload}>{this.props.retryText}</a>
    )
  }
}
