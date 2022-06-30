/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'
/* Components */
import ListSpacer from './CoreFontListSpacer'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2022, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * Displays the Console output for our Core Font Downloader
 *
 * @since 5.0
 */
export class CoreFontListResults extends Component {
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

    return (!lines.length)
      ? null
      : (
        <ul
          data-test='component-coreFont-container'
          className='gfpdf-core-font-list-results-container'
          aria-label={GFPDF.coreFontAriaLabel}
        >
          {lines.map((key) =>
            <li
              data-test={console[key].status}
              key={key}
              className={'gfpdf-core-font-status-' + console[key].status}
            >
              {console[key].message}
              {' '}
              {key === 'completed' && retry && <Retry history={this.props.history} retryText={this.props.retryText} />}
              {key === 'completed' && <ListSpacer />}
            </li>
          )}
        </ul>
        )
  }
}

/**
 * @since 5.0
 */
export class Retry extends Component {
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
  handleTriggerRetryFontDownload = (e) => {
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
      <a
        data-test='component-retry-link'
        href='#'
        onClick={this.handleTriggerRetryFontDownload}
        aria-live='polite'
        role='dialog'
      >
        {this.props.retryText}
      </a>
    )
  }
}
