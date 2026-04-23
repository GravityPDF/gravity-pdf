import { render, fireEvent } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import AdvancedButton from '../../../../../src/assets/js/react/components/FontManager/AdvancedButton';

describe('FontManager - AdvancedButton.js', () => {
	const onOpen = jest.fn();

	describe('RENDERS COMPONENT', () => {
		test('render <AdvancedButton /> component', () => {
			const { container } = render(<AdvancedButton onOpen={onOpen} />);
			const component = findByTestAttr(
				container,
				'component-AdvancedButton'
			);

			expect(component).toBeInTheDocument();
			expect(component!.textContent).toBe('Manage');
		});
	});

	describe('RUN COMPONENT METHODS', () => {
		test('clicking the button calls onOpen', () => {
			const { container } = render(<AdvancedButton onOpen={onOpen} />);
			fireEvent.click(
				findByTestAttr(container, 'component-AdvancedButton')!
			);
			expect(onOpen).toHaveBeenCalledTimes(1);
		});
	});
});
