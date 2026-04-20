import React from 'react';
import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import FontListIcon from '../../../../../src/assets/js/react/components/FontManager/FontListIcon';

describe('FontManager - FontListIcon.js', () => {
	describe('RENDERS COMPONENT', () => {
		test('render <FontListIcon /> component', () => {
			const { container } = render(<FontListIcon font="" />);
			expect(
				findByTestAttr(container, 'component-FontListIcon')
			).toBeInTheDocument();
		});

		test('render "check" icon', () => {
			const { container } = render(<FontListIcon font="arial" />);
			expect(
				container.querySelector('span.dashicons-yes')
			).toBeInTheDocument();
		});

		test('render "x" icon', () => {
			const { container } = render(<FontListIcon font="" />);
			expect(
				container.querySelector('span.dashicons-no-alt')
			).toBeInTheDocument();
		});
	});
});
