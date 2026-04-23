import { act, fireEvent } from '@testing-library/react';
import {
	findByTestAttr,
	renderWithStore,
	createTestStore,
} from '../../testUtilsRTL';
import FontListItems from '../../../../../src/assets/js/react/components/FontManager/FontListItems';

jest.mock(
	'../../../../../src/assets/js/react/utilities/FontManager/toggleUpdateFont',
	() => ({
		toggleUpdateFont: jest.fn(),
	})
);

const {
	toggleUpdateFont,
} = require('../../../../../src/assets/js/react/utilities/FontManager/toggleUpdateFont');

jest.mock('../../../../../src/assets/js/react/api/fontManager', () => ({
	apiGetCustomFontList: jest.fn(() => new Promise(() => {})),
	apiAddFont: jest.fn(() => new Promise(() => {})),
	apiEditFont: jest.fn(() => new Promise(() => {})),
	apiDeleteFont: jest
		.fn()
		.mockRejectedValue(
			Object.assign(new Error('Aborted'), { name: 'AbortError' })
		),
}));

describe('FontManager - FontListItems.js', () => {
	const navigate = jest.fn();

	const sampleFont = {
		font_name: 'Fira Sans Light',
		id: 'firasanslight',
		regular: 'FiraSans-Light.ttf',
		italics: 'FiraSans-LightItalic.ttf',
		bold: 'FiraSans-Medium.ttf',
		bolditalics: 'FiraSans-MediumItalic.ttf',
	};

	const initialState = {
		fontManager: {
			loading: false,
			addFontLoading: false,
			deleteFontLoading: false,
			fontList: [sampleFont],
			searchResult: null,
			selectedFont: 'roboto',
			msg: { success: { addFont: 'success' } },
		},
	};

	/* FontListItems' mount effect calls document.querySelector on this element */
	beforeEach(() => {
		document.body.innerHTML = '<select id="gfpdf_settings[font]"></select>';
		jest.clearAllMocks();
	});

	afterEach(() => {
		document.body.innerHTML = '';
		/* Restore window.location if a test replaced it */
		if (window.location.search !== '') {
			delete (window as unknown as { location?: unknown }).location;
			(window as unknown as { location: unknown }).location = {
				search: '',
			};
		}
	});

	describe('RUN LIFECYCLE METHODS', () => {
		test('componentDidMount() - dispatches moveSelectedFontToTop when selectedFont is set', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');

			renderWithStore(
				<FontListItems navigate={navigate} />,
				{},
				{},
				store
			);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'MOVE_SELECTED_FONT_TO_TOP' })
			);
		});

		test('componentDidMount() - sets disableSelectFontName when under tools tab', () => {
			delete (window as unknown as { location?: unknown }).location;
			(window as unknown as { location: unknown }).location = {
				search: '?page=gf_settings&subview=PDF&tab=tools',
			};

			const store = createTestStore({
				fontManager: { ...initialState.fontManager, selectedFont: '' },
			});
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				{},
				{},
				store
			);

			expect(
				container.querySelector('input[type="radio"]')
			).not.toBeInTheDocument();
		});

		test('componentDidUpdate() - resets deleteId when loading finishes', () => {
			const store = createTestStore({
				fontManager: {
					...initialState.fontManager,
					deleteFontLoading: true,
				},
			});
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				{},
				{},
				store
			);

			/* Simulate delete to set deleteId */
			global.confirm = () => true;
			fireEvent.click(container.querySelector('.dashicons-trash')!);

			/* Finish loading — spinner should be showing now */
			act(() => {
				store.dispatch({
					type: 'DELETE_FONT_SUCCESS',
					payload: { id: sampleFont.id },
				});
			});

			/* After loading finishes, spinner should go away */
			expect(
				container.querySelector('.gfpdf-spinner')
			).not.toBeInTheDocument();
		});

		test('componentDidUpdate() - calls toggleUpdateFont after font deletion', () => {
			document.body.innerHTML += '<div class="update-font show"></div>';

			const store = createTestStore({
				fontManager: {
					...initialState.fontManager,
					deleteFontLoading: true,
				},
			});
			renderWithStore(
				<FontListItems navigate={navigate} />,
				{},
				{},
				store
			);

			act(() => {
				store.dispatch({
					type: 'DELETE_FONT_SUCCESS',
					payload: sampleFont.id,
				});
			});

			expect(toggleUpdateFont).toHaveBeenCalled();
		});
	});

	describe('RUN COMPONENT METHODS', () => {
		test('handleFontClick() - dispatches clearAddFontMsg and calls toggleUpdateFont', () => {
			document.body.innerHTML += '<div class="update-font show"></div>';

			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				{},
				{},
				store
			);

			fireEvent.click(container.querySelector('.font-list-item')!);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'CLEAR_ADD_FONT_MSG' })
			);
			expect(toggleUpdateFont).toHaveBeenCalled();
		});

		test('handleFontClickKeypress() - calls handleFontClick on Enter key', () => {
			const store = createTestStore(initialState);
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				{},
				{},
				store
			);

			fireEvent.keyDown(container.querySelector('.font-list-item')!, {
				key: 'Enter',
			});

			expect(toggleUpdateFont).toHaveBeenCalled();
		});

		test('handleDeleteFont() - dispatches deleteFont when confirmed', () => {
			global.confirm = () => true;
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				{},
				{},
				store
			);

			fireEvent.click(container.querySelector('.dashicons-trash')!);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'DELETE_FONT' })
			);
		});

		test('handleDeleteFontKeypress() - dispatches deleteFont on Enter key when confirmed', () => {
			global.confirm = () => true;
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				{},
				{},
				store
			);

			fireEvent.keyDown(container.querySelector('.dashicons-trash')!, {
				key: 'Enter',
				stopPropagation: jest.fn(),
			});

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'DELETE_FONT' })
			);
		});

		test('handleSelectFont() - dispatches selectFont on radio change', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				{},
				{},
				store
			);

			const radio = container.querySelector('input[type="radio"]')!;
			fireEvent.change(radio, { target: { value: sampleFont.id } });

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'SELECT_FONT' })
			);
		});

		test('handleSelectFontKeypress() - dispatches selectFont on Enter key', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				{},
				{},
				store
			);

			const radio = container.querySelector('input[type="radio"]')!;
			fireEvent.keyDown(radio, {
				key: 'Enter',
				preventDefault: jest.fn(),
				stopPropagation: jest.fn(),
				target: { value: sampleFont.id },
			});

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'SELECT_FONT' })
			);
		});
	});

	describe('RENDERS COMPONENT', () => {
		test('render <FontListItems /> component', () => {
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-FontListItems')
			).toBeInTheDocument();
		});

		test('render delete trash icon', () => {
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				initialState
			);
			expect(
				container.querySelector('span.dashicons-trash')
			).toBeInTheDocument();
		});

		test('render radio button for select font name', () => {
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				initialState
			);
			const name = 'select-font-name-' + sampleFont.id;
			expect(
				container.querySelector(`input[name="${name}"]`)
			).toBeInTheDocument();
		});

		test('render font name', () => {
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				initialState
			);
			expect(
				container.querySelector('span.font-name')?.textContent
			).toContain('Fira Sans Light');
		});

		test('render <FontListIcon /> components', () => {
			const { container } = renderWithStore(
				<FontListItems navigate={navigate} />,
				initialState
			);
			expect(
				container.querySelectorAll(
					'[data-test="component-FontListIcon"]'
				)
			).toHaveLength(4);
		});
	});
});
