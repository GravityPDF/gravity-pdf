/* Dependencies */
import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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

const TemplateUploader = () => {
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
						message: __(
							'Template successfully installed',
							'gravity-pdf'
						),
					});
				} else {
					updateTemplateParam(
						template.id,
						'message',
						__('Template successfully updated', 'gravity-pdf')
					);
				}
			});
			setAjax(false);
			setMessage(
				__(
					'PDF Template(s) Successfully Installed / Updated',
					'gravity-pdf'
				)
			);
			clearTemplateUploadProcessing();
		};

		const ajaxFailed = (err: UploadErrorResponse) => {
			setError(
				err?.message ||
					__(
						'There was a problem with the upload. Reload the page and try again.',
						'gravity-pdf'
					)
			);
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
		addTemplate,
		updateTemplateParam,
		clearTemplateUploadProcessing,
	]);

	const checkFilename = (name: string) => {
		if (name.substr(name.length - 4) !== '.zip') {
			setError(
				__(
					'Upload is not a valid template. Upload a .zip file.',
					'gravity-pdf'
				)
			);
			return false;
		}
		return true;
	};

	const checkFilesize = (size: number) => {
		if (size / 1024 > 10240) {
			setError(__('Upload exceeds the 10MB limit.', 'gravity-pdf'));
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

					<h2 className="theme-name">
						{__('Add New Template', 'gravity-pdf')}
					</h2>
				</a>
				<div
					className="gfpdf-template-install-instructions"
					id="gfpdf-template-install-instructions"
				>
					{__(
						'If you have a PDF template in .zip format you may install it here. You can also update an existing PDF template (this will override any changes you have made).',
						'gravity-pdf'
					)}
				</div>
			</div>
		</div>
	);
};

export default TemplateUploader;
