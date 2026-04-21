import { render, fireEvent } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import AdvancedButton from '../../../../../src/assets/js/react/components/FontManager/AdvancedButton';

describe('FontManager - AdvancedButton.js', () => {
	const navigate = jest.fn();

	describe('RENDERS COMPONENT', () => {
		test('render <AdvancedButton /> component', () => {
			const { container } = render(
				<AdvancedButton navigate={navigate} />
			);
			const component = findByTestAttr(
				container,
				'component-AdvancedButton'
			);

			expect(component).toBeInTheDocument();
			expect(component!.textContent).toBe('Advanced');
		});
	});

	describe('RUN COMPONENT METHODS', () => {
		test('clicking the button navigates to /fontmanager/', () => {
			const { container } = render(
				<AdvancedButton navigate={navigate} />
			);
			fireEvent.click(
				findByTestAttr(container, 'component-AdvancedButton')!
			);
			expect(navigate).toHaveBeenCalledWith('/fontmanager/');
		});
	});
});
