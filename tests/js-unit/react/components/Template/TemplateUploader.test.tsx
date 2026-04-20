import * as React from '@wordpress/element';
import { act, fireEvent } from '@testing-library/react';
import {
	findByTestAttr,
	renderWithStore,
	createTestStore,
} from '../../testUtilsRTL';
import TemplateUploader from '../../../../../src/assets/js/react/components/Template/TemplateUploader';
import type { TemplateState } from '../../../../../src/assets/js/react/types';

jest.mock('../../../../../src/assets/js/react/api/templates', () => ({
	apiPostUpdateSelectBox: jest.fn().mockResolvedValue(''),
	apiPostTemplateProcessing: jest.fn().mockResolvedValue(null),
	apiPostTemplateUploadProcessing: jest.fn().mockResolvedValue({}),
}));

jest.mock('@wordpress/components', () => ({
	...jest.requireActual('@wordpress/components'),
	DropZone: ({
		onFilesDrop,
	}: {
		onFilesDrop?: (files: { name: string; size: number }[]) => void;
	}) => (
		<>
			<button
				data-test="drop-valid-file"
				onClick={() =>
					onFilesDrop?.([{ name: 'template.zip', size: 1024 }])
				}
			/>
			<button
				data-test="drop-invalid-ext"
				onClick={() =>
					onFilesDrop?.([{ name: 'template.txt', size: 1024 }])
				}
			/>
			<button
				data-test="drop-large-file"
				onClick={() =>
					onFilesDrop?.([
						{ name: 'template.zip', size: 1024 * 10241 },
					])
				}
			/>
		</>
	),
}));

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
		} as unknown as TemplateState,
	};

	test('renders <TemplateUploader /> component', () => {
		const { container } = renderWithStore(
			<TemplateUploader />,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateUploader')
		).toBeInTheDocument();
	});

	test('renders Dropzone area', () => {
		const { container } = renderWithStore(
			<TemplateUploader />,
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
			<TemplateUploader />,
			{},
			{},
			store
		);
		fireEvent.click(findByTestAttr(container, 'drop-valid-file')!);
		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'POST_TEMPLATE_UPLOAD_PROCESSING' })
		);
	});

	test('invalid file extension shows filename error', () => {
		const { container, getByText } = renderWithStore(
			<TemplateUploader />,
			initialState
		);
		fireEvent.click(findByTestAttr(container, 'drop-invalid-ext')!);
		expect(
			getByText('Upload is not a valid template. Upload a .zip file.')
		).toBeInTheDocument();
	});

	test('oversized file shows filesize error', () => {
		const { container, getByText } = renderWithStore(
			<TemplateUploader />,
			initialState
		);
		fireEvent.click(findByTestAttr(container, 'drop-large-file')!);
		expect(getByText('Upload exceeds the 10MB limit.')).toBeInTheDocument();
	});

	test('success with new template dispatches ADD_TEMPLATE and shows success message', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		const { getByText } = renderWithStore(
			<TemplateUploader />,
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
			getByText('PDF Template(s) Successfully Installed / Updated')
		).toBeInTheDocument();
	});

	test('success with existing template dispatches UPDATE_TEMPLATE_PARAM', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		renderWithStore(<TemplateUploader />, {}, {}, store);

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
		renderWithStore(<TemplateUploader />, {}, {}, store);

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
			<TemplateUploader />,
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

	test('error response falls back to generic text when no message', () => {
		const store = createTestStore(initialState);
		const { getByText } = renderWithStore(
			<TemplateUploader />,
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

		expect(
			getByText(
				'There was a problem with the upload. Reload the page and try again.'
			)
		).toBeInTheDocument();
	});

	test('error response dispatches CLEAR_TEMPLATE_UPLOAD_PROCESSING', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		renderWithStore(<TemplateUploader />, {}, {}, store);

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
