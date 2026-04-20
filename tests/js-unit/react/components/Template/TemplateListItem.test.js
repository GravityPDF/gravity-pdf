import React from 'react';
import { fireEvent } from '@testing-library/react';
import { findByTestAttr, renderWithStore } from '../../testUtilsRTL';
import TemplateListItem from '../../../../../src/assets/js/react/components/Template/TemplateListItem';

jest.mock(
	'../../../../../src/assets/js/react/components/Template/TemplateActivateButton',
	() =>
		function TemplateActivateButton() {
			return <div data-test="component-templateActivateButton" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/utilities/withRouterHooks',
	() => (Component) => Component
);

describe('Template - TemplateListItem.js', () => {
	const navigate = jest.fn();
	const template = {
		id: 'zadani',
		template: 'Zadani',
		group: 'Core',
		compatible: true,
	};

	const initialState = {
		template: {
			list: [template],
			activeTemplate: '',
			search: '',
			updateSelectBoxText: '',
			templateProcessing: '',
			templateUploadProcessingSuccess: {},
			templateUploadProcessingError: {},
		},
	};

	beforeEach(() => jest.clearAllMocks());

	test('renders <TemplateListItem /> component', () => {
		const { container } = renderWithStore(
			<TemplateListItem navigate={navigate} template={template} />,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateListItem')
		).toBeInTheDocument();
	});

	test('click navigates to template detail page', () => {
		const { container } = renderWithStore(
			<TemplateListItem navigate={navigate} template={template} />,
			initialState
		);
		fireEvent.click(
			findByTestAttr(container, 'component-templateListItem')
		);
		expect(navigate).toHaveBeenCalledWith('/template/zadani');
	});

	test('Enter keydown on non-button element navigates to template detail page', () => {
		const { container } = renderWithStore(
			<TemplateListItem navigate={navigate} template={template} />,
			initialState
		);
		const listItem = findByTestAttr(
			container,
			'component-templateListItem'
		);
		fireEvent.keyDown(listItem, { keyCode: 13 });
		expect(navigate).toHaveBeenCalledWith('/template/zadani');
	});

	test('renders <TemplateActivateButton /> when template is compatible and not active', () => {
		const { container } = renderWithStore(
			<TemplateListItem navigate={navigate} template={template} />,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateActivateButton')
		).toBeInTheDocument();
	});

	test('does not render <TemplateActivateButton /> when template is the active template', () => {
		const activeState = {
			...initialState,
			template: { ...initialState.template, activeTemplate: 'zadani' },
		};
		const { container } = renderWithStore(
			<TemplateListItem navigate={navigate} template={template} />,
			activeState
		);
		expect(
			findByTestAttr(container, 'component-templateActivateButton')
		).not.toBeInTheDocument();
	});

	test('does not render <TemplateActivateButton /> when template is incompatible', () => {
		const incompatibleTemplate = { ...template, compatible: false };
		const { container } = renderWithStore(
			<TemplateListItem
				navigate={navigate}
				template={incompatibleTemplate}
			/>,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateActivateButton')
		).not.toBeInTheDocument();
	});
});
