import { fireEvent, act } from '@testing-library/react';
import {
	findByTestAttr,
	renderWithStore,
	createTestStore,
} from '../../testUtilsRTL';
import TemplateDeleteButton from '../../../../../src/assets/js/react/components/Template/TemplateDeleteButton';
import type {
	TemplateItem,
	TemplateState,
} from '../../../../../src/assets/js/react/types';

jest.mock('../../../../../src/assets/js/react/api/templates', () => ({
	apiPostUpdateSelectBox: jest.fn(() => new Promise(() => {})),
	apiPostTemplateProcessing: jest.fn(() => new Promise(() => {})),
	apiPostTemplateUploadProcessing: jest.fn(() => new Promise(() => {})),
}));

describe('Template - TemplateDeleteButton.js', () => {
	const navigate = jest.fn();
	const template = { id: 'zadani' } as TemplateItem;

	const initialState = {
		template: {
			list: [template],
			activeTemplate: '',
			search: '',
			updateSelectBoxText: '',
			templateProcessing: '',
			templateUploadProcessingSuccess: {},
			templateUploadProcessingError: {},
		} as TemplateState,
	};

	beforeEach(() => {
		jest.clearAllMocks();
		window.confirm = jest.fn(() => true);
	});

	test('renders <TemplateDeleteButton /> component', () => {
		const { container } = renderWithStore(
			<TemplateDeleteButton
				navigate={navigate}
				template={template}
				buttonText="Delete"
			/>,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateDeleteButton')
		).toBeInTheDocument();
	});

	test('renders button text', () => {
		const { container } = renderWithStore(
			<TemplateDeleteButton
				navigate={navigate}
				template={template}
				buttonText="Delete"
			/>,
			initialState
		);
		expect(container.querySelector('button')!.textContent).toBe('Delete');
	});

	test('uses callbackFunction prop instead of built-in delete handler', () => {
		const callbackFn = jest.fn();
		const { container } = renderWithStore(
			<TemplateDeleteButton
				navigate={navigate}
				template={template}
				callbackFunction={callbackFn}
				buttonText="Delete"
			/>,
			initialState
		);
		fireEvent.click(
			findByTestAttr(container, 'component-templateDeleteButton')!
		);
		expect(callbackFn).toHaveBeenCalledTimes(1);
	});

	test('button click with confirm=true dispatches TEMPLATE_PROCESSING and DELETE_TEMPLATE', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		const { container } = renderWithStore(
			<TemplateDeleteButton
				navigate={navigate}
				template={template}
				buttonText="Delete"
				templateConfirmDeleteText="Are you sure?"
			/>,
			{},
			{},
			store
		);

		fireEvent.click(
			findByTestAttr(container, 'component-templateDeleteButton')!
		);

		expect(window.confirm).toHaveBeenCalledWith('Are you sure?');
		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'TEMPLATE_PROCESSING' })
		);
		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'DELETE_TEMPLATE' })
		);
	});

	test('button click with confirm=false does not dispatch', () => {
		window.confirm = jest.fn(() => false);
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		const { container } = renderWithStore(
			<TemplateDeleteButton
				navigate={navigate}
				template={template}
				buttonText="Delete"
			/>,
			{},
			{},
			store
		);

		fireEvent.click(
			findByTestAttr(container, 'component-templateDeleteButton')!
		);

		expect(dispatchSpy).not.toHaveBeenCalled();
	});

	test('navigates to /template when templateProcessing changes to success', () => {
		const store = createTestStore(initialState);
		renderWithStore(
			<TemplateDeleteButton
				navigate={navigate}
				template={template}
				buttonText="Delete"
			/>,
			{},
			{},
			store
		);

		act(() => {
			store.dispatch({
				type: 'TEMPLATE_PROCESSING_SUCCESS',
				payload: 'success',
			});
		});

		expect(navigate).toHaveBeenCalledWith('/template');
	});

	test('dispatches ADD_TEMPLATE and CLEAR_TEMPLATE_PROCESSING and navigates when templateProcessing changes to failed', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		renderWithStore(
			<TemplateDeleteButton
				navigate={navigate}
				template={template}
				buttonText="Delete"
				templateDeleteErrorText="Delete failed"
			/>,
			{},
			{},
			store
		);

		act(() => {
			store.dispatch({
				type: 'TEMPLATE_PROCESSING_FAILED',
				payload: 'failed',
			});
		});

		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'ADD_TEMPLATE' })
		);
		expect(navigate).toHaveBeenCalledWith('/template');
		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'CLEAR_TEMPLATE_PROCESSING' })
		);
	});
});
