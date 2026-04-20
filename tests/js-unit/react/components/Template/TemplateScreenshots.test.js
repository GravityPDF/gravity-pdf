import React from 'react';
import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import TemplateScreenshots from '../../../../../src/assets/js/react/components/Template/TemplateScreenshots';

describe('Template - TemplateScreenshots.js', () => {
	test('renders <TemplateScreenshots /> component and image', () => {
		const { container } = render(<TemplateScreenshots image="test.png" />);
		expect(
			findByTestAttr(container, 'component-templateScreenshots')
		).toBeInTheDocument();
		expect(container.querySelector('img')).toBeInTheDocument();
	});
});
