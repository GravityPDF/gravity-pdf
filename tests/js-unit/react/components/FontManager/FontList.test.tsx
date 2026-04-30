import { act, fireEvent } from '@testing-library/react';
import {
	createTestStore,
	findByTestAttr,
	renderWithStore,
	type TestStore,
} from '../../testUtilsRTL';
import FontList from '../../../../../src/assets/js/react/components/FontManager/FontList';
import type { FontItem } from '../../../../../src/assets/js/react/types';

const fontA: FontItem = {
	id: 'lato',
	font_name: 'Lato',
	regular: 'paths/Lato-Regular.ttf',
	italics: '',
	bold: '',
	bolditalics: '',
};

const fontB: FontItem = {
	id: 'montserrat',
	font_name: 'Montserrat',
	regular: 'paths/Montserrat-Regular.ttf',
	italics: '',
	bold: '',
	bolditalics: '',
};

const fontC: FontItem = {
	id: 'open-sans',
	font_name: 'Open Sans',
	regular: 'paths/OpenSans-Regular.ttf',
	italics: '',
	bold: '',
	bolditalics: '',
};

/**
 * Drop the loading flag the store sets on mount so the listbox renders.
 * @param store
 * @param fontList
 */
function flipLoadingOff(store: TestStore, fontList: FontItem[]): void {
	act(() => {
		store.dispatch({
			type: 'GET_CUSTOM_FONT_LIST_SUCCESS',
			payload: fontList,
		});
	});
}

describe('FontManager - FontList', () => {
	test('renders one role="option" per font with WAI-ARIA listbox semantics', () => {
		const store = createTestStore({
			fontManager: { fontList: [fontA, fontB] },
		});
		const { container, getAllByRole } = renderWithStore(
			<FontList onSelect={jest.fn()} />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [fontA, fontB]);
		expect(
			findByTestAttr(container, 'component-FontList')
		).toBeInTheDocument();
		const listbox = container.querySelector('[role="listbox"]');
		expect(listbox).toHaveAttribute('aria-label', 'Installed Fonts');
		expect(getAllByRole('option')).toHaveLength(2);
	});

	test('clicking a row calls onSelect with that font id', () => {
		const onSelect = jest.fn();
		const store = createTestStore({
			fontManager: { fontList: [fontA, fontB] },
		});
		const { getByRole } = renderWithStore(
			<FontList onSelect={onSelect} />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [fontA, fontB]);
		fireEvent.click(getByRole('option', { name: /montserrat/i }));
		expect(onSelect).toHaveBeenCalledWith('montserrat');
	});

	test('ArrowDown moves the active descendant to the next id', () => {
		const onSelect = jest.fn();
		const store = createTestStore({
			fontManager: {
				fontList: [fontA, fontB, fontC],
				editingFont: {
					id: 'lato',
					isDraft: false,
					label: 'Lato',
					fontStyles: {
						regular: 'paths/Lato-Regular.ttf',
						italics: '',
						bold: '',
						bolditalics: '',
					},
				},
			},
		});
		const { container } = renderWithStore(
			<FontList onSelect={onSelect} />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [fontA, fontB, fontC]);
		const listbox = container.querySelector(
			'[role="listbox"]'
		) as HTMLElement;
		fireEvent.keyDown(listbox, { key: 'ArrowDown' });
		expect(onSelect).toHaveBeenCalledWith('montserrat');
	});

	test('Home / End jump to first / last id', () => {
		const onSelect = jest.fn();
		const store = createTestStore({
			fontManager: {
				fontList: [fontA, fontB, fontC],
				editingFont: {
					id: 'montserrat',
					isDraft: false,
					label: 'Montserrat',
					fontStyles: {
						regular: 'paths/Montserrat-Regular.ttf',
						italics: '',
						bold: '',
						bolditalics: '',
					},
				},
			},
		});
		const { container } = renderWithStore(
			<FontList onSelect={onSelect} />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [fontA, fontB, fontC]);
		const listbox = container.querySelector(
			'[role="listbox"]'
		) as HTMLElement;
		fireEvent.keyDown(listbox, { key: 'Home' });
		expect(onSelect).toHaveBeenLastCalledWith('lato');
		fireEvent.keyDown(listbox, { key: 'End' });
		expect(onSelect).toHaveBeenLastCalledWith('open-sans');
	});

	test('type-ahead jumps to the next font whose name starts with the typed letter', () => {
		const onSelect = jest.fn();
		const store = createTestStore({
			fontManager: {
				fontList: [fontA, fontB, fontC],
				editingFont: {
					id: 'lato',
					isDraft: false,
					label: 'Lato',
					fontStyles: {
						regular: 'paths/Lato-Regular.ttf',
						italics: '',
						bold: '',
						bolditalics: '',
					},
				},
			},
		});
		const { container } = renderWithStore(
			<FontList onSelect={onSelect} />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [fontA, fontB, fontC]);
		const listbox = container.querySelector(
			'[role="listbox"]'
		) as HTMLElement;
		fireEvent.keyDown(listbox, { key: 'M' });
		expect(onSelect).toHaveBeenLastCalledWith('montserrat');
	});

	test('shows "No results." when search returns an empty list', () => {
		const store = createTestStore({
			fontManager: { fontList: [fontA, fontB], searchResult: [] },
		});
		const { getByText } = renderWithStore(
			<FontList onSelect={jest.fn()} />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [fontA, fontB]);
		expect(getByText('No results.')).toBeInTheDocument();
	});

	test('shows the empty-state message when no fonts are installed', () => {
		const store = createTestStore({ fontManager: { fontList: [] } });
		const { getByText } = renderWithStore(
			<FontList onSelect={jest.fn()} />,
			{},
			{},
			store
		);
		flipLoadingOff(store, []);
		expect(getByText('No custom fonts installed yet.')).toBeInTheDocument();
	});

	test('renders an unsaved draft as the first option', () => {
		const store = createTestStore({
			fontManager: {
				fontList: [fontA],
				editingFont: {
					id: 'draft-1',
					isDraft: true,
					label: 'My New Font',
					fontStyles: {
						regular: '',
						italics: '',
						bold: '',
						bolditalics: '',
					},
				},
			},
		});
		const { getAllByRole } = renderWithStore(
			<FontList onSelect={jest.fn()} />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [fontA]);
		const options = getAllByRole('option');
		expect(options).toHaveLength(2);
		expect(options[0]).toHaveTextContent('My New Font');
	});
});
