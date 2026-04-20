/* Dependencies */
import React, { useState, useRef, useEffect } from 'react';
import PropTypes from 'prop-types';
import { useSelector, useDispatch } from 'react-redux';
import classNames from 'classnames';
import Dropzone from 'react-dropzone';
/* Components */
import ShowMessage from '../ShowMessage';
/* Redux actions */
import {
	addTemplate as addTemplateAction,
	updateTemplateParam as updateTemplateParamAction,
	postTemplateUploadProcessing as postTemplateUploadProcessingAction,
	clearTemplateUploadProcessing as clearTemplateUploadProcessingAction,
} from '../../actions/templates';

/**
 * Handles the uploading of new PDF templates to the server
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/**
 * React Component
 *
 * @param {Object} root0
 * @param {*}      root0.genericUploadErrorText
 * @param {*}      root0.addTemplateText
 * @param {*}      root0.filenameErrorText
 * @param {*}      root0.filesizeErrorText
 * @param {*}      root0.installSuccessText
 * @param {*}      root0.installUpdatedText
 * @param {*}      root0.templateSuccessfullyInstalledUpdated
 * @param {*}      root0.templateInstallInstructions
 * @since 4.1
 */
const TemplateUploader = ({
	genericUploadErrorText,
	addTemplateText,
	filenameErrorText,
	filesizeErrorText,
	installSuccessText,
	installUpdatedText,
	templateSuccessfullyInstalledUpdated,
	templateInstallInstructions,
}) => {
	const dispatch = useDispatch();
	const templates = useSelector((s) => s.template.list);
	const templateUploadProcessingSuccess = useSelector(
		(s) => s.template.templateUploadProcessingSuccess
	);
	const templateUploadProcessingError = useSelector(
		(s) => s.template.templateUploadProcessingError
	);

	const [ajax, setAjax] = useState(false);
	const [error, setError] = useState('');
	const [message, setMessage] = useState('');

	/* Track previous values to replicate componentDidUpdate comparisons */
	const prevSuccessRef = useRef(templateUploadProcessingSuccess);
	const prevErrorRef = useRef(templateUploadProcessingError);

	/* componentDidUpdate: respond when upload processing state changes */
	useEffect(() => {
		const prevSuccess = prevSuccessRef.current;
		prevSuccessRef.current = templateUploadProcessingSuccess;

		const prevError = prevErrorRef.current;
		prevErrorRef.current = templateUploadProcessingError;

		const ajaxSuccess = (response) => {
			response.templates.forEach((template) => {
				const matched = templates.find(
					(item) => item.id === template.id
				);
				if (matched === undefined) {
					dispatch(
						addTemplateAction({
							...template,
							new: true,
							message: installSuccessText,
						})
					);
				} else {
					dispatch(
						updateTemplateParamAction(
							template.id,
							'message',
							installUpdatedText
						)
					);
				}
			});
			setAjax(false);
			setMessage(templateSuccessfullyInstalledUpdated);
			dispatch(clearTemplateUploadProcessingAction());
		};

		const ajaxFailed = (err) => {
			setError(err?.message || genericUploadErrorText);
			setAjax(false);
			dispatch(clearTemplateUploadProcessingAction());
		};

		if (
			prevSuccess !== templateUploadProcessingSuccess &&
			templateUploadProcessingSuccess?.templates?.length > 0
		) {
			ajaxSuccess(templateUploadProcessingSuccess);
		}

		if (
			prevError !== templateUploadProcessingError &&
			Object.keys(templateUploadProcessingError).length > 0
		) {
			ajaxFailed(templateUploadProcessingError);
		}
	}, [
		templateUploadProcessingSuccess,
		templateUploadProcessingError,
		templates,
		installSuccessText,
		installUpdatedText,
		genericUploadErrorText,
		templateSuccessfullyInstalledUpdated,
		dispatch,
	]);

	const checkFilename = (name) => {
		if (name.substr(name.length - 4) !== '.zip') {
			setError(filenameErrorText);
			return false;
		}
		return true;
	};

	const checkFilesize = (size) => {
		if (size / 1024 > 10240) {
			setError(filesizeErrorText);
			return false;
		}
		return true;
	};

	const handleOndrop = (acceptedFiles) => {
		if (acceptedFiles instanceof Array && acceptedFiles.length > 0) {
			acceptedFiles.forEach((file) => {
				const filename = file.name;

				if (!checkFilename(filename) || !checkFilesize(file.size)) {
					return;
				}

				setAjax(true);
				setError('');
				setMessage('');

				dispatch(postTemplateUploadProcessingAction(file, filename));
			});
		}
	};

	const removeMessage = () => {
		setMessage('');
	};

	return (
		<div
			data-test="component-templateUploader"
			className="theme add-new-theme gfpdf-dropzone"
		>
			<Dropzone data-test="component-dropzone" onDrop={handleOndrop}>
				{({ getRootProps, getInputProps, isDragActive }) => {
					return (
						<div
							{...getRootProps()}
							className={classNames('dropzone', {
								'dropzone--isActive': isDragActive,
							})}
						>
							<input {...getInputProps()} />
							<a
								href="#/template"
								className={ajax ? 'doing-ajax' : ''}
								aria-labelledby="gfpdf-template-install-instructions"
							>
								<div className="theme-screenshot">
									<span />
								</div>

								{error !== '' && (
									<ShowMessage
										data-test="component-stateError-showMessage"
										text={error}
										error
									/>
								)}
								{message !== '' ? (
									<ShowMessage
										data-test="component-stateMessage-showMessage"
										text={message}
										dismissable
										dismissableCallback={removeMessage}
									/>
								) : null}

								<h2 className="theme-name">
									{addTemplateText}
								</h2>
							</a>
							<div
								className="gfpdf-template-install-instructions"
								id="gfpdf-template-install-instructions"
							>
								{templateInstallInstructions}
							</div>
						</div>
					);
				}}
			</Dropzone>
		</div>
	);
};

TemplateUploader.propTypes = {
	genericUploadErrorText: PropTypes.string,
	addTemplateText: PropTypes.string,
	filenameErrorText: PropTypes.string,
	filesizeErrorText: PropTypes.string,
	installSuccessText: PropTypes.string,
	installUpdatedText: PropTypes.string,
	templateSuccessfullyInstalledUpdated: PropTypes.string,
	templateInstallInstructions: PropTypes.string,
};

export default TemplateUploader;
