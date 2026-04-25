import { act, fireEvent } from '@testing-library/react';
import {
	findByTestAttr,
	renderWithStore,
	createTestStore,
} from '../../testUtilsRTL';
import FontListItems from '../../../../../src/assets/js/react/components/FontManager/FontListItems';

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

	const defaultProps = {
		activeFontId: '',
		onSelectFont: jest.fn(),
		hasDetailOpen: false,
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

			renderWithStore(<FontListItems {...defaultProps} />, {}, {}, store);

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
				<FontListItems {...defaultProps} />,
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
				<FontListItems {...defaultProps} />,
				{},
				{},
				store
			);

			/* Trigger the delete flow: click trash -> ConfirmDialog opens,
			   click OK -> deleteId is set and deleteFont dispatched. */
			fireEvent.click(container.querySelector('.dashicons-trash')!);
			const okButton = Array.from(
				document.body.querySelectorAll<HTMLButtonElement>(
					'.components-modal__frame button'
				)
			).find((b) => b.textContent === 'OK')!;
			fireEvent.click(okButton);

			/* DELETE_FONT_SUCCESS flips deleteFontLoading off, which clears deleteId */
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

		test('componentDidUpdate() - calls onSelectFont("") when the active font is removed from the list', () => {
			const onSelectFont = jest.fn();
			const store = createTestStore({
				fontManager: {
					...initialState.fontManager,
					deleteFontLoading: true,
				},
			});
			renderWithStore(
				<FontListItems
					activeFontId={sampleFont.id}
					onSelectFont={onSelectFont}
					hasDetailOpen={true}
				/>,
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

			expect(onSelectFont).toHaveBeenCalledWith('');
		});

		test('componentDidUpdate() - does not call onSelectFont when the active font is just edited', () => {
			const onSelectFont = jest.fn();
			const store = createTestStore({
				fontManager: {
					...initialState.fontManager,
					deleteFontLoading: false,
				},
			});
			renderWithStore(
				<FontListItems
					activeFontId={sampleFont.id}
					onSelectFont={onSelectFont}
					hasDetailOpen={true}
				/>,
				{},
				{},
				store
			);

			act(() => {
				store.dispatch({
					type: 'EDIT_FONT_SUCCESS',
					payload: {
						font: { ...sampleFont, font_name: 'Fira Sans Bold' },
						msg: 'saved',
					},
				});
			});

			expect(onSelectFont).not.toHaveBeenCalledWith('');
		});
	});

	describe('RUN COMPONENT METHODS', () => {
		test('handleFontClick() - dispatches clearAddFontMsg and calls onSelectFont', () => {
			const onSelectFont = jest.fn();
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<FontListItems
					activeFontId=""
					onSelectFont={onSelectFont}
					hasDetailOpen={false}
				/>,
				{},
				{},
				store
			);

			fireEvent.click(container.querySelector('.font-list-item')!);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'CLEAR_ADD_FONT_MSG' })
			);
			expect(onSelectFont).toHaveBeenCalledWith(sampleFont.id);
		});

		test('handleFontClickKeypress() - calls onSelectFont on Enter key', () => {
			const onSelectFont = jest.fn();
			const store = createTestStore(initialState);
			const { container } = renderWithStore(
				<FontListItems
					activeFontId=""
					onSelectFont={onSelectFont}
					hasDetailOpen={false}
				/>,
				{},
				{},
				store
			);

			fireEvent.keyDown(container.querySelector('.font-list-item')!, {
				key: 'Enter',
			});

			expect(onSelectFont).toHaveBeenCalledWith(sampleFont.id);
		});

		test('handleDeleteFont() - dispatches deleteFont when ConfirmDialog OK clicked', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<FontListItems {...defaultProps} />,
				{},
				{},
				store
			);

			fireEvent.click(container.querySelector('.dashicons-trash')!);

			const okButton = Array.from(
				document.body.querySelectorAll<HTMLButtonElement>(
					'.components-modal__frame button'
				)
			).find((b) => b.textContent === 'OK')!;
			fireEvent.click(okButton);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'DELETE_FONT' })
			);
		});

		test('handleDeleteFontKeypress() - dispatches deleteFont on Enter when OK clicked', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<FontListItems {...defaultProps} />,
				{},
				{},
				store
			);

			fireEvent.keyDown(container.querySelector('.dashicons-trash')!, {
				key: 'Enter',
				stopPropagation: jest.fn(),
			});

			const okButton = Array.from(
				document.body.querySelectorAll<HTMLButtonElement>(
					'.components-modal__frame button'
				)
			).find((b) => b.textContent === 'OK')!;
			fireEvent.click(okButton);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'DELETE_FONT' })
			);
		});

		test('handleSelectFont() - dispatches selectFont on radio change', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<FontListItems {...defaultProps} />,
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
				<FontListItems {...defaultProps} />,
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
				<FontListItems {...defaultProps} />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-FontListItems')
			).toBeInTheDocument();
		});

		test('render delete trash icon', () => {
			const { container } = renderWithStore(
				<FontListItems {...defaultProps} />,
				initialState
			);
			expect(
				container.querySelector('span.dashicons-trash')
			).toBeInTheDocument();
		});

		test('render radio button for select font name', () => {
			const { container } = renderWithStore(
				<FontListItems {...defaultProps} />,
				initialState
			);
			const name = 'select-font-name-' + sampleFont.id;
			expect(
				container.querySelector(`input[name="${name}"]`)
			).toBeInTheDocument();
		});

		test('render font name', () => {
			const { container } = renderWithStore(
				<FontListItems {...defaultProps} />,
				initialState
			);
			expect(
				container.querySelector('span.font-name')?.textContent
			).toContain('Fira Sans Light');
		});

		test('render four font-variant icons per row', () => {
			const { container } = renderWithStore(
				<FontListItems {...defaultProps} />,
				initialState
			);
			expect(
				container.querySelectorAll(
					'.font-list-item .dashicons-yes, .font-list-item .dashicons-no-alt'
				)
			).toHaveLength(4);
		});
	});
});
