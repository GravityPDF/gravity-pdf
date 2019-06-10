import React from 'react'
import PropTypes from 'prop-types'
import request from 'superagent'
import Queue from 'promise-queue'
import promiseReflect from '../../utilities/promiseReflect'
import { connect } from 'react-redux'

import CoreFontListResults from './CoreFontListResults'
import Button from './CoreFontButton'
import Counter from './CoreFontCounter'
import Spinner from '../Spinner'
import { clearRetryList, addToRetryList, addToConsole, clearConsole } from '../../actions/coreFonts'

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
    retry: PropTypes.array,
    clearConsole: PropTypes.func,
    clearRetryList: PropTypes.func,
    listUrl: PropTypes.string,
    error: PropTypes.string,
    success: PropTypes.string,
    addToConsole: PropTypes.func,
    history: PropTypes.object,
    githubError: PropTypes.string,
    itemPending: PropTypes.string,
    itemSuccess: PropTypes.string,
    itemError: PropTypes.string,
    addToRetryList: PropTypes.func,
    buttonClassName: PropTypes.string,
    buttonText: PropTypes.string,
    counterText: PropTypes.string,
    console: PropTypes.object,
    retryText: PropTypes.string
  }

  /**
   * Switches to show loaders
   *
   * @type {{ajax: boolean, queueLoaded: boolean}}
   *
   * @since 5.0
   */
  constructor (props) {
    super(props)
    this.state = {
      ajax: false,
      queueLoaded: false
    }
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
  }

  /**
   * When the component is first mounted we'll check if the fonts should be downloaded
   *
   * @since 5.0
   */
  componentDidMount () {
    this.maybeStartDownload(this.props.location)
  }

  /**
   * If the Hash History matches our keys (and not already loading) start the download
   *
   * @param location
   *
   * @since 5.0
   */
  maybeStartDownload (location) {
    if (!this.state.ajax && location.pathname === '/downloadCoreFonts') {
      this.startDownloadFonts()
    }

    if (!this.state.ajax && location.pathname === '/retryDownloadCoreFonts' && this.props.retry.length > 0) {
      this.startDownloadFonts(this.props.retry)
    }
  }

  /**
   * Call our server to download the fonts in batches of 5
   *
   * @param array files The font files to download (usually passed in from the 'retry' prop)
   *
   * @returns {Promise.<void>}
   *
   * @since 5.0
   */
  startDownloadFonts = async (files = []) => {
    try {
      this.setState({ ajax: true })
      this.props.clearConsole()
      this.props.clearRetryList()

      /* If not retrying, get the font list from our GitHub repo */
      if (files.length === 0) {
        files = await this.getFilesFromGitHub()
      }

      const tasks = []
      this.queue = new Queue(5, Infinity)

      files.map(
        (file) => tasks.push(this.queue.add(() => this.downloadFontsApiCall(file)))
      )

      Promise.all(tasks.map(promiseReflect)).then(this.showDownloadCompletedStatus)

      this.setState({ queueLoaded: true })
    } catch (error) {
      this.handleGithubApiError(error)
    }
  }

  /**
   * Get our Promise Queue length
   *
   * @returns {number}
   *
   * @since 5.0
   */
  getQueueLength () {
    return (this.queue !== undefined) ? this.queue.getQueueLength() + this.queue.getPendingLength() : 0
  }

  /**
   * Get the font names from GitHub we need to download
   *
   * @returns {Promise.<Array>}
   *
   * @since 5.0
   */
  async getFilesFromGitHub () {
    const req = await request
      .get(this.props.listUrl)
      .accept('application/vnd.github.v3+json')
      .type('json')

    let files = []

    req.body.map(
      (file) => files.push(file.name)
    )

    return files
  }

  /**
   * Show the overall status in the console once all the fonts have been downloaded (or tried to download)
   *
   * @since 5.0
   */
  showDownloadCompletedStatus = () => {
    const errors = this.props.retry.length
    const status = errors ? 'error' : 'success'
    const message = errors ? this.props.error.replace('%s', errors) : this.props.success

    this.props.addToConsole('completed', status, message)
    this.setState({ ajax: false, queueLoaded: false })
    this.props.history.replace('')
  }

  /**
   * Add a GitHub API overall status to the console
   *
   * @param error
   *
   * @since 5.0
   */
  handleGithubApiError () {
    this.setState({ ajax: false, queueLoaded: false })
    this.props.addToConsole('completed', 'error', this.props.githubError)
    this.props.history.replace('')
  }

  /**
   * Tell our backend to download and save the font
   *
   * @param file
   * @returns {Promise.<void>}
   *
   * @since 5.0
   */
  downloadFontsApiCall = async (file) => {
    this.addFontPendingMessage(file)

    /* Do AJAX call */
    try {
      const req = await request
        .post(GFPDF.ajaxUrl)
        .field('action', 'gfpdf_save_core_font')
        .field('nonce', GFPDF.ajaxNonce)
        .field('font_name', file)

      /* API returns `true` on success and `false` on failure */
      if (!req.body) {
        throw true
      }

      this.addFontSuccessMessage(file)
    } catch (e) {
      this.addFontErrorMessage(file)
    }
  }

  /**
   * Add pending message to console
   *
   * @param string name The Font Name
   *
   * @since 5.0
   */
  addFontPendingMessage (name) {
    this.props.addToConsole(name, 'pending', this.props.itemPending.replace('%s', name))
  }

  /**
   * Add success message to console
   *
   * @param string name The Font Name
   *
   * @since 5.0
   */
  addFontSuccessMessage (name) {
    this.props.addToConsole(name, 'success', this.props.itemSuccess.replace('%s', name))
  }

  /**
   * Add error message to console
   *
   * @param string name The Font Name
   *
   * @since 5.0
   */
  addFontErrorMessage (name) {
    this.props.addToConsole(name, 'error', this.props.itemError.replace('%s', name))
    this.props.addToRetryList(name)
  }

  /**
   * Trigger the font download by updating the Hash History
   *
   * @since 5.0
   */
  triggerFontDownload = () => {
    this.props.history.replace('downloadCoreFonts')
  }

  /**
   * Renders our Core Font downloader UI
   *
   * @returns {XML}
   *
   * @since 5.0
   */
  render () {
    return (
      <div>
        <Button className={this.props.buttonClassName} callback={this.triggerFontDownload}
                text={this.props.buttonText} />

        {this.state.ajax && <Spinner />}
        {this.state.queueLoaded && <Counter text={this.props.counterText} queue={this.getQueueLength()} />}

        <CoreFontListResults
          history={this.props.history}
          console={this.props.console}
          retry={this.props.retry}
          retryText={this.props.retryText} />
      </div>
    )
  }
}

/**
 * Map Redux state to props
 *
 * @param state
 * @returns {{console, retry: (*|number|Array)}}
 *
 * @since 5.0
 */
const mapStateToProps = (state) => {
  return {
    console: state.coreFonts.console,
    retry: state.coreFonts.retry
  }
}

/**
 * Map Redux actions to props
 *
 * @param dispatch
 * @returns {{addToConsole: (function(*=, *=, *=)), clearConsole: (function()), addToRetryList: (function(*=)), clearRetryList: (function())}}
 *
 * @since 5.0
 */
const mapDispatchToProps = (dispatch) => {
  return {
    addToConsole: (key, status, message) => {
      dispatch(addToConsole(key, status, message))
    },

    clearConsole: () => {
      dispatch(clearConsole())
    },

    addToRetryList: (name) => {
      dispatch(addToRetryList(name))
    },

    clearRetryList: () => {
      dispatch(clearRetryList())
    }
  }
}

export default connect(mapStateToProps, mapDispatchToProps)(CoreFontContainer)
