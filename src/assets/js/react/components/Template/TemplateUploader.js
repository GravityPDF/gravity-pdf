/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import Dropzone from 'react-dropzone'
/* Components */
import ShowMessage from '../ShowMessage'
import { TemplateUploaderContext } from './TemplateUploaderContext'
/* Redux actions */
import {
  addTemplate,
  updateTemplateParam,
  postTemplateUploadProcessing,
  clearTemplateUploadProcessing
} from '../../actions/templates'

/**
 * Handles the uploading of new PDF templates to the server
 *
 * Wraps the whole Template Manager so a zip can be dropped anywhere, and shares the file picker via context
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @since 4.1
 */
export class TemplateUploader extends Component {
  /**
   * @since 4.1
   */
  static propTypes = {
    children: PropTypes.node,
    genericUploadErrorText: PropTypes.string,
    filenameErrorText: PropTypes.string,
    filesizeErrorText: PropTypes.string,
    installSuccessText: PropTypes.string,
    installUpdatedText: PropTypes.string,
    templateSuccessfullyInstalledUpdated: PropTypes.string,
    dropzoneText: PropTypes.string,
    uploadInProgressText: PropTypes.string,
    maxFileSize: PropTypes.number,
    addNewTemplate: PropTypes.func,
    updateTemplateParam: PropTypes.func,
    postTemplateUploadProcessing: PropTypes.func,
    clearTemplateUploadProcessing: PropTypes.func,
    templates: PropTypes.array,
    templateUploadResults: PropTypes.array
  }

  /**
   * @since 6.18.0
   */
  static defaultProps = {
    templateUploadResults: []
  }

  /**
   * `total` and `completed` track the in-flight batch, so each result in the Redux store is drained once
   *
   * @since 4.1
   */
  state = {
    errors: [],
    showSuccess: false,
    total: 0,
    completed: 0
  }

  /**
   * Whether the current batch of uploads is still in flight
   *
   * @returns {boolean} True until every file dispatched by handleOndrop() has reported back
   *
   * @since 6.18.0
   */
  get isUploading () {
    return this.state.completed < this.state.total
  }

  /**
   * Drain any upload results that arrived since the last render
   *
   * @param {Object} prevProps
   *
   * @since 4.1
   */
  componentDidUpdate (prevProps) {
    const { templateUploadResults } = this.props

    if (prevProps.templateUploadResults === templateUploadResults) {
      return
    }

    const fresh = templateUploadResults.slice(this.state.completed)

    if (fresh.length > 0) {
      this.processResults(fresh)
    }
  }

  /**
   * Upload the accepted zips and report the files react-dropzone rejected
   *
   * @param {Array} acceptedFiles
   * @param {Array} fileRejections
   *
   * @since 4.1
   */
  handleOndrop = (acceptedFiles, fileRejections = []) => {
    if (acceptedFiles.length === 0 && fileRejections.length === 0) {
      return
    }

    const errors = fileRejections.map(({ file, errors: reasons }) => ({
      filename: file.name,
      message: reasons[0].code === 'file-too-large' ? this.props.filesizeErrorText : this.props.filenameErrorText
    }))

    /* A drop during an upload joins the batch in flight; clearing the store would lose its results */
    if (this.isUploading) {
      this.setState((prevState) => ({
        errors: [...prevState.errors, ...errors],
        total: prevState.total + acceptedFiles.length
      }))
    } else {
      this.props.clearTemplateUploadProcessing()

      this.setState({
        errors,
        showSuccess: false,
        total: acceptedFiles.length,
        completed: 0
      })
    }

    acceptedFiles.forEach((file) => this.props.postTemplateUploadProcessing(file, file.name))
  }

  /**
   * Apply a batch of upload results to our Redux store and the on-screen messages
   *
   * @param {Array} results
   *
   * @since 6.18.0
   */
  processResults = (results) => {
    const errors = []

    results.forEach((result) => {
      if (result.success) {
        this.addTemplatesToStore(result.templates)
        return
      }

      errors.push({
        filename: result.filename,
        message: result.message || this.props.genericUploadErrorText
      })
    })

    const completed = this.state.completed + results.length

    /* Latch the success message on, so a later failure in the batch can't hide it */
    this.setState({
      completed,
      errors: [...this.state.errors, ...errors],
      showSuccess: this.state.showSuccess || results.some((result) => result.success)
    })

    if (completed >= this.state.total) {
      this.props.clearTemplateUploadProcessing()
    }
  }

  /**
   * Update our Redux store with the new PDF template details
   *
   * @param {Array} templates
   *
   * @since 4.1
   */
  addTemplatesToStore = (templates) => {
    templates.forEach((template) => {
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
  }

  /**
   * Remove message from state once the timeout has finished
   *
   * @since 4.1
   */
  removeMessage = () => {
    this.setState({
      showSuccess: false
    })
  }

  /**
   * Upload progress, errors and success, pinned to the foot of the modal so they show wherever the list is scrolled
   *
   * @since 6.18.0
   */
  renderStatus () {
    const { errors, showSuccess } = this.state
    const uploading = this.isUploading

    if (!uploading && errors.length === 0 && !showSuccess) {
      return null
    }

    return (
      <div data-test='component-templateUploaderStatus' className='gfpdf-dropzone-status'>
        {uploading && <ShowMessage text={this.props.uploadInProgressText} />}

        {errors.map((error, index) => (
          <ShowMessage
            data-test='component-stateError-showMessage'
            key={`${error.filename}-${index}`}
            text={`${error.filename}: ${error.message}`}
            error
          />
        ))}

        {showSuccess && (
          <ShowMessage
            data-test='component-stateMessage-showMessage'
            text={this.props.templateSuccessfullyInstalledUpdated}
            dismissable
            dismissableCallback={this.removeMessage}
          />
        )}
      </div>
    )
  }

  /**
   * @since 4.1
   */
  render () {
    const { children, dropzoneText, maxFileSize } = this.props

    return (
      <Dropzone
        data-test='component-dropzone'
        onDrop={this.handleOndrop}
        accept={{ 'application/zip': ['.zip'] }}
        maxSize={maxFileSize}
        noClick
        noKeyboard
      >
        {({ getRootProps, getInputProps, isDragActive, open }) => (
          <div {...getRootProps({ className: 'gfpdf-template-dropzone' })}>
            <input {...getInputProps()} />

            <TemplateUploaderContext.Provider value={{ open, ajax: this.isUploading }}>
              {children}
            </TemplateUploaderContext.Provider>

            {this.renderStatus()}

            {isDragActive && (
              <div data-test='component-dropzoneOverlay' className='gfpdf-dropzone-overlay'>
                <div className='gfpdf-dropzone-overlay__message'>
                  <span className='dashicons dashicons-upload' />
                  <p>{dropzoneText}</p>
                </div>
              </div>
            )}
          </div>
        )}
      </Dropzone>
    )
  }
}

/**
 * Map Redux state to props
 *
 * @param state
 * @returns {{templates: Array, templateUploadResults: Array}}
 *
 * @since 5.2
 */
const mapStateToProps = (state) => {
  return {
    templates: state.template.list,
    templateUploadResults: state.template.templateUploadResults
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
