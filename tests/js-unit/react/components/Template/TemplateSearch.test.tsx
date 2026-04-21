import { fireEvent } from '@testing-library/react';
import {
	findByTestAttr,
	renderWithStore,
	createTestStore,
} from '../../testUtilsRTL';
import TemplateSearch from '../../../../../src/assets/js/react/components/Template/TemplateSearch';

jest.mock('lodash.debounce', () => (fn: (...args: unknown[]) => unknown) => fn);

describe('Template - TemplateSearch.js', () => {
	const initialState = {
		template: {
			list: [],
			activeTemplate: '',
			search: '',
			updateSelectBoxText: '',
			templateProcessing: '',
			templateUploadProcessingSuccess: {},
			templateUploadProcessingError: {},
		},
	};

	test('renders <TemplateSearch /> component', () => {
		const { container } = renderWithStore(<TemplateSearch />, initialState);
		expect(
			findByTestAttr(container, 'component-templateSearch')
		).toBeInTheDocument();
	});

	test('renders search input element', () => {
		const { container } = renderWithStore(<TemplateSearch />, initialState);
		expect(
			container.querySelector('input[type="search"]')
		).toBeInTheDocument();
	});

	test('dispatches SEARCH_TEMPLATES action on input change', () => {
		const store = createTestStore(initialState);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		const { container } = renderWithStore(
			<TemplateSearch />,
			{},
			{},
			store
		);

		fireEvent.change(container.querySelector('input')!, {
			target: { value: 'zadani' },
		});

		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({
				type: 'SEARCH_TEMPLATES',
				text: 'zadani',
			})
		);
	});

	test('dispatches SEARCH_TEMPLATES with empty string when input is cleared', () => {
		const stateWithSearch = {
			...initialState,
			template: { ...initialState.template, search: 'rubix' },
		};
		const store = createTestStore(stateWithSearch);
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		const { container } = renderWithStore(
			<TemplateSearch />,
			{},
			{},
			store
		);

		fireEvent.change(container.querySelector('input')!, {
			target: { value: '' },
		});

		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'SEARCH_TEMPLATES', text: '' })
		);
	});
});
