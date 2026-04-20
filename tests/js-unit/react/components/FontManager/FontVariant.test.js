import React from 'react';
import { render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import FontVariant from '../../../../../src/assets/js/react/components/FontManager/FontVariant';

describe('FontManager - FontVariant.js', () => {
	const props = {
		state: 'addFont',
		fontStyles: {
			regular:
				'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/FiraSans-Regular.ttf',
			italics:
				'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/FiraSans-Italic.ttf',
			bold: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/FiraSans-SemiBold.ttf',
			bolditalics:
				'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/FiraSans-SemiBoldItalic.ttf',
		},
		validateRegular: true,
		onHandleUpload: jest.fn(),
		onHandleDeleteFontStyle: jest.fn(),
		msg: {},
		tabIndex: '146',
	};

	describe('RENDERS COMPONENT', () => {
		test('render <FontVariant /> component', () => {
			const { container } = render(<FontVariant {...props} />);
			expect(
				findByTestAttr(container, 'component-FontVariant')
			).toBeInTheDocument();
		});

		test('render four drop zones', () => {
			const { container } = render(<FontVariant {...props} />);
			expect(container.querySelectorAll('.drop-zone').length).toBe(4);
		});

		test('render add input field', () => {
			const { container } = render(
				<FontVariant {...props} fontStyles={{ regular: '' }} />
			);
			expect(
				findByTestAttr(container, 'component-FontVariant-add')
			).toBeInTheDocument();
		});

		test('render delete input field', () => {
			const { container } = render(<FontVariant {...props} />);
			expect(
				container.querySelectorAll(
					'[data-test="component-FontVariant-delete"]'
				).length
			).toBe(4);
		});

		test('render <FontVariantLabel /> component', () => {
			const { container } = render(<FontVariant {...props} />);
			expect(
				container.querySelectorAll(
					'[data-test="component-FontVariantLabel"]'
				).length
			).toBe(4);
		});
	});
});
