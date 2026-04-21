import React from 'react';
import { findByTestAttr, renderWithRouter } from '../../testUtilsRTL';
import FontManagerHeader from '../../../../../src/assets/js/react/components/FontManager/FontManagerHeader';

describe('FontManager - FontManagerHeader.js', () => {
	describe('RENDERS COMPONENT', () => {
		test('render <FontManagerHeader /> component', () => {
			const { container } = renderWithRouter(
				<FontManagerHeader id="rubix" />
			);
			expect(
				findByTestAttr(container, 'component-FontManagerHeader')
			).toBeInTheDocument();
		});

		test('render <CloseDialog /> component', () => {
			const { container } = renderWithRouter(
				<FontManagerHeader id="rubix" />
			);
			expect(
				findByTestAttr(container, 'component-CloseDialog')
			).toBeInTheDocument();
		});
	});
});
