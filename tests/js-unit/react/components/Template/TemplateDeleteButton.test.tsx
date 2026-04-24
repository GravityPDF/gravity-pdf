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
				onSelectTemplate={jest.fn()}
				template={template}
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
				onSelectTemplate={jest.fn()}
				template={template}
			/>,
			initialState
		);
		expect(container.querySelector('button')!.textContent).toBe('Delete');
	});

	const findDialogButton = (text: string) =>
		Array.from(
			document.body.querySelectorAll<HTMLButtonElement>(
				'.components-modal__frame button'
			)
		).find((b) => b.textContent === text);

	test('delete flow - OK in ConfirmDialog dispatches TEMPLATE_PROCESSING and DELETE_TEMPLATE', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		const { container } = renderWithStore(
			<TemplateDeleteButton
				onSelectTemplate={jest.fn()}
				template={template}
			/>,
			{},
			{},
			store
		);

		fireEvent.click(
			findByTestAttr(container, 'component-templateDeleteButton')!
		);
		fireEvent.click(findDialogButton('OK')!);

		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'TEMPLATE_PROCESSING' })
		);
		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'DELETE_TEMPLATE' })
		);
	});

	test('delete flow - Cancel in ConfirmDialog does not dispatch', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		const { container } = renderWithStore(
			<TemplateDeleteButton
				onSelectTemplate={jest.fn()}
				template={template}
			/>,
			{},
			{},
			store
		);

		fireEvent.click(
			findByTestAttr(container, 'component-templateDeleteButton')!
		);
		fireEvent.click(findDialogButton('Cancel')!);

		expect(dispatchSpy).not.toHaveBeenCalled();
	});

	test("calls onSelectTemplate('') when templateProcessing changes to success", () => {
		const onSelectTemplate = jest.fn();
		const store = createTestStore(initialState);
		renderWithStore(
			<TemplateDeleteButton
				onSelectTemplate={onSelectTemplate}
				template={template}
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

		expect(onSelectTemplate).toHaveBeenCalledWith('');
	});

	test('dispatches ADD_TEMPLATE and CLEAR_TEMPLATE_PROCESSING and calls onSelectTemplate when templateProcessing changes to failed', () => {
		const onSelectTemplate = jest.fn();
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		renderWithStore(
			<TemplateDeleteButton
				onSelectTemplate={onSelectTemplate}
				template={template}
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
		expect(onSelectTemplate).toHaveBeenCalledWith('');
		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'CLEAR_TEMPLATE_PROCESSING' })
		);
	});
});
