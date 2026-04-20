import React from 'react';
import { fireEvent } from '@testing-library/react';
import { findByTestAttr, renderWithStore } from '../../testUtilsRTL';
import AddFont from '../../../../../src/assets/js/react/components/FontManager/AddFont';

describe('FontManager - AddFont.js', () => {
	const props = {
		label: '',
		onHandleInputChange: jest.fn(),
		onHandleUpload: jest.fn(),
		onHandleDeleteFontStyle: jest.fn(),
		onHandleSubmit: jest.fn(),
		validateLabel: false,
		validateRegular: false,
		fontStyles: {},
		msg: {},
		loading: false,
		tabIndexFontName: '',
		tabIndexFontFiles: '',
		tabIndexFooterButtons: '',
	};

	describe('RENDERS COMPONENT', () => {
		test('render <AddFont /> component', () => {
			const { container } = renderWithStore(<AddFont {...props} />);
			expect(
				findByTestAttr(container, 'component-AddFont')
			).toBeInTheDocument();
		});

		test('render font name input box', () => {
			const { container } = renderWithStore(<AddFont {...props} />);
			expect(
				container.querySelector('input#gfpdf-add-font-name-input')
			).toBeInTheDocument();
		});

		test('call input box onChange event', () => {
			const onHandleInputChange = jest.fn();
			const { container } = renderWithStore(
				<AddFont {...props} onHandleInputChange={onHandleInputChange} />
			);
			fireEvent.change(
				container.querySelector('input#gfpdf-add-font-name-input'),
				{ target: { value: 'Your new Value' } }
			);
			expect(onHandleInputChange).toHaveBeenCalledTimes(1);
		});

		test('render font name validation error', () => {
			const { container } = renderWithStore(<AddFont {...props} />);
			expect(
				container.querySelector('span.required[role="alert"]')
			).toBeInTheDocument();
		});

		test('hide font name validation error', () => {
			const { container } = renderWithStore(
				<AddFont {...props} validateLabel={true} />
			);
			expect(
				container.querySelector('span.required[role="alert"]')
			).not.toBeInTheDocument();
		});

		test('render font files label text', () => {
			const { container } = renderWithStore(<AddFont {...props} />);
			const labels = container.querySelectorAll('label');
			expect(labels[1].textContent).toBe('Font Files');
		});

		test('render <FontVariant /> component', () => {
			const { container } = renderWithStore(<AddFont {...props} />);
			expect(
				findByTestAttr(container, 'component-FontVariant')
			).toBeInTheDocument();
		});

		test('render <AddUpdateFontFooter /> component', () => {
			const { container } = renderWithStore(<AddFont {...props} />);
			expect(
				findByTestAttr(container, 'component-AddFontFooter')
			).toBeInTheDocument();
		});
	});
});
