import React from 'react';
import { act, fireEvent } from '@testing-library/react';
import {
	findByTestAttr,
	renderWithStore,
	createTestStore,
} from '../../testUtilsRTL';
import TemplateUploader from '../../../../../src/assets/js/react/components/Template/TemplateUploader';

jest.mock(
	'react-dropzone',
	() =>
		function Dropzone({ onDrop, children }) {
			return (
				<>
					<button
						data-test="drop-valid-file"
						onClick={() =>
							onDrop([{ name: 'template.zip', size: 1024 }])
						}
					/>
					<button
						data-test="drop-invalid-ext"
						onClick={() =>
							onDrop([{ name: 'template.txt', size: 1024 }])
						}
					/>
					<button
						data-test="drop-large-file"
						onClick={() =>
							onDrop([
								{ name: 'template.zip', size: 1024 * 10241 },
							])
						}
					/>
					{children({
						getRootProps: () => ({}),
						getInputProps: () => ({}),
						isDragActive: false,
					})}
				</>
			);
		}
);

describe('Template - TemplateUploader.js', () => {
	const initialState = {
		template: {
			list: [
				{ id: 'blank-slate', template: 'Blank Slate' },
				{ id: 'rubix', template: 'Rubix' },
			],
			activeTemplate: '',
			search: '',
			updateSelectBoxText: '',
			templateProcessing: '',
			templateUploadProcessingSuccess: {},
			templateUploadProcessingError: {},
		},
	};

	const defaultProps = {
		genericUploadErrorText: 'Generic upload error',
		addTemplateText: 'Add New Template',
		filenameErrorText: 'Filename must be a zip file',
		filesizeErrorText: 'File size exceeds limit',
		installSuccessText: 'Installed successfully',
		installUpdatedText: 'Updated successfully',
		templateSuccessfullyInstalledUpdated:
			'Template installed/updated successfully',
		templateInstallInstructions: 'Drag & drop your zip file',
	};

	test('renders <TemplateUploader /> component', () => {
		const { container } = renderWithStore(
			<TemplateUploader {...defaultProps} />,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateUploader')
		).toBeInTheDocument();
	});

	test('renders Dropzone area', () => {
		const { container } = renderWithStore(
			<TemplateUploader {...defaultProps} />,
			initialState
		);
		expect(
			findByTestAttr(container, 'drop-valid-file')
		).toBeInTheDocument();
	});

	test('valid file drop dispatches POST_TEMPLATE_UPLOAD_PROCESSING', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		const { container } = renderWithStore(
			<TemplateUploader {...defaultProps} />,
			{},
			{},
			store
		);
		fireEvent.click(findByTestAttr(container, 'drop-valid-file'));
		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'POST_TEMPLATE_UPLOAD_PROCESSING' })
		);
	});

	test('invalid file extension shows filename error', () => {
		const { container, getByText } = renderWithStore(
			<TemplateUploader {...defaultProps} />,
			initialState
		);
		fireEvent.click(findByTestAttr(container, 'drop-invalid-ext'));
		expect(getByText('Filename must be a zip file')).toBeInTheDocument();
	});

	test('oversized file shows filesize error', () => {
		const { container, getByText } = renderWithStore(
			<TemplateUploader {...defaultProps} />,
			initialState
		);
		fireEvent.click(findByTestAttr(container, 'drop-large-file'));
		expect(getByText('File size exceeds limit')).toBeInTheDocument();
	});

	test('success with new template dispatches ADD_TEMPLATE and shows success message', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		const { getByText } = renderWithStore(
			<TemplateUploader {...defaultProps} />,
			{},
			{},
			store
		);

		act(() => {
			store.dispatch({
				type: 'TEMPLATE_UPLOAD_PROCESSING_SUCCESS',
				payload: {
					templates: [{ id: 'cellulose', template: 'Cellulose' }],
				},
			});
		});

		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'ADD_TEMPLATE' })
		);
		expect(
			getByText('Template installed/updated successfully')
		).toBeInTheDocument();
	});

	test('success with existing template dispatches UPDATE_TEMPLATE_PARAM', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		renderWithStore(<TemplateUploader {...defaultProps} />, {}, {}, store);

		act(() => {
			store.dispatch({
				type: 'TEMPLATE_UPLOAD_PROCESSING_SUCCESS',
				payload: { templates: [{ id: 'rubix', template: 'Rubix' }] },
			});
		});

		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'UPDATE_TEMPLATE_PARAM' })
		);
	});

	test('success dispatches CLEAR_TEMPLATE_UPLOAD_PROCESSING', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		renderWithStore(<TemplateUploader {...defaultProps} />, {}, {}, store);

		act(() => {
			store.dispatch({
				type: 'TEMPLATE_UPLOAD_PROCESSING_SUCCESS',
				payload: {
					templates: [{ id: 'cellulose', template: 'Cellulose' }],
				},
			});
		});

		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({
				type: 'CLEAR_TEMPLATE_UPLOAD_PROCESSING',
			})
		);
	});

	test('error response shows error message from payload', () => {
		const store = createTestStore(initialState);
		const { getByText } = renderWithStore(
			<TemplateUploader {...defaultProps} />,
			{},
			{},
			store
		);

		act(() => {
			store.dispatch({
				type: 'TEMPLATE_UPLOAD_PROCESSING_FAILED',
				payload: { message: 'Specific upload error' },
			});
		});

		expect(getByText('Specific upload error')).toBeInTheDocument();
	});

	test('error response falls back to genericUploadErrorText when no message', () => {
		const store = createTestStore(initialState);
		const { getByText } = renderWithStore(
			<TemplateUploader {...defaultProps} />,
			{},
			{},
			store
		);

		act(() => {
			store.dispatch({
				type: 'TEMPLATE_UPLOAD_PROCESSING_FAILED',
				payload: { code: 'upload_error' },
			});
		});

		expect(getByText('Generic upload error')).toBeInTheDocument();
	});

	test('error response dispatches CLEAR_TEMPLATE_UPLOAD_PROCESSING', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		renderWithStore(<TemplateUploader {...defaultProps} />, {}, {}, store);

		act(() => {
			store.dispatch({
				type: 'TEMPLATE_UPLOAD_PROCESSING_FAILED',
				payload: { message: 'error' },
			});
		});

		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({
				type: 'CLEAR_TEMPLATE_UPLOAD_PROCESSING',
			})
		);
	});
});
