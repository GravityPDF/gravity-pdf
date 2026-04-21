import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import FontVariantLabel from '../../../../../src/assets/js/react/components/FontManager/FontVariantLabel';

describe('FontManager - FontVariantLabel.js', () => {
	describe('RENDERS COMPONENT', () => {
		test('render <FontVariantLabel /> component', () => {
			const { container } = render(
				<FontVariantLabel label="regular" font="false" />
			);
			const component = findByTestAttr(
				container,
				'component-FontVariantLabel'
			);

			expect(component).toBeInTheDocument();
			expect(container.querySelector('span')!.textContent).toBe(
				'Regular (required)'
			);
		});
	});
});
