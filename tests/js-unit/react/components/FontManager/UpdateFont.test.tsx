import { findByTestAttr, renderWithStore } from '../../testUtilsRTL';
import UpdateFont from '../../../../../src/assets/js/react/components/FontManager/UpdateFont';
import type { FontStyles } from '../../../../../src/assets/js/react/components/FontManager/InitialAddUpdateState';
import type {
	FontManagerMsg,
	FontItem,
} from '../../../../../src/assets/js/react/types';

describe('FontManager - UpdateFont.js', () => {
	const props = {
		id: 'firasanslight',
		fontList: [
			{ font_name: 'Fira Sans Light', id: 'firasanslight' } as FontItem,
		],
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
		fontStyles: {} as FontStyles,
		msg: {} as FontManagerMsg,
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
