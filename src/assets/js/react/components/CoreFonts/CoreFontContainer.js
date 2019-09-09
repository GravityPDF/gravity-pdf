import React from 'react'
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
  retryDownload,
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
   * When new props are received we'll check if the fonts should be downloaded
   *
   * @param nextProps
   *
   * @since 5.0
   */
  componentWillReceiveProps (nextProps) {
    this.maybeStartDownload(nextProps.location)
    /* Set ajax/loading false if request download is finished */
    nextProps.requestDownload === 'finished' && (
      this.setState({ajax: false}), this.props.clearRequestRemainingData()
    )
  }

  /**
   * When the component is first mounted we'll check if the fonts should be downloaded
   *
   * @since 5.0
   */
  componentDidMount () {
    this.maybeStartDownload(this.props.location)
    /* Get the font names from GitHub we need to download */
    this.props.getFilesFromGitHub()
  }

  /**
   * If the Hash History matches our keys (and not already loading) start the download
   *
   * @param location
   *
   * @since 5.0
   */
  maybeStartDownload = (location) => {
    if (!this.state.ajax && location.pathname === '/downloadCoreFonts') {
      this.startDownloadFonts(this.props.fontList)
    }

    if (!this.state.ajax && location.pathname === '/retryDownloadCoreFonts' && this.props.retry.length > 0) {
      this.startDownloadFonts(this.props.retry)
      this.props.retryDownload(this.props.retry.length)
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
  startDownloadFonts = (files) => {
    if (files.length === 0) {
      return this.handleGithubApiError()
    }

    this.props.clearConsole()
    this.props.history.replace('')
    this.setState({ajax: true})
    this.props.clearRetryList()

    files.map((file) => this.props.downloadFontsApiCall(file))
    return files
  }

  /**
   * Add a GitHub API overall status to the console
   *
   * @param error
   *
   * @since 5.0
   */
  handleGithubApiError () {
    let error = this.props.githubError
    this.setState({ajax: false})
    this.props.addToConsole('completed', 'error', error)
    this.props.history.replace('')

    return error
  }

  /**
   * Trigger the font download by updating the Hash History
   *
   * @since 5.0
   */
  triggerFontDownload = () => {
    if (this.state.ajax === false) {
      this.props.history.replace('downloadCoreFonts')
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
    /* Set our Queue length value */
    let queueLength
    if (this.props.retry_download === true) queueLength = this.props.retryDownloadLength
    if (this.props.remainingDownload === 0 && this.props.retry.length === 0 && this.props.retry_download === false) {
      queueLength = this.props.fontList.length
    } else {
      queueLength = this.props.retryDownloadLength
    }
    if (!this.props.console && this.props.retry_download === false) queueLength = this.props.fontList.length
    if (Object.keys(this.props.console).length > 5 && this.props.retry_download === false) queueLength = this.props.remainingDownload

    return (
      <div>
        <Button className={this.props.buttonClassName} callback={this.triggerFontDownload}
                text={this.props.buttonText}/>

        {this.state.ajax && <Spinner/>}
        {this.state.ajax && <Counter text={this.props.counterText} queue={queueLength}/>}

        <CoreFontListResults
          history={this.props.history}
          console={this.props.console}
          retry={this.props.retry}
          retryText={this.props.retryText}/>
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
 *  console: Object,
 *  retry: (*|number|Array),
 *  remainingDownload: Integer,
 *  requestDownload: String,
 *  retry_download: Boolean,
 *  retryDownloadLength: null
 * }}
 *
 * @since 5.0
 */
const mapStateToProps = (state) => {
  return {
    fontList: state.coreFonts.fontList,
    console: state.coreFonts.console,
    retry: state.coreFonts.retry,
    remainingDownload: state.coreFonts.remainingDownload,
    requestDownload: state.coreFonts.requestDownload,
    retry_download: state.coreFonts.retry_download,
    retryDownloadLength: state.coreFonts.retryDownloadLength
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
 *  retryDownload
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
  retryDownload,
  clearConsole
})(CoreFontContainer)
