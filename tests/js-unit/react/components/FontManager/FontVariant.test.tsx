import * as React from '@wordpress/element';
import { fireEvent, render } from '@testing-library/react';
import { findByTestAttr } from '../../testUtilsRTL';
import FontVariant from '../../../../../src/assets/js/react/components/FontManager/FontVariant';
import type { FontStyles } from '../../../../../src/assets/js/react/components/FontManager/InitialAddUpdateState';

jest.mock('@wordpress/components', () => ({
	...jest.requireActual('@wordpress/components'),
	DropZone: ({ onFilesDrop }: { onFilesDrop?: (files: File[]) => void }) => (
		<button
			data-test="drop-ttf"
			onClick={() =>
				onFilesDrop?.([
					new File([''], 'replace.ttf', { type: 'font/ttf' }),
				])
			}
		/>
	),
	FormFileUpload: ({
		render: renderProp,
	}: {
		render?: (arg: { openFileDialog: () => void }) => React.ReactNode;
	}) =>
		renderProp ? <>{renderProp({ openFileDialog: jest.fn() })}</> : null,
}));

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
		tabIndex: 0,
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
				<FontVariant
					{...props}
					fontStyles={{ regular: '' } as FontStyles}
				/>
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

	describe('REPLACE-IN-PLACE BEHAVIOUR', () => {
		test('dropping a .ttf on a filled tile calls onHandleUpload', () => {
			const onHandleUpload = jest.fn();
			const { container } = render(
				<FontVariant {...props} onHandleUpload={onHandleUpload} />
			);

			const dropTriggers = container.querySelectorAll(
				'[data-test="drop-ttf"]'
			);
			expect(dropTriggers.length).toBe(4);

			fireEvent.click(dropTriggers[0]);

			expect(onHandleUpload).toHaveBeenCalledTimes(1);
			expect(onHandleUpload).toHaveBeenCalledWith(
				'regular',
				expect.any(File),
				'addFont'
			);
		});
	});
});
