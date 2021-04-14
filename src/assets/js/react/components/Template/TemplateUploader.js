import PropTypes from 'prop-types'
import React from 'react'
import { connect } from 'react-redux'
import {
  addTemplate,
  updateTemplateParam,
  postTemplateUploadProcessing,
  clearTemplateUploadProcessing
} from '../../actions/templates'
import classNames from 'classnames'
import Dropzone from 'react-dropzone'
import ShowMessage from '../ShowMessage'

/**
 * Handles the uploading of new PDF templates to the server
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
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
    postTemplateUploadProcessing: PropTypes.func,
    clearTemplateUploadProcessing: PropTypes.func,
    templates: PropTypes.array,
    templateUploadProcessingSuccess: PropTypes.object,
    templateUploadProcessingError: PropTypes.object
  }

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
  }

  /**
   * If component did update, fires appropriate function based on Redux store data
   *
   * @param {Object} prevProps
   *
   * @since 4.1
   */
  componentDidUpdate (prevProps) {
    const { templateUploadProcessingSuccess, templateUploadProcessingError } = this.props

    if (
      prevProps.templateUploadProcessingSuccess !== templateUploadProcessingSuccess &&
      Object.keys(templateUploadProcessingSuccess).length > 0
    ) {
      this.ajaxSuccess(templateUploadProcessingSuccess)
    }

    if (
      prevProps.templateUploadProcessingError !== templateUploadProcessingError &&
      Object.keys(templateUploadProcessingError).length > 0
    ) {
      this.ajaxFailed(templateUploadProcessingError)
    }
  }

  /**
   * Manages the template file upload
   *
   * @param {array} acceptedFiles The array of uploaded files we should send to the server
   *
   * @since 4.1
   */
  handleOndrop = (acceptedFiles) => {
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
          message: ''
        })

        /* POST the PDF template to our endpoint for processing */
        this.props.postTemplateUploadProcessing(file, filename)
      })
    }
  }

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
  }

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
    /* Check the file is no larger than 10MB (convert from bytes to KB) */
    if (size / 1024 > 10240) {
      /* Tell use about incorrect file type */
      this.setState({
        error: this.props.filesizeErrorText
      })

      return false
    }

    return true
  }

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
        return (item.id === template.id)
      })

      if (matched === undefined) {
        template.new = true // ensure new templates go to end of list
        template.message = this.props.installSuccessText
        this.props.addNewTemplate(template)
      } else {
        this.props.updateTemplateParam(template.id, 'message', this.props.installUpdatedText)
      }
    })

    /* Mark as success and stop AJAX spinner */
    this.setState({
      ajax: false,
      message: this.props.templateSuccessfullyInstalledUpdated
    })

    /* Clean/Reset our Redux Store state for templateUploadProcessing */
    this.props.clearTemplateUploadProcessing()
  }

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

    /* Clean/Reset our Redux Store state for templateUploadProcessing */
    this.props.clearTemplateUploadProcessing()
  }

  /**
   * Remove message from state once the timeout has finished
   *
   * @since 4.1
   */
  removeMessage = () => {
    this.setState({
      message: ''
    })
  }

  /**
   * @since 4.1
   */
  render () {
    return (
      <div
        data-test='component-templateUploader'
        className='theme add-new-theme gfpdf-dropzone'
      >
        <Dropzone
          data-test='component-dropzone'
          onDrop={this.handleOndrop}
        >
          {({ getRootProps, getInputProps, isDragActive }) => {
            return (
              <div
                {...getRootProps()}
                className={classNames('dropzone', { 'dropzone--isActive': isDragActive })}
              >
                <input {...getInputProps()} />
                <a href='#/template' className={this.state.ajax ? 'doing-ajax' : ''} aria-labelledby='gfpdf-template-install-instructions'>

                  <div className='theme-screenshot'><span /></div>

                  {this.state.error !== '' ? (
                    <ShowMessage
                      data-test='component-stateError-showMessage'
                      text={this.state.error}
                      error
                    />
                  ) : null}
                  {this.state.message !== '' ? (
                    <ShowMessage
                      data-test='component-stateMessage-showMessage'
                      text={this.state.message}
                      dismissable
                      dismissableCallback={this.removeMessage}
                    />
                  ) : null}

                  <h2 className='theme-name'>{this.props.addTemplateText}</h2>
                </a>
                <div className='gfpdf-template-install-instructions' id='gfpdf-template-install-instructions'>
                  {this.props.templateInstallInstructions}
                </div>
              </div>
            )
          }}
        </Dropzone>
      </div>
    )
  }
}

/**
 * Map Redux state to props
 *
 * @param state
 * @returns {{templates: Array, templateUploadProcessingSuccess: Object, templateUploadProcessingError: Object}}
 *
 * @since 5.2
 */
const mapStateToProps = (state) => {
  return {
    templates: state.template.list,
    templateUploadProcessingSuccess: state.template.templateUploadProcessingSuccess,
    templateUploadProcessingError: state.template.templateUploadProcessingError
  }
}

/**
 * Map actions to props
 *
 * @param {func} dispatch Redux dispatcher
 *
 * @returns {{addNewTemplate: (function(template)), updateTemplateParam: (function(id=string, name=string, value=*)), postTemplateUploadProcessing: (function(file=object, filename=string)), clearTemplateUploadProcessing: (function())}}
 *
 * @since 4.1
 */
export const mapDispatchToProps = (dispatch) => {
  return {
    addNewTemplate: (template) => {
      dispatch(addTemplate(template))
    },

    updateTemplateParam: (id, name, value) => {
      dispatch(updateTemplateParam(id, name, value))
    },

    postTemplateUploadProcessing: (file, filename) => {
      dispatch(postTemplateUploadProcessing(file, filename))
    },

    clearTemplateUploadProcessing: () => {
      dispatch(clearTemplateUploadProcessing())
    }
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default connect(mapStateToProps, mapDispatchToProps)(TemplateUploader)
