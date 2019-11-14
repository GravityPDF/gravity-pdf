import React from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import CoreFontListResults from './CoreFontListResults'
import Button from './CoreFontButton'
import Counter from './CoreFontCounter'
import Spinner from '../Spinner'
import {
  clearRetryList,
  addToConsole,
  getFilesFromGitHub,
  downloadFontsApiCall,
  clearRequestRemainingData,
  clearConsole
} from '../../actions/coreFonts'

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
 * Handles the grunt work for our Core Font downloader (API calls, display, state ect)
 *
 * @since 5.0
 */
export class CoreFontContainer extends React.Component {

  /**
   *
   * @since 5.0
   */
  static propTypes = {
    location: PropTypes.object,
    requestDownload: PropTypes.string,
    clearRequestRemainingData: PropTypes.func,
    getFilesFromGitHub: PropTypes.func,
    fontList: PropTypes.array,
    getFilesFromGitHubFailed: PropTypes.string,
    retry: PropTypes.array,
    clearConsole: PropTypes.func,
    history: PropTypes.object,
    clearRetryList: PropTypes.func,
    downloadFontsApiCall: PropTypes.func,
    githubError: PropTypes.string,
    addToConsole: PropTypes.func,
    console: PropTypes.object,
    buttonClassName: PropTypes.string,
    buttonText: PropTypes.string,
    counterText: PropTypes.string,
    retryText: PropTypes.string,
    queue: PropTypes.number
  }

  /**
   * Switches to show loaders
   *
   * @type {{ajax: boolean}}
   *
   * @since 5.0
   */
  state = {
    ajax: false
  }

  /**
   * When new props are received we'll check if the font list should be loaded
   *
   * @param nextProps
   *
   * @since 5.0
   */
  componentWillReceiveProps (nextProps) {
    /* Load current hash history location & font list */
    if (nextProps.fontList.length > 0 && nextProps.location.pathname === '/downloadCoreFonts') {
      this.maybeStartDownload(nextProps.location, nextProps.fontList)
    }

    /* Check for /downloadCoreFonts redirect URL and run the installer */
    if (nextProps.location.pathname === '/downloadCoreFonts') {
      this.triggerFontDownload()
    }

    /* Load current hash history location & retry font list */
    if (nextProps.location.pathname === '/retryDownloadCoreFonts') {
      this.maybeStartDownload(nextProps.location, nextProps.retry)
    }

    /* Load error if something went wrong */
    if (nextProps.getFilesFromGitHubFailed !== '') {
      this.maybeStartDownload(nextProps.location, nextProps.fontList, nextProps.getFilesFromGitHubFailed)
    }

    /* Set ajax/loading false if request download is finished */
    nextProps.requestDownload === 'finished' && (
      this.setState({ ajax: false }),
        this.props.clearRequestRemainingData(),
        this.props.history.replace('')
    )
  }

  /**
   * Check for /downloadCoreFonts redirect URL and run the installer
   *
   * @since 5.0
   */
  componentDidMount () {
    if (this.props.location.pathname === '/downloadCoreFonts') {
      this.triggerFontDownload()
    }
  }

  /**
   * If the Hash History matches our keys (and not already loading) start the download
   *
   * @param location
   * @param fontList
   * @param error
   *
   * @since 5.0
   */
  maybeStartDownload = (location, fontList, error = null) => {
    if (location.pathname === '/downloadCoreFonts') {
      this.startDownloadFonts(fontList, error)
    }

    if (location.pathname === '/retryDownloadCoreFonts') {
      this.startDownloadFonts(fontList, error)
    }
  }

  /**
   * Call our server to download the fonts in batches of 5
   *
   * @param array files The font files to download (usually passed in from the 'retry' prop)
   *
   * @returns {files: Array}
   *
   * @since 5.0
   */
  startDownloadFonts = (files, error) => {
    if (files.length === 0) {
      return this.handleGithubApiError(error)
    }

    this.props.clearConsole()
    this.props.clearRetryList()

    /* Clean Hash History*/
    this.props.history.replace('')

    setTimeout(() => files.map((file) => this.props.downloadFontsApiCall(file)), 300)

    return files
  }

  /**
   * Add a GitHub API overall status to the console
   *
   * @param error
   *
   * @since 5.0
   */
  handleGithubApiError = (error) => {
    this.setState({ ajax: false })
    this.props.addToConsole('completed', 'error', error)
    this.props.history.replace('')

    return error
  }

  /**
   * Request GitHub for font names & trigger the font download by updating the Hash History
   *
   * @since 5.0
   */
  triggerFontDownload = () => {
    if (this.state.ajax === false) {
      /* Get the font names from GitHub we need to download */
      this.setState({ ajax: true }, () => {
        this.props.getFilesFromGitHub()

        /* Update hash history */
        this.props.history.replace('downloadCoreFonts')
      })
    }
  }

  /**
   * Renders our Core Font downloader UI
   *
   * @returns {XML}
   *
   * @since 5.0
   */
  render () {
    const {
      ajax
    } = this.state

    const {
      buttonClassName,
      buttonText,
      counterText,
      queue,
      history,
      console: consoleList,
      retry,
      retryText
    } = this.props

    return (
      <div>
        <Button
          className={buttonClassName}
          callback={this.triggerFontDownload}
          text={buttonText}
          disable={ajax}
        />
        {ajax && <Spinner />}
        {ajax && queue !== 0 && <Counter text={counterText} queue={queue} />}
        <CoreFontListResults
          history={history}
          console={consoleList}
          retry={retry}
          retryText={retryText}
        />
      </div>
    )
  }
}

/**
 * Map Redux state to props
 *
 * @param state
 *
 * @returns {{
 *  fontList: Array,
 *  getFilesFromGitHubFailed: String,
 *  console: Object,
 *  retry: (*|number|Array),
 *  requestDownload: String,
 *  queue: boolean
 * }}
 *
 * @since 5.0
 */
const mapStateToProps = state => {
  return {
    fontList: state.coreFonts.fontList,
    getFilesFromGitHubFailed: state.coreFonts.getFilesFromGitHubFailed,
    console: state.coreFonts.console,
    retry: state.coreFonts.retry,
    requestDownload: state.coreFonts.requestDownload,
    queue: state.coreFonts.downloadCounter
  }
}

/**
 * Map Redux actions to props
 *
 * @returns {{
 *  addToConsole,
 *  clearRetryList,
 *  getFilesFromGitHub,
 *  downloadFontsApiCall,
 *  clearRequestRemainingData,
 *  clearConsole
 * }}
 *
 * @since 5.0
 */

export default connect(mapStateToProps, {
  addToConsole,
  clearRetryList,
  getFilesFromGitHub,
  downloadFontsApiCall,
  clearRequestRemainingData,
  clearConsole
})(CoreFontContainer)
