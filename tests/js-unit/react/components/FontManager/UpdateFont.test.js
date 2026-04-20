import React from 'react';
import { findByTestAttr, renderWithStore } from '../../testUtilsRTL';
import UpdateFont from '../../../../../src/assets/js/react/components/FontManager/UpdateFont';

describe('FontManager - UpdateFont.js', () => {
	const props = {
		id: 'firasanslight',
		fontList: [{ font_name: 'Fira Sans Light', id: 'firasanslight' }],
		label: '',
		onHandleInputChange: jest.fn(),
		onHandleUpload: jest.fn(),
		onHandleDeleteFontStyle: jest.fn(),
		onHandleCancelEditFont: jest.fn(),
		onHandleCancelEditFontKeypress: jest.fn(),
		onHandleSubmit: jest.fn(),
		validateLabel: false,
		validateRegular: false,
		disableUpdateButton: false,
		fontStyles: {},
		msg: {},
		loading: false,
		tabIndexFontName: '',
		tabIndexFontFiles: '',
		tabIndexFooterButtons: '',
	};

	describe('RENDERS COMPONENT', () => {
		test('render <UpdateFont /> component', () => {
			const { container } = renderWithStore(<UpdateFont {...props} />);
			expect(
				findByTestAttr(container, 'component-UpdateFont')
			).toBeInTheDocument();
		});

		test('render font name input box', () => {
			const { container } = renderWithStore(<UpdateFont {...props} />);
			expect(
				container.querySelector('input#gfpdf-update-font-name-input')
			).toBeInTheDocument();
		});

		test('render font name validation error', () => {
			const { container } = renderWithStore(<UpdateFont {...props} />);
			expect(
				container.querySelector('span.required[role="alert"]')
			).toBeInTheDocument();
		});

		test('render <FontVariant /> component', () => {
			const { container } = renderWithStore(<UpdateFont {...props} />);
			expect(
				findByTestAttr(container, 'component-FontVariant')
			).toBeInTheDocument();
		});

		test('render <AddUpdateFontFooter /> component', () => {
			const { container } = renderWithStore(<UpdateFont {...props} />);
			expect(
				findByTestAttr(container, 'component-AddFontFooter')
			).toBeInTheDocument();
		});
	});
});
