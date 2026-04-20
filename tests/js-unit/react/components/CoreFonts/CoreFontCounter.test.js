import React from 'react';
import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import CoreFontCounter from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontCounter';

describe('CoreFonts - CoreFontCounter.js', () => {
	test('renders <CoreFontCounter /> component container', () => {
		const { container } = render(<CoreFontCounter />);
		const component = findByTestAttr(
			container,
			'component-coreFont-counter'
		);

		expect(component).toBeInTheDocument();
	});

	test('display an inline counter', () => {
		const { container } = render(
			<CoreFontCounter text="Fonts remaining:" queue={8} />
		);

		expect(container.querySelector('span').textContent).toBe(
			'Fonts remaining: 8'
		);
	});
});
