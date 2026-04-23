import { render, fireEvent } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import TemplateButton from '../../../../../src/assets/js/react/components/Template/TemplateButton';

describe('Template - TemplateButton.js', () => {
	const onOpen = jest.fn();

	beforeEach(() => {
		jest.clearAllMocks();
	});

	test('renders <TemplateButton /> component', () => {
		const { container } = render(<TemplateButton onOpen={onOpen} />);
		expect(
			findByTestAttr(container, 'component-templateButton')
		).toBeInTheDocument();
	});

	test('renders button text', () => {
		const { container } = render(<TemplateButton onOpen={onOpen} />);
		expect(container.querySelector('button')!.textContent).toBe('Manage');
	});

	test('handleClick() - calls onOpen', () => {
		const { container } = render(<TemplateButton onOpen={onOpen} />);
		fireEvent.click(container.querySelector('button')!);
		expect(onOpen).toHaveBeenCalledTimes(1);
	});
});
