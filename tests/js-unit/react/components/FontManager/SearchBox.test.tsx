import { act, fireEvent } from '@testing-library/react';
import {
	findByTestAttr,
	renderWithStore,
	createTestStore,
} from '../../testUtilsRTL';
import SearchBox from '../../../../../src/assets/js/react/components/FontManager/SearchBox';
import type { FontItem } from '../../../../../src/assets/js/react/types';

describe('FontManager - SearchBox.js', () => {
	const initialState = {
		fontManager: {
			loading: false,
			addFontLoading: false,
			deleteFontLoading: false,
			fontList: [],
			searchResult: null,
			selectedFont: '',
			msg: {},
		},
	};

	describe('RENDERS COMPONENT', () => {
		test('render <SearchBox /> component', () => {
			const { container } = renderWithStore(
				<SearchBox id="arial" />,
				initialState
			);
			expect(
				findByTestAttr(container, 'component-SearchBox')
			).toBeInTheDocument();
		});
	});

	describe('RUN LIFECYCLE METHODS', () => {
		test('resets search input when searchResult changes to null', () => {
			const store = createTestStore({
				fontManager: {
					...initialState.fontManager,
					searchResult: [{ id: 'arial' } as FontItem],
				},
			});
			const { container } = renderWithStore(
				<SearchBox id="arial" />,
				{},
				{},
				store
			);
			const input = findByTestAttr(
				container,
				'component-SearchBox'
			) as HTMLInputElement;

			fireEvent.change(input, { target: { value: 'arial' } });
			expect(input.value).toBe('arial');

			act(() => {
				store.dispatch({ type: 'RESET_SEARCH_RESULT' });
			});

			expect(input.value).toBe('');
		});

		test('dispatches resetSearchResult on unmount when search input is not empty', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container, unmount } = renderWithStore(
				<SearchBox id="arial" />,
				{},
				{},
				store
			);

			fireEvent.change(
				findByTestAttr(container, 'component-SearchBox')!,
				{
					target: { value: 'arial' },
				}
			);

			unmount();

			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'RESET_SEARCH_RESULT' })
			);
		});

		test('does not dispatch resetSearchResult on unmount when search input is empty', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { unmount } = renderWithStore(
				<SearchBox id="arial" />,
				{},
				{},
				store
			);

			unmount();

			expect(dispatchSpy).not.toHaveBeenCalledWith(
				expect.objectContaining({ type: 'RESET_SEARCH_RESULT' })
			);
		});
	});

	describe('RUN COMPONENT METHODS', () => {
		test('handleSearch() - updates input value and dispatches searchFontList', () => {
			const store = createTestStore(initialState);
			const dispatchSpy = jest.spyOn(store, 'dispatch');
			const { container } = renderWithStore(
				<SearchBox id="arial" />,
				{},
				{},
				store
			);

			fireEvent.change(
				findByTestAttr(container, 'component-SearchBox')!,
				{
					target: { value: 'arial' },
				}
			);

			expect(
				(
					findByTestAttr(
						container,
						'component-SearchBox'
					) as HTMLInputElement
				).value
			).toBe('arial');
			expect(dispatchSpy).toHaveBeenCalledWith(
				expect.objectContaining({ type: 'SEARCH_FONT_LIST' })
			);
		});
	});
});
