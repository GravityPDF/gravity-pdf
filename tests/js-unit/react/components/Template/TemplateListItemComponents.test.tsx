import React from 'react';
import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import {
	TemplateDetails,
	Group,
} from '../../../../../src/assets/js/react/components/Template/TemplateListItemComponents';

describe('Template - TemplateListItemComponents.js', () => {
	test('renders <TemplateDetails /> component and text', () => {
		const { container } = render(<TemplateDetails label="Label Text" />);
		const component = findByTestAttr(
			container,
			'component-templateDetails'
		);
		expect(component).toBeInTheDocument();
		expect(component!.textContent).toBe('Label Text');
	});

	test('renders <Group /> component and text', () => {
		const { container } = render(<Group group="Group Text" />);
		const component = findByTestAttr(container, 'component-group');
		expect(component).toBeInTheDocument();
		expect(component!.textContent).toBe('Group Text');
	});
});
