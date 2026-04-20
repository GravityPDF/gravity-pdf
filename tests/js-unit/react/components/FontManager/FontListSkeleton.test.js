import React from 'react';
import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import FontListSkeleton from '../../../../../src/assets/js/react/components/FontManager/FontListSkeleton';

describe('FontManager - FontListSkeleton.js', () => {
	describe('RENDERS COMPONENT', () => {
		test('render <FontListSkeleton /> component', () => {
			const { container } = render(<FontListSkeleton />);
			expect(
				findByTestAttr(container, 'component-FontListSkeleton')
			).toBeInTheDocument();
		});
	});
});
