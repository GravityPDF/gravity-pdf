import React from 'react';
import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import TemplateHeaderTitle from '../../../../../src/assets/js/react/components/Template/TemplateHeaderTitle';

describe('Template - TemplateHeaderTitle.js', () => {
	test('renders <TemplateHeaderTitle /> component', () => {
		const { container } = render(
			<TemplateHeaderTitle header="Sample Text" />
		);
		expect(
			findByTestAttr(container, 'component-templateHeaderTitle')
		).toBeInTheDocument();
	});

	test('renders component text', () => {
		const { container } = render(
			<TemplateHeaderTitle header="Sample Text" />
		);
		expect(
			findByTestAttr(container, 'component-templateHeaderTitle')!
				.textContent
		).toBe('Sample Text');
	});
});
