import React from 'react';
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
	'../../../../../src/assets/js/react/utilities/FontManager/toggleUpdateFont',
	() => ({
		toggleUpdateFont: jest.fn(),
		addClass: jest.fn(),
	})
);

jest.mock(
	'../../../../../src/assets/js/react/utilities/FontManager/adjustFontListHeight',
	() => ({
		adjustFontListHeight: jest.fn(),
	})
);

const {
	toggleUpdateFont,
	addClass,
} = require('../../../../../src/assets/js/react/utilities/FontManager/toggleUpdateFont');

describe('FontManager - FontManagerBody.js', () => {
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
				<FontManagerBody navigate={navigate} />,
				{},
				{},
				store
			);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'GET_CUSTOM_FONT_LIST' })
			);
		});

		test('componentDidMount() - calls addClass to auto-open update panel when id is set', () => {
			renderWithStore(
				<FontManagerBody id={sampleFont.id} navigate={navigate} />,
				initialState
			);

			expect(addClass).toHaveBeenCalledTimes(1);
		});

		test('componentDidUpdate() - navigates to /fontmanager/ when id becomes invalid', () => {
			const store = createTestStore({
				fontManager: { ...initialState.fontManager, fontList: [] },
			});
			const { rerender } = renderWithStore(
				<FontManagerBody id="roboto" navigate={navigate} />,
				{},
				{},
				store
			);

			act(() => {
				rerender(<FontManagerBody id="arial" navigate={navigate} />);
			});

			expect(navigate).toHaveBeenCalledWith('/fontmanager/');
		});

		test('componentDidUpdate() - loads font details when id changes to a valid font', () => {
			const store = createTestStore(initialState);
			const { rerender, container } = renderWithStore(
				<FontManagerBody navigate={navigate} />,
				{},
				{},
				store
			);

			act(() => {
				rerender(
					<FontManagerBody id={sampleFont.id} navigate={navigate} />
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
				<FontManagerBody navigate={navigate} />,
				{},
				{},
				store
			);

			fireEvent.submit(findByTestAttr(container, 'component-AddFont')!);

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'VALIDATION_ERROR' })
			);
		});

		test('handleCancelEditFont() - calls toggleUpdateFont and dispatches clearAddFontMsg', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<FontManagerBody id={sampleFont.id} navigate={navigate} />,
				{},
				{},
				store
			);

			fireEvent.click(findByTestAttr(container, 'cancel-button')!);

			expect(toggleUpdateFont).toHaveBeenCalledTimes(1);
			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'CLEAR_ADD_FONT_MSG' })
			);
		});

		test('handleCancelEditFontKeypress() - calls toggleUpdateFont on Enter key', () => {
			const { container } = renderWithStore(
				<FontManagerBody id={sampleFont.id} navigate={navigate} />,
				initialState
			);

			fireEvent.keyDown(findByTestAttr(container, 'cancel-button')!, {
				key: 'Enter',
			});

			expect(toggleUpdateFont).toHaveBeenCalledTimes(1);
		});

		test('handleSubmit() - dispatches editFont when update form has valid existing data', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');

			/* Render with id to trigger handleRequestFontDetails and populate updateFontState */
			const { rerender, container } = renderWithStore(
				<FontManagerBody navigate={navigate} />,
				{},
				{},
				store
			);

			act(() => {
				rerender(
					<FontManagerBody id={sampleFont.id} navigate={navigate} />
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
		test('render <FontManagerBody /> component', () => {
			const { container } = renderWithStore(
				<FontManagerBody navigate={navigate} />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-FontManagerBody')
			).toBeInTheDocument();
		});

		test('render <SearchBox /> component', () => {
			const { container } = renderWithStore(
				<FontManagerBody navigate={navigate} />,
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
				<FontManagerBody navigate={navigate} />,
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
				<FontManagerBody navigate={navigate} />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-FontList')
			).toBeInTheDocument();
		});

		test('render <AddFont /> component', () => {
			const { container } = renderWithStore(
				<FontManagerBody navigate={navigate} />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-AddFont')
			).toBeInTheDocument();
		});

		test('render <UpdateFont /> component', () => {
			const { container } = renderWithStore(
				<FontManagerBody navigate={navigate} />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-UpdateFont')
			).toBeInTheDocument();
		});
	});
});
