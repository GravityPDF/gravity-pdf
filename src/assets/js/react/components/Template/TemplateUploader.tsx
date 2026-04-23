/* Dependencies */
import { useState, useRef, useEffect } from '@wordpress/element';
import { DropZone } from '@wordpress/components';
/* Components */
import ShowMessage from '../ShowMessage';
/* Store */
import { useSelect, useDispatch } from '@wordpress/data';
import { TEMPLATE_STORE_NAME, templateStore } from '../../store/templateStore';
/* Types */
import { TemplateItem } from '../../types';

/**
 * Handles the uploading of new PDF templates to the server
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

interface UploadSuccessResponse {
	templates: TemplateItem[];
}

interface UploadErrorResponse {
	message?: string;
}

interface Props {
	genericUploadErrorText?: string;
	addTemplateText?: string;
	filenameErrorText?: string;
	filesizeErrorText?: string;
	installSuccessText?: string;
	installUpdatedText?: string;
	templateSuccessfullyInstalledUpdated?: string;
	templateInstallInstructions?: string;
}

const TemplateUploader = ({
	genericUploadErrorText,
	addTemplateText,
	filenameErrorText,
	filesizeErrorText,
	installSuccessText,
	installUpdatedText,
	templateSuccessfullyInstalledUpdated,
	templateInstallInstructions,
}: Props) => {
	const {
		addTemplate,
		updateTemplateParam,
		postTemplateUploadProcessing,
		clearTemplateUploadProcessing,
	} = useDispatch(TEMPLATE_STORE_NAME);
	const templates = useSelect(
		(select) => select(templateStore).getList(),
		[]
	);
	const templateUploadProcessingSuccess = useSelect(
		(select) => select(templateStore).getTemplateUploadProcessingSuccess(),
		[]
	);
	const templateUploadProcessingError = useSelect(
		(select) => select(templateStore).getTemplateUploadProcessingError(),
		[]
	);

	const [ajax, setAjax] = useState(false);
	const [error, setError] = useState('');
	const [message, setMessage] = useState('');

	const fileInputRef = useRef<HTMLInputElement>(null);

	/* Track previous values to replicate componentDidUpdate comparisons */
	const prevSuccessRef = useRef(templateUploadProcessingSuccess);
	const prevErrorRef = useRef(templateUploadProcessingError);

	/* componentDidUpdate: respond when upload processing state changes */
	useEffect(() => {
		const prevSuccess = prevSuccessRef.current;
		prevSuccessRef.current = templateUploadProcessingSuccess;

		const prevError = prevErrorRef.current;
		prevErrorRef.current = templateUploadProcessingError;

		const ajaxSuccess = (response: UploadSuccessResponse) => {
			response.templates.forEach((template) => {
				const matched = templates.find(
					(item) => item.id === template.id
				);
				if (matched === undefined) {
					addTemplate({
						...template,
						new: true,
						message: installSuccessText,
					});
				} else {
					updateTemplateParam(
						template.id,
						'message',
						installUpdatedText ?? null
					);
				}
			});
			setAjax(false);
			setMessage(templateSuccessfullyInstalledUpdated ?? '');
			clearTemplateUploadProcessing();
		};

		const ajaxFailed = (err: UploadErrorResponse) => {
			setError(err?.message || genericUploadErrorText || '');
			setAjax(false);
			clearTemplateUploadProcessing();
		};

		const successWithTemplates =
			templateUploadProcessingSuccess as unknown as UploadSuccessResponse;
		if (
			prevSuccess !== templateUploadProcessingSuccess &&
			successWithTemplates?.templates?.length > 0
		) {
			ajaxSuccess(successWithTemplates);
		}

		if (
			prevError !== templateUploadProcessingError &&
			Object.keys(templateUploadProcessingError).length > 0
		) {
			ajaxFailed(templateUploadProcessingError as UploadErrorResponse);
		}
	}, [
		templateUploadProcessingSuccess,
		templateUploadProcessingError,
		templates,
		installSuccessText,
		installUpdatedText,
		genericUploadErrorText,
		templateSuccessfullyInstalledUpdated,
		addTemplate,
		updateTemplateParam,
		clearTemplateUploadProcessing,
	]);

	const checkFilename = (name: string) => {
		if (name.substr(name.length - 4) !== '.zip') {
			setError(filenameErrorText ?? '');
			return false;
		}
		return true;
	};

	const checkFilesize = (size: number) => {
		if (size / 1024 > 10240) {
			setError(filesizeErrorText ?? '');
			return false;
		}
		return true;
	};

	const handleOndrop = (acceptedFiles: { name: string; size: number }[]) => {
		if (acceptedFiles instanceof Array && acceptedFiles.length > 0) {
			acceptedFiles.forEach((file) => {
				const filename = file.name;

				if (!checkFilename(filename) || !checkFilesize(file.size)) {
					return;
				}

				setAjax(true);
				setError('');
				setMessage('');

				postTemplateUploadProcessing(file as File, filename);
			});
		}
	};

	const handleFilesDrop = (files: File[]) => {
		handleOndrop(files);
	};

	const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
		const files = Array.from(e.target.files ?? []);
		if (files.length) {
			handleOndrop(files);
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
			<div className="dropzone">
				<input
					ref={fileInputRef}
					type="file"
					accept=".zip"
					style={{ display: 'none' }}
					onChange={handleFileSelect}
				/>
				<DropZone onFilesDrop={handleFilesDrop} />
				<a
					href="#/template"
					onClick={(e) => {
						e.preventDefault();
						fileInputRef.current?.click();
					}}
					onKeyDown={(e) => {
						if (e.key === ' ') {
							e.preventDefault();
							fileInputRef.current?.click();
						}
					}}
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

					<h2 className="theme-name">{addTemplateText}</h2>
				</a>
				<div
					className="gfpdf-template-install-instructions"
					id="gfpdf-template-install-instructions"
				>
					{templateInstallInstructions}
				</div>
			</div>
		</div>
	);
};

export default TemplateUploader;
