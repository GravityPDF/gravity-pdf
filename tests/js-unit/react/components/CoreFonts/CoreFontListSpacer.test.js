import React from 'react';
import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import CoreFontListSpacer from '../../../../../src/assets/js/react/components/CoreFonts/CoreFontListSpacer';

describe('CoreFonts - CoreFontListSpacer.js', () => {
	test('renders <CoreFontListSpacer /> component container', () => {
		const { container } = render(<CoreFontListSpacer />);
		expect(
			findByTestAttr(container, 'component-coreFontList-spacer')
		).toBeInTheDocument();
	});

	test('display spacer content', () => {
		const { container } = render(<CoreFontListSpacer />);
		expect(container.querySelector('div').textContent).toBe('---');
	});
});
