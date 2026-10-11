/* Dependencies */
import React, { Component } from 'react'
import PropTypes from 'prop-types'
import { connect } from 'react-redux'
import Dropzone from 'react-dropzone'
/* Components */
import ShowMessage from '../ShowMessage'
import { TemplateUploaderContext } from './TemplateUploaderContext'
/* Utilities */
import { hasPendingUpload } from '../../utilities/Template/hasPendingUpload'
/* Redux actions */
import {
  postTemplateUploadProcessing,
  templateUploadRejected,
  dismissTemplateUploadSuccess
} from '../../actions/templates'

const maxSize = parseInt(GFPDF.templateUploadMaxSize, 10)

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
    postTemplateUploadProcessing: PropTypes.func,
    templateUploadRejected: PropTypes.func,
    dismissTemplateUploadSuccess: PropTypes.func,
    templateUploads: PropTypes.array
  }

  /**
   * Whether any zip in the batch is still uploading
   *
   * @returns {boolean} True until every zip in the batch has reported back
   *
   * @since 6.18.0
   */
  get isUploading () {
    return hasPendingUpload(this.props.templateUploads)
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
    /* Queue the uploads first, so the rejections join their batch */
    acceptedFiles.forEach((file) => this.props.postTemplateUploadProcessing(file, file.name))

    if (fileRejections.length > 0) {
      this.props.templateUploadRejected(
        fileRejections.map(({ file, errors }) => ({
          filename: file.name,
          message: errors[0].code === 'file-too-large'
            ? GFPDF.uploadInvalidExceedsFileSizeLimit
            : GFPDF.uploadInvalidNotZipFile
        }))
      )
    }
  }

  /**
   * Upload progress, errors and success, pinned to the foot of the modal so they show wherever the list is scrolled
   *
   * @since 6.18.0
   */
  renderStatus () {
    const uploads = this.props.templateUploads
    const errors = uploads.filter((upload) => upload.status === 'failed')
    /* Held until the batch finishes, so a dismissed message can't be brought back by a later zip */
    const showSuccess = !this.isUploading && uploads.some((upload) => upload.status === 'success')

    if (!this.isUploading && errors.length === 0 && !showSuccess) {
      return null
    }

    return (
      <div data-test='component-templateUploaderStatus' className='gfpdf-dropzone-status'>
        {this.isUploading && <ShowMessage text={GFPDF.templateUploadInProgress} />}

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
            text={GFPDF.templateSuccessfullyInstalledUpdated}
            dismissable
            dismissableCallback={this.props.dismissTemplateUploadSuccess}
          />
        )}
      </div>
    )
  }

  /**
   * @since 4.1
   */
  render () {
    return (
      <Dropzone
        data-test='component-dropzone'
        onDrop={this.handleOndrop}
        accept={{ 'application/zip': ['.zip'] }}
        maxSize={maxSize}
        noClick
        noKeyboard
      >
        {({ getRootProps, getInputProps, isDragActive, open }) => (
          <div {...getRootProps({ className: 'gfpdf-template-dropzone' })}>
            <input {...getInputProps()} />

            <TemplateUploaderContext.Provider value={{ open, isUploading: this.isUploading }}>
              {this.props.children}
            </TemplateUploaderContext.Provider>

            {this.renderStatus()}

            {isDragActive && (
              <div data-test='component-dropzoneOverlay' className='gfpdf-dropzone-overlay'>
                <div className='gfpdf-dropzone-overlay__message'>
                  <span className='dashicons dashicons-upload' />
                  <p>{GFPDF.templateUploadDropzone}</p>
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
 * @returns {{templateUploads: Array}}
 *
 * @since 5.2
 */
const mapStateToProps = (state) => {
  return {
    templateUploads: state.template.templateUploads
  }
}

/**
 * Map actions to props
 *
 * @param {func} dispatch Redux dispatcher
 *
 * @returns {{postTemplateUploadProcessing: (function(file=object, filename=string)), templateUploadRejected: (function(rejections=Array)), dismissTemplateUploadSuccess: (function())}}
 *
 * @since 4.1
 */
export const mapDispatchToProps = (dispatch) => {
  return {
    postTemplateUploadProcessing: (file, filename) => {
      dispatch(postTemplateUploadProcessing(file, filename))
    },

    templateUploadRejected: (rejections) => {
      dispatch(templateUploadRejected(rejections))
    },

    dismissTemplateUploadSuccess: () => {
      dispatch(dismissTemplateUploadSuccess())
    }
  }
}

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default connect(mapStateToProps, mapDispatchToProps)(TemplateUploader)
