import { fireEvent } from '@testing-library/react';
import {
	findByTestAttr,
	renderWithStore,
	createTestStore,
} from '../../testUtilsRTL';
import TemplateActivateButton from '../../../../../src/assets/js/react/components/Template/TemplateActivateButton';
import type { TemplateItem } from '../../../../../src/assets/js/react/types';

describe('Template - TemplateActivateButton.js', () => {
	const navigate = jest.fn();
	const template = { id: 'zadani' } as TemplateItem;

	beforeEach(() => {
		jest.clearAllMocks();
	});

	test('renders <TemplateActivateButton /> component', () => {
		const { container } = renderWithStore(
			<TemplateActivateButton navigate={navigate} template={template} />
		);
		expect(
			findByTestAttr(container, 'component-templateActivateButton')
		).toBeInTheDocument();
	});

	test('renders button text', () => {
		const { container } = renderWithStore(
			<TemplateActivateButton
				navigate={navigate}
				template={template}
				buttonText="Select"
			/>
		);
		expect(container.querySelector('button')!.textContent).toBe('Select');
	});

	test('handleSelectTemplate() - calls navigate and dispatches selectTemplate', () => {
		const store = createTestStore({});
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		const { container } = renderWithStore(
			<TemplateActivateButton navigate={navigate} template={template} />,
			{},
			{},
			store
		);

		fireEvent.click(
			findByTestAttr(container, 'component-templateActivateButton')!
		);

		expect(navigate).toHaveBeenCalledWith('/');
		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'SELECT_TEMPLATE' })
		);
	});
});
