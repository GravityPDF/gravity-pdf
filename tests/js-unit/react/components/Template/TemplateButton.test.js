import React from 'react';
import { render, fireEvent } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import TemplateButton from '../../../../../src/assets/js/react/components/Template/TemplateButton';

describe('Template - TemplateButton.js', () => {
	const navigate = jest.fn();

	beforeEach(() => {
		jest.clearAllMocks();
	});

	test('renders <TemplateButton /> component', () => {
		const { container } = render(<TemplateButton navigate={navigate} />);
		expect(
			findByTestAttr(container, 'component-templateButton')
		).toBeInTheDocument();
	});

	test('renders button text', () => {
		const { container } = render(<TemplateButton navigate={navigate} />);
		expect(container.querySelector('button').textContent).toBe(
			GFPDF.manage
		);
	});

	test('handleClick() - calls navigate with template route', () => {
		const { container } = render(<TemplateButton navigate={navigate} />);
		fireEvent.click(container.querySelector('button'));
		expect(navigate).toHaveBeenCalledWith('template');
	});
});
