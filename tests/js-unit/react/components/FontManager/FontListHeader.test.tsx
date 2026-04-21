import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import FontListHeader from '../../../../../src/assets/js/react/components/FontManager/FontListHeader';

describe('FontManager - FontListHeader.js', () => {
	describe('RENDERS COMPONENT', () => {
		test('render <FontListHeader /> component', () => {
			const { container } = render(<FontListHeader />);
			const component = findByTestAttr(
				container,
				'component-FontListHeader'
			);

			expect(component).toBeInTheDocument();
			expect(component!.querySelector('.font-name')!.textContent).toBe(
				'Installed Fonts'
			);
			expect(component!.querySelectorAll('div')[1].textContent).toBe(
				'Regular'
			);
			expect(component!.querySelectorAll('div')[2].textContent).toBe(
				'Italics'
			);
			expect(component!.querySelectorAll('div')[3].textContent).toBe(
				'Bold'
			);
			expect(component!.querySelectorAll('div')[4].textContent).toBe(
				'Bold Italics'
			);
		});
	});
});
