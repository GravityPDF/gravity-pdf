/* Dependencies */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import Dropzone from 'react-dropzone';
/* Components */
import ShowMessage from '../ShowMessage';
import { TemplateUploaderContext } from './TemplateUploaderContext';
/* Redux actions */
import {
	postTemplateUploadProcessing,
	clearTemplateUploadProcessing,
} from '../../actions/templates';

/**
 * Handles the uploading of new PDF templates to the server
 *
 * Wraps the whole Template Manager so a zip can be dropped anywhere, and shares the file picker via context
 *
 * @package			Gravity PDF
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
		clearTemplateUploadProcessing: PropTypes.func,
		templateUploadTotal: PropTypes.number,
		templateUploadResults: PropTypes.array,
	};

	/**
	 * The files react-dropzone rejected in this batch, and whether the success message was dismissed
	 *
	 * @since 4.1
	 */
	state = {
		rejections: [],
		dismissed: false,
	};

	/**
	 * Whether the current batch of uploads is still in flight
	 *
	 * @return { boolean } True until every zip in the batch has reported back
	 *
	 * @since 6.18.0
	 */
	get isUploading() {
		return (
			this.props.templateUploadResults.length <
			this.props.templateUploadTotal
		);
	}

	/**
	 * Clear a finished batch, so its messages don't show again when the Template Manager is reopened
	 *
	 * @since 6.18.0
	 */
	componentWillUnmount() {
		if (!this.isUploading) {
			this.props.clearTemplateUploadProcessing();
		}
	}

	/**
	 * Upload the accepted zips and report the files react-dropzone rejected
	 *
	 * @param { Array<Object> } acceptedFiles
	 * @param { Array<Object> } fileRejections
	 *
	 * @since 4.1
	 */
	handleOndrop = (acceptedFiles, fileRejections = []) => {
		if (acceptedFiles.length === 0 && fileRejections.length === 0) {
			return;
		}

		const rejections = fileRejections.map(({ file, errors }) => ({
			filename: file.name,
			message:
				errors[0].code === 'file-too-large'
					? GFPDF.uploadInvalidExceedsFileSizeLimit
					: GFPDF.uploadInvalidNotZipFile,
		}));

		/* A drop during an upload joins the batch in flight */
		if (this.isUploading) {
			this.setState((prevState) => ({
				rejections: [...prevState.rejections, ...rejections],
			}));
		} else {
			this.props.clearTemplateUploadProcessing();
			this.setState({ rejections, dismissed: false });
		}

		acceptedFiles.forEach((file) =>
			this.props.postTemplateUploadProcessing(file, file.name)
		);
	};

	/**
	 * Hide the success message once it's dismissed or times out
	 *
	 * @since 4.1
	 */
	removeMessage = () => {
		this.setState({ dismissed: true });
	};

	/**
	 * Upload progress, errors and success, pinned to the foot of the modal so they show wherever the list is scrolled
	 *
	 * @since 6.18.0
	 */
	renderStatus() {
		const results = this.props.templateUploadResults;
		const uploading = this.isUploading;
		const showSuccess =
			!this.state.dismissed && results.some((result) => result.success);
		const errors = [
			...this.state.rejections,
			...results
				.filter((result) => !result.success)
				.map((result) => ({
					filename: result.filename,
					message: result.message || GFPDF.problemWithTheUpload,
				})),
		];

		if (!uploading && errors.length === 0 && !showSuccess) {
			return null;
		}

		return (
			<div
				data-test="component-templateUploaderStatus"
				className="gfpdf-dropzone-status"
			>
				{uploading && (
					<ShowMessage text={GFPDF.templateUploadInProgress} />
				)}

				{errors.map((error, index) => (
					<ShowMessage
						data-test="component-stateError-showMessage"
						key={`${error.filename}-${index}`}
						text={`${error.filename}: ${error.message}`}
						error
					/>
				))}

				{showSuccess && (
					<ShowMessage
						data-test="component-stateMessage-showMessage"
						text={GFPDF.templateSuccessfullyInstalledUpdated}
						dismissable
						dismissableCallback={this.removeMessage}
					/>
				)}
			</div>
		);
	}

	/**
	 * @since 4.1
	 */
	render() {
		return (
			<Dropzone
				data-test="component-dropzone"
				onDrop={this.handleOndrop}
				accept={{ 'application/zip': ['.zip'] }}
				maxSize={parseInt(GFPDF.templateUploadMaxSize, 10)}
				noClick
				noKeyboard
			>
				{({ getRootProps, getInputProps, isDragActive, open }) => (
					<div
						{...getRootProps({
							className: 'gfpdf-template-dropzone',
						})}
					>
						<input {...getInputProps()} />

						<TemplateUploaderContext.Provider
							value={{ open, isUploading: this.isUploading }}
						>
							{this.props.children}
						</TemplateUploaderContext.Provider>

						{this.renderStatus()}

						{isDragActive && (
							<div
								data-test="component-dropzoneOverlay"
								className="gfpdf-dropzone-overlay"
							>
								<div className="gfpdf-dropzone-overlay__message">
									<span className="dashicons dashicons-upload" />
									<p>{GFPDF.templateUploadDropzone}</p>
								</div>
							</div>
						)}
					</div>
				)}
			</Dropzone>
		);
	}
}

/**
 * Map Redux state to props
 *
 * @param { Object } state
 * @param { Object } state.template
 *
 * @return {{
 * templateUploadTotal: number,
 * templateUploadResults: Array<Object>
 * }} mapped state
 *
 * @since 5.2
 */
const mapStateToProps = (state) => {
	return {
		templateUploadTotal: state.template.templateUploadTotal,
		templateUploadResults: state.template.templateUploadResults,
	};
};

/**
 * Map actions to props
 *
 * @param { Function } dispatch Redux dispatcher
 *
 * @return {{
 * 	postTemplateUploadProcessing: Function
 * 	clearTemplateUploadProcessing: Function
 * 	}} mappedDispatch
 *
 * @since 4.1
 */
export const mapDispatchToProps = (dispatch) => {
	return {
		postTemplateUploadProcessing: (file, filename) => {
			dispatch(postTemplateUploadProcessing(file, filename));
		},

		clearTemplateUploadProcessing: () => {
			dispatch(clearTemplateUploadProcessing());
		},
	};
};

/**
 * Maps our Redux store to our React component
 *
 * @since 4.1
 */
export default connect(mapStateToProps, mapDispatchToProps)(TemplateUploader);
