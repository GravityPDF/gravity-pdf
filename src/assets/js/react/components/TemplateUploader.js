import PropTypes from 'prop-types';
import React from 'react'
import { connect } from 'react-redux'
import request from 'superagent'
import { fromJS } from 'immutable'

import { addTemplate, updateTemplateParam } from '../actions/templates'
import Dropzone from './Dropzone'
import ShowMessage from './ShowMessage'

/**
 * Handles the uploading of new PDF templates to the server
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
export class TemplateUploader extends React.Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    ajaxUrl: PropTypes.string,
    ajaxNonce: PropTypes.string,

    genericUploadErrorText: PropTypes.string,
    addTemplateText: PropTypes.string,
    filenameErrorText: PropTypes.string,
    filesizeErrorText: PropTypes.string,
    installSuccessText: PropTypes.string,
    installUpdatedText: PropTypes.string,
    templateSuccessfullyInstalledUpdated: PropTypes.string,
    templateInstallInstructions: PropTypes.string,

    addNewTemplate: PropTypes.func,
    updateTemplateParam: PropTypes.func,
    templates: PropTypes.object
  };

  /**
   * Setup internal component state that doesn't need to be in Redux
   *
   * @returns {{ajax: boolean, error: string, message: string}}
   *
   * @since 4.1
   */
  state = {
    ajax: false,
    error: '',
    message: ''
  };

  /**
   * Manages the template file upload
   *
   * @param {array} acceptedFiles The array of uploaded files we should send to the server
   *
   * @since 4.1
   */
  onDrop = (acceptedFiles) => {
    /* Handle file upload and pass in an nonce!!! */
    if (acceptedFiles instanceof Array && acceptedFiles.length > 0) {

      acceptedFiles.forEach((file) => {
        const filename = file.name

        /* Do validation */
        if (!this.checkFilename(filename) || !this.checkFilesize(file.size)) {
          return
        }

        /* Add our loader */
        this.setState({
          ajax: true,
          error: '',
          message: '',
        })

        /* POST the PDF template to our endpoint for processing */
        request
          .post(this.props.ajaxUrl)
          .field('action', 'gfpdf_upload_template')
          .field('nonce', this.props.ajaxNonce)
          .attach('template', file, filename)
          .then(this.ajaxSuccess, this.ajaxFailed)
      })

    }
  };

  /**
   * Checks if the uploaded file has a .zip extension
   * We do this instead of mime type checking as it doesn't work in all browsers
   *
   * @param {string} name
   *
   * @returns {boolean}
   *
   * @since 4.1
   */
  checkFilename = (name) => {
    if (name.substr(name.length - 4) !== '.zip') {

      /* Tell use about incorrect file type */
      this.setState({
        error: this.props.filenameErrorText
      })

      return false
    }

    return true
  };

  /**
   * Checks if the file size is larger than 5MB
   *
   * @param {int} size File size in bytes
   *
   * @returns {boolean}
   *
   * @since 4.1
   */
  checkFilesize = (size) => {
    /* Check the file is no larger than 5MB (convert from bytes to KB) */
    if (size / 1024 > 5120) {
      /* Tell use about incorrect file type */
      this.setState({
        error: this.props.filesizeErrorText
      })

      return false
    }

    return true
  };

  /**
   * Update our Redux store with the new PDF template details
   * If our upload AJAX call to the server passed this function gets fired
   *
   * @param {Object} response
   *
   * @since 4.1
   */
  ajaxSuccess = (response) => {

    /* Update our Redux Store with the new template(s) */
    response.body.templates.forEach((template) => {

      /* Check if template already in the list before adding to our store */
      const matched = this.props.templates.find((item) => {
        return (item.get('id') === template.id)
      })

      if (matched === undefined) {
        template.new = true //ensure new templates go to end of list
        template.message = this.props.installSuccessText
        this.props.addNewTemplate(fromJS(template))
      } else {
        this.props.updateTemplateParam(template.id, 'message', this.props.installUpdatedText)
      }
    })

    /* Mark as success and stop AJAX spinner */
    this.setState({
      ajax: false,
      message: this.props.templateSuccessfullyInstalledUpdated
    })
  };

  /**
   * Show any errors to the user when AJAX request fails for any reason
   *
   * @param {Object} error
   *
   * @since 4.1
   */
  ajaxFailed = (error) => {
    /* Let the user know there was a problem with the upload */
    this.setState({
      error: (error.response.body && error.response.body.error !== undefined) ? error.response.body.error : this.props.genericUploadErrorText,
      ajax: false
    })
  };

  /**
   * Remove message from state once the timeout has finished
   *
   * @since 4.1
   */
  removeMessage = () => {
    this.setState( {
      message: ''
    })
  };

  /**
   * Prevent normal behaviour when this event fires
   *
   * @param {Object} e Event
   *
   * @since 4.1
   */
  openDropzone = (e) => {
    e.preventDefault()
  };

  /**
   * @since 4.1
   */
  render() {
    return (
      <Dropzone
        onDrop={this.onDrop}
        maxSize={10240000}
        multiple={true}
        className="theme add-new-theme gfpdf-dropzone">
        <a href="#" onClick={this.openDropzone} className={this.state.ajax ? 'doing-ajax' : ''}>
          <div className="theme-screenshot"><span /></div>

          {this.state.error !== '' ? <ShowMessage text={this.state.error} error={true}/> : null}
          {this.state.message !== '' ? <ShowMessage text={this.state.message} dismissable={true} dismissableCallback={this.removeMessage} /> : null}

          <h2 className="theme-name">{this.props.addTemplateText}</h2>
        </a>
        <div className="gfpdf-template-install-instructions">{this.props.templateInstallInstructions}</div>
      </Dropzone>
    )
  }
}

/**
 * Map state to props
 *
 * @param {Object} state The current Redux State
 *
 * @returns {{templates}}
 *
 * @since 4.1
 */
const mapStateToProps = (state) => {
  return {
    templates: state.template.list
  }
}

/**
 * Map actions to props
 *
 * @param {func} dispatch Redux dispatcher
 *
 * @returns {{addNewTemplate: (function(template=Immutable Map)), updateTemplateParam: (function(id=string, name=string, value=*))}}
 *
 * @since 4.1
 */
const mapDispatchToProps = (dispatch) => {
  return {
    addNewTemplate: (template) => {
      dispatch(addTemplate(template))
    },

    updateTemplateParam: (id, name, value) => {
      dispatch(updateTemplateParam(id, name, value))
    }
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default connect(mapStateToProps, mapDispatchToProps)(TemplateUploader)