import { fireEvent } from '@testing-library/react';
import { findByTestAttr, renderWithStore } from '../../testUtilsRTL';
import TemplateListItem from '../../../../../src/assets/js/react/components/Template/TemplateListItem';
import type {
	TemplateItem,
	TemplateState,
} from '../../../../../src/assets/js/react/types';

jest.mock(
	'../../../../../src/assets/js/react/components/Template/TemplateActivateButton',
	() =>
		function TemplateActivateButton() {
			return <div data-test="component-templateActivateButton" />;
		}
);

describe('Template - TemplateListItem.js', () => {
	const template = {
		id: 'zadani',
		template: 'Zadani',
		group: 'Core',
		compatible: true,
	} as TemplateItem;

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

	beforeEach(() => jest.clearAllMocks());

	test('renders <TemplateListItem /> component', () => {
		const { container } = renderWithStore(
			<TemplateListItem
				onSelectTemplate={jest.fn()}
				onClose={jest.fn()}
				template={template}
			/>,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateListItem')
		).toBeInTheDocument();
	});

	test('click calls onSelectTemplate with template id', () => {
		const onSelectTemplate = jest.fn();
		const { container } = renderWithStore(
			<TemplateListItem
				onSelectTemplate={onSelectTemplate}
				onClose={jest.fn()}
				template={template}
			/>,
			initialState
		);
		fireEvent.click(
			findByTestAttr(container, 'component-templateListItem')!
		);
		expect(onSelectTemplate).toHaveBeenCalledWith('zadani');
	});

	test('Enter keydown on non-button element calls onSelectTemplate', () => {
		const onSelectTemplate = jest.fn();
		const { container } = renderWithStore(
			<TemplateListItem
				onSelectTemplate={onSelectTemplate}
				onClose={jest.fn()}
				template={template}
			/>,
			initialState
		);
		const listItem = findByTestAttr(
			container,
			'component-templateListItem'
		);
		fireEvent.keyDown(listItem!, { keyCode: 13 });
		expect(onSelectTemplate).toHaveBeenCalledWith('zadani');
	});

	test('renders <TemplateActivateButton /> when template is compatible and not active', () => {
		const { container } = renderWithStore(
			<TemplateListItem
				onSelectTemplate={jest.fn()}
				onClose={jest.fn()}
				template={template}
			/>,
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
			<TemplateListItem
				onSelectTemplate={jest.fn()}
				onClose={jest.fn()}
				template={template}
			/>,
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
				onSelectTemplate={jest.fn()}
				onClose={jest.fn()}
				template={incompatibleTemplate}
			/>,
			initialState
		);
		expect(
			findByTestAttr(container, 'component-templateActivateButton')
		).not.toBeInTheDocument();
	});
});
