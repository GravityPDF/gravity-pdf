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
	addTemplate,
	updateTemplateParam,
	postTemplateUploadProcessing,
	clearTemplateUploadProcessing,
} from '../../actions/templates';

/**
 * Handles the uploading of new PDF templates to the server
 *
 * Wraps the entire Template Manager so a zip can be dropped anywhere in the window, and shares the file
 * picker with the "Add New Template" tile via context
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/* The fallback ceiling if the server didn't tell us its own (see Helper_Templates::MAX_UPLOAD_SIZE) */
export const DEFAULT_MAX_FILE_SIZE = 32 * 1024 * 1024;

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
		addTemplateText: PropTypes.string,
		filenameErrorText: PropTypes.string,
		filesizeErrorText: PropTypes.string,
		installSuccessText: PropTypes.string,
		installUpdatedText: PropTypes.string,
		templateSuccessfullyInstalledUpdated: PropTypes.string,
		templateInstallInstructions: PropTypes.string,
		dropzoneText: PropTypes.string,
		uploadInProgressText: PropTypes.string,
		/* wp_localize_script() stringifies scalars, so this arrives from PHP as a string */
		maxFileSize: PropTypes.oneOfType([PropTypes.number, PropTypes.string]),
		addNewTemplate: PropTypes.func,
		updateTemplateParam: PropTypes.func,
		postTemplateUploadProcessing: PropTypes.func,
		clearTemplateUploadProcessing: PropTypes.func,
		templates: PropTypes.array,
		templateUploadResults: PropTypes.array,
	};

	/**
	 * @since 6.17
	 */
	static defaultProps = {
		templateUploadResults: [],
	};

	/**
	 * Setup internal component state that doesn't need to be in Redux
	 *
	 * `total` and `completed` track the current batch of uploads so results can be drained from the
	 * Redux store exactly once, even when several land in the same render
	 *
	 * @return {{
	 * errors: Array<Object>,
	 * showSuccess: boolean,
	 * total: number,
	 * completed: number
	 * }} initial state
	 *
	 * @since 4.1
	 */
	state = {
		errors: [],
		showSuccess: false,
		total: 0,
		completed: 0,
	};

	/**
	 * Whether the current batch of uploads is still in flight
	 *
	 * @return { boolean } True until every file dispatched by handleOndrop() has reported back
	 *
	 * @since 6.17
	 */
	get isUploading() {
		return this.state.completed < this.state.total;
	}

	/**
	 * Drain any upload results that arrived since the last render
	 *
	 * @param { Object } prevProps
	 *
	 * @since 4.1
	 */
	componentDidUpdate(prevProps) {
		const { templateUploadResults } = this.props;

		if (prevProps.templateUploadResults === templateUploadResults) {
			return;
		}

		const fresh = templateUploadResults.slice(this.state.completed);

		if (fresh.length > 0) {
			this.processResults(fresh);
		}
	}

	/**
	 * Manages the template file upload
	 *
	 * @param { Array<Object> } acceptedFiles The array of uploaded files we should send to the server
	 *
	 * @since 4.1
	 */
	handleOndrop = (acceptedFiles) => {
		if (acceptedFiles.length === 0) {
			return;
		}

		const errors = [];
		const valid = [];

		acceptedFiles.forEach((file) => {
			const error = this.validateFile(file);

			if (error !== '') {
				errors.push({ filename: file.name, message: error });
				return;
			}

			valid.push(file);
		});

		/* Discard the previous batch's results before we start counting this one */
		this.props.clearTemplateUploadProcessing();

		this.setState({
			errors,
			showSuccess: false,
			total: valid.length,
			completed: 0,
		});

		valid.forEach((file) =>
			this.props.postTemplateUploadProcessing(file, file.name)
		);
	};

	/**
	 * Check a file is something we're willing to send to the server
	 *
	 * The extension is checked instead of the mime type, which isn't reported reliably by all browsers
	 *
	 * @param { Object } file
	 *
	 * @return { string } The problem with the file, or an empty string when it's valid
	 *
	 * @since 6.17
	 */
	validateFile = (file) => {
		if (file.name.substr(file.name.length - 4) !== '.zip') {
			return this.props.filenameErrorText;
		}

		/* wp_localize_script() stringifies the server's limit, and may not have sent one at all */
		const maxFileSize =
			parseInt(this.props.maxFileSize, 10) || DEFAULT_MAX_FILE_SIZE;

		if (file.size > maxFileSize) {
			return this.props.filesizeErrorText;
		}

		return '';
	};

	/**
	 * Apply a batch of upload results to our Redux store and the on-screen messages
	 *
	 * @param { Array<Object> } results
	 *
	 * @since 6.17
	 */
	processResults = (results) => {
		const errors = [];
		let installed = 0;

		results.forEach((result) => {
			if (!result.success) {
				errors.push({
					filename: result.filename,
					message:
						result.message || this.props.genericUploadErrorText,
				});

				return;
			}

			this.addTemplatesToStore(result.templates);
			installed++;
		});

		const completed = this.state.completed + results.length;
		const done = completed >= this.state.total;

		/* Latch the success message on, so a later failure in the batch can't hide it */
		this.setState((prevState) => ({
			completed,
			errors: [...prevState.errors, ...errors],
			showSuccess: prevState.showSuccess || installed > 0,
		}));

		if (done) {
			this.props.clearTemplateUploadProcessing();
		}
	};

	/**
	 * Update our Redux store with the new PDF template details
	 *
	 * @param { Array<Object> } templates
	 *
	 * @since 4.1
	 */
	addTemplatesToStore = (templates) => {
		templates.forEach((template) => {
			/* Check if template already in the list before adding to our store */
			const matched = this.props.templates.find((item) => {
				return item.id === template.id;
			});

			if (matched === undefined) {
				template.new = true; // ensure new templates go to end of list
				template.message = this.props.installSuccessText;
				this.props.addNewTemplate(template);
			} else {
				this.props.updateTemplateParam(
					template.id,
					'message',
					this.props.installUpdatedText
				);
			}
		});
	};

	/**
	 * Remove message from state once the timeout has finished
	 *
	 * @since 4.1
	 */
	removeMessage = () => {
		this.setState({
			showSuccess: false,
		});
	};

	/**
	 * The upload progress, errors and success message, pinned to the foot of the Template Manager so
	 * they're seen no matter where the template list is scrolled to
	 *
	 * @since 6.17
	 */
	renderStatus() {
		const { errors, showSuccess } = this.state;
		const uploading = this.isUploading;

		if (!uploading && errors.length === 0 && !showSuccess) {
			return null;
		}

		return (
			<div
				data-test="component-templateUploaderStatus"
				className="gfpdf-dropzone-status"
			>
				{uploading && (
					<ShowMessage text={this.props.uploadInProgressText} />
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
						text={this.props.templateSuccessfullyInstalledUpdated}
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
		const {
			children,
			dropzoneText,
			addTemplateText,
			templateInstallInstructions,
		} = this.props;

		return (
			<Dropzone
				data-test="component-dropzone"
				onDrop={this.handleOndrop}
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
							value={{
								open,
								ajax: this.isUploading,
								addTemplateText,
								templateInstallInstructions,
							}}
						>
							{children}
						</TemplateUploaderContext.Provider>

						{this.renderStatus()}

						{isDragActive && (
							<div
								data-test="component-dropzoneOverlay"
								className="gfpdf-dropzone-overlay"
							>
								<div className="gfpdf-dropzone-overlay__message">
									<span className="dashicons dashicons-upload" />
									<p>{dropzoneText}</p>
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
 * templates: Array<Object>,
 * templateUploadResults: Array<Object>
 * }} mapped state
 *
 * @since 5.2
 */
const mapStateToProps = (state) => {
	return {
		templates: state.template.list,
		templateUploadResults: state.template.templateUploadResults,
	};
};

/**
 * Map actions to props
 *
 * @param { Function } dispatch Redux dispatcher
 *
 * @return {{
 * 	addNewTemplate: Function
 * 	updateTemplateParam: Function
 * 	postTemplateUploadProcessing: Function
 * 	clearTemplateUploadProcessing: Function
 * 	}} mappedDispatch
 *
 * @since 4.1
 */
export const mapDispatchToProps = (dispatch) => {
	return {
		addNewTemplate: (template) => {
			dispatch(addTemplate(template));
		},

		updateTemplateParam: (id, name, value) => {
			dispatch(updateTemplateParam(id, name, value));
		},

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
