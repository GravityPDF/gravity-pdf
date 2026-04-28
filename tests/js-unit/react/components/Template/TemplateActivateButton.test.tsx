import { fireEvent } from '@testing-library/react';
import {
	findByTestAttr,
	renderWithStore,
	createTestStore,
} from '../../testUtilsRTL';
import TemplateActivateButton from '../../../../../src/assets/js/react/components/Template/TemplateActivateButton';
import type { TemplateItem } from '../../../../../src/assets/js/react/types';

describe('Template - TemplateActivateButton.js', () => {
	const template = { id: 'zadani' } as TemplateItem;

	beforeEach(() => {
		jest.clearAllMocks();
	});

	test('renders <TemplateActivateButton /> component', () => {
		const { container } = renderWithStore(
			<TemplateActivateButton onClose={jest.fn()} template={template} />
		);
		expect(
			findByTestAttr(container, 'component-templateActivateButton')
		).toBeInTheDocument();
	});

	test('renders button text', () => {
		const { container } = renderWithStore(
			<TemplateActivateButton onClose={jest.fn()} template={template} />
		);
		expect(container.querySelector('button')!.textContent).toBe('Select');
	});

	test('handleSelectTemplate() - calls onClose and dispatches selectTemplate', () => {
		const onClose = jest.fn();
		const store = createTestStore({});
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		const { container } = renderWithStore(
			<TemplateActivateButton onClose={onClose} template={template} />,
			{},
			{},
			store
		);

		fireEvent.click(
			findByTestAttr(container, 'component-templateActivateButton')!
		);

		expect(onClose).toHaveBeenCalledTimes(1);
		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'SELECT_TEMPLATE' })
		);
	});
});
