import { act, fireEvent } from '@testing-library/react';
import {
	findByTestAttr,
	renderWithStore,
	createTestStore,
} from '../../testUtilsRTL';
import FontManagerBody from '../../../../../src/assets/js/react/components/FontManager/FontManagerBody';

jest.mock(
	'../../../../../src/assets/js/react/components/FontManager/SearchBox',
	() =>
		function SearchBox() {
			return <div data-test="component-SearchBox" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/components/FontManager/FontList',
	() =>
		function FontList() {
			return <div data-test="component-FontList" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/components/FontManager/AddFont',
	() =>
		function AddFont({ onHandleSubmit }: { onHandleSubmit: jest.Mock }) {
			return (
				<form data-test="component-AddFont" onSubmit={onHandleSubmit}>
					<button type="submit">Submit</button>
				</form>
			);
		}
);

jest.mock(
	'../../../../../src/assets/js/react/components/FontManager/UpdateFont',
	() =>
		function UpdateFont({
			onHandleSubmit,
			onHandleCancelEditFont,
			onHandleCancelEditFontKeypress,
		}: {
			onHandleSubmit: jest.Mock;
			onHandleCancelEditFont: jest.Mock;
			onHandleCancelEditFontKeypress: jest.Mock;
		}) {
			return (
				<form
					data-test="component-UpdateFont"
					onSubmit={onHandleSubmit}
				>
					<button
						type="button"
						data-test="cancel-button"
						onClick={onHandleCancelEditFont}
						onKeyDown={onHandleCancelEditFontKeypress}
					>
						Cancel
					</button>
					<button type="submit">Submit</button>
				</form>
			);
		}
);

jest.mock(
	'../../../../../src/assets/js/react/components/Alert/Alert',
	() =>
		function Alert() {
			return <div data-test="component-Alert" />;
		}
);

jest.mock(
	'../../../../../src/assets/js/react/utilities/FontManager/adjustFontListHeight',
	() => ({
		adjustFontListHeight: jest.fn(),
	})
);

jest.mock('../../../../../src/assets/js/react/api/fontManager', () => ({
	apiGetCustomFontList: jest
		.fn()
		.mockRejectedValue(
			Object.assign(new Error('Aborted'), { name: 'AbortError' })
		),
	apiAddFont: jest.fn(),
	apiEditFont: jest.fn(),
	apiDeleteFont: jest.fn(),
}));

describe('FontManager - FontManagerBody.js', () => {
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
			addFontLoading: true,
			deleteFontLoading: false,
			fontList: [sampleFont],
			searchResult: null,
			selectedFont: 'roboto',
			msg: {
				success: { addFont: 'success' },
				error: { deleteFont: 'error' },
			},
		},
	};

	beforeEach(() => {
		jest.clearAllMocks();
	});

	describe('RUN LIFECYCLE METHODS', () => {
		test('componentDidMount() - dispatches getCustomFontList', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');

			renderWithStore(
				<FontManagerBody activeFontId="" onSelectFont={jest.fn()} />,
				{},
				{},
				store
			);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'GET_CUSTOM_FONT_LIST' })
			);
		});

		test('componentDidMount() - adds .show class to update-font when activeFontId is set', () => {
			document.body.innerHTML = '<div class="update-font"></div>';
			renderWithStore(
				<FontManagerBody
					activeFontId={sampleFont.id}
					onSelectFont={jest.fn()}
				/>,
				initialState
			);

			expect(
				document
					.querySelector('.update-font')
					?.classList.contains('show')
			).toBe(true);
		});

		test('componentDidUpdate() - calls onSelectFont with empty string when id becomes invalid', () => {
			const onSelectFont = jest.fn();
			const store = createTestStore({
				fontManager: { ...initialState.fontManager, fontList: [] },
			});
			const { rerender } = renderWithStore(
				<FontManagerBody
					activeFontId="roboto"
					onSelectFont={onSelectFont}
				/>,
				{},
				{},
				store
			);

			act(() => {
				rerender(
					<FontManagerBody
						activeFontId="arial"
						onSelectFont={onSelectFont}
					/>
				);
			});

			expect(onSelectFont).toHaveBeenCalledWith('');
		});

		test('componentDidUpdate() - loads font details when activeFontId changes to a valid font', () => {
			const store = createTestStore(initialState);
			const { rerender, container } = renderWithStore(
				<FontManagerBody activeFontId="" onSelectFont={jest.fn()} />,
				{},
				{},
				store
			);

			act(() => {
				rerender(
					<FontManagerBody
						activeFontId={sampleFont.id}
						onSelectFont={jest.fn()}
					/>
				);
			});

			/* UpdateFont component receives new props — verify it's rendered */
			expect(
				findByTestAttr(container, 'component-UpdateFont')
			).toBeInTheDocument();
		});
	});

	describe('RUN COMPONENT METHODS', () => {
		test('handleSubmit() - dispatches validationError when add font form has empty label', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<FontManagerBody activeFontId="" onSelectFont={jest.fn()} />,
				{},
				{},
				store
			);

			fireEvent.submit(findByTestAttr(container, 'component-AddFont')!);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'VALIDATION_ERROR' })
			);
		});

		test('handleCancelEditFont() - calls onSelectFont and dispatches clearAddFontMsg', () => {
			const onSelectFont = jest.fn();
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<FontManagerBody
					activeFontId={sampleFont.id}
					onSelectFont={onSelectFont}
				/>,
				{},
				{},
				store
			);

			fireEvent.click(findByTestAttr(container, 'cancel-button')!);

			expect(onSelectFont).toHaveBeenCalledWith('');
			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'CLEAR_ADD_FONT_MSG' })
			);
		});

		test('handleCancelEditFontKeypress() - calls onSelectFont on Enter key', () => {
			const onSelectFont = jest.fn();
			const { container } = renderWithStore(
				<FontManagerBody
					activeFontId={sampleFont.id}
					onSelectFont={onSelectFont}
				/>,
				initialState
			);

			fireEvent.keyDown(findByTestAttr(container, 'cancel-button')!, {
				key: 'Enter',
			});

			expect(onSelectFont).toHaveBeenCalledWith('');
		});

		test('handleSubmit() - dispatches clearAddFontMsg when update form has no changes', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');

			/* Render with activeFontId to trigger handleRequestFontDetails and populate updateFontState */
			const { rerender, container } = renderWithStore(
				<FontManagerBody activeFontId="" onSelectFont={jest.fn()} />,
				{},
				{},
				store
			);

			act(() => {
				rerender(
					<FontManagerBody
						activeFontId={sampleFont.id}
						onSelectFont={jest.fn()}
					/>
				);
			});

			/* Submit the update form — state has the loaded font data, but no changes.
			   This triggers clearAddFontMsg (no changes = cancel the edit) */
			fireEvent.submit(
				findByTestAttr(container, 'component-UpdateFont')!
			);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'CLEAR_ADD_FONT_MSG' })
			);
		});
	});

	describe('RENDERS COMPONENT', () => {
		const defaultProps = {
			activeFontId: '',
			onSelectFont: jest.fn(),
		};

		test('render <FontManagerBody /> component', () => {
			const { container } = renderWithStore(
				<FontManagerBody {...defaultProps} />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-FontManagerBody')
			).toBeInTheDocument();
		});

		test('render <SearchBox /> component', () => {
			const { container } = renderWithStore(
				<FontManagerBody {...defaultProps} />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-SearchBox')
			).toBeInTheDocument();
		});

		test('render <Alert /> component when deleteFont error', () => {
			/* GET_CUSTOM_FONT_LIST resets msg to {} on mount, so dispatch the error after */
			const store = createTestStore(initialState);
			const { container } = renderWithStore(
				<FontManagerBody {...defaultProps} />,
				{},
				{},
				store
			);

			act(() => {
				store.dispatch({
					type: 'DELETE_FONT_ERROR',
					payload: 'delete error',
				});
			});

			expect(
				findByTestAttr(container, 'component-Alert')
			).toBeInTheDocument();
		});

		test('render <FontList /> component', () => {
			const { container } = renderWithStore(
				<FontManagerBody {...defaultProps} />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-FontList')
			).toBeInTheDocument();
		});

		test('render <AddFont /> component', () => {
			const { container } = renderWithStore(
				<FontManagerBody {...defaultProps} />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-AddFont')
			).toBeInTheDocument();
		});

		test('render <UpdateFont /> component', () => {
			const { container } = renderWithStore(
				<FontManagerBody {...defaultProps} />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-UpdateFont')
			).toBeInTheDocument();
		});
	});
});
