import { act, fireEvent, waitFor, within } from '@testing-library/react';
import {
	createTestStore,
	findByTestAttr,
	renderWithStore,
	type TestStore,
} from '../../testUtilsRTL';
import FontManagerModal from '../../../../../src/assets/js/react/components/FontManager/FontManagerModal';
import type { FontItem } from '../../../../../src/assets/js/react/types';

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

const sample: FontItem = {
	id: 'roboto',
	font_name: 'Roboto',
	regular: 'paths/Roboto-Regular.ttf',
	italics: '',
	bold: '',
	bolditalics: '',
};

/**
 * `getCustomFontList` is dispatched on mount and the test mock rejects
 * with AbortError, leaving `loading: true` in the store. Manually push a
 * SUCCESS so the listbox renders rather than showing the loading spinner.
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

describe('FontManager - FontManagerModal', () => {
	test('mounts and renders the new modal layout', () => {
		const { container } = renderWithStore(
			<FontManagerModal tabLocation="tools" />
		);
		expect(
			findByTestAttr(container, 'component-FontManagerModal')
		).toBeInTheDocument();
		expect(
			findByTestAttr(container, 'component-FontSidebar')
		).toBeInTheDocument();
		expect(
			findByTestAttr(container, 'component-FontDetail')
		).toBeInTheDocument();
	});

	test('Add new font button starts editing a fresh draft', () => {
		const store = createTestStore({
			fontManager: { fontList: [sample], selectedFont: 'roboto' },
		});
		const { container } = renderWithStore(
			<FontManagerModal tabLocation="tools" />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [sample]);
		const sidebar = findByTestAttr(container, 'component-FontSidebar');
		fireEvent.click(
			within(sidebar!).getByRole('button', { name: /add new font/i })
		);
		expect(store.getState().fontManager.editingFont?.isDraft).toBe(true);
	});

	test('Selecting a font from the list starts editing the saved record', () => {
		const store = createTestStore({
			fontManager: { fontList: [sample] },
		});
		const { getByRole } = renderWithStore(
			<FontManagerModal tabLocation="tools" />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [sample]);
		fireEvent.click(getByRole('option', { name: /roboto/i }));
		expect(store.getState().fontManager.editingFont).toMatchObject({
			id: 'roboto',
			isDraft: false,
			label: 'Roboto',
		});
	});

	test('Switching from a draft to another font drops the draft', () => {
		const store = createTestStore({
			fontManager: {
				fontList: [sample],
				editingFont: {
					id: 'draft-123',
					isDraft: true,
					label: 'New Font',
					fontStyles: {
						regular: '',
						italics: '',
						bold: '',
						bolditalics: '',
					},
				},
			},
		});
		const { getByRole } = renderWithStore(
			<FontManagerModal tabLocation="tools" />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [sample]);
		fireEvent.click(getByRole('option', { name: /roboto/i }));
		const editing = store.getState().fontManager.editingFont;
		expect(editing?.id).toBe('roboto');
		expect(editing?.isDraft).toBe(false);
	});

	test('Cancel on an unsaved draft clears editing state', () => {
		const store = createTestStore({
			fontManager: {
				fontList: [],
				editingFont: {
					id: 'draft-123',
					isDraft: true,
					label: 'Roboto',
					fontStyles: {
						regular: new File(
							[new Uint8Array(0)],
							'Roboto-Regular.ttf'
						),
						italics: '',
						bold: '',
						bolditalics: '',
					},
				},
			},
		});
		const { getByRole } = renderWithStore(
			<FontManagerModal tabLocation="tools" />,
			{},
			{},
			store
		);
		flipLoadingOff(store, []);
		fireEvent.click(getByRole('button', { name: 'Cancel' }));
		expect(store.getState().fontManager.editingFont).toBeNull();
	});

	test('Set as active dispatches SELECT_FONT', () => {
		const store = createTestStore({
			fontManager: {
				fontList: [sample],
				selectedFont: '',
				editingFont: {
					id: 'roboto',
					isDraft: false,
					label: 'Roboto',
					fontStyles: {
						regular: 'paths/Roboto-Regular.ttf',
						italics: '',
						bold: '',
						bolditalics: '',
					},
				},
			},
		});
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		const { getByRole } = renderWithStore(
			<FontManagerModal tabLocation="tools" />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [sample]);
		fireEvent.click(getByRole('button', { name: /set as active/i }));
		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'SELECT_FONT', payload: 'roboto' })
		);
	});

	test('saving with a destructive file change opens SaveReplaceDialog', async () => {
		const store = createTestStore({
			fontManager: {
				fontList: [sample],
				editingFont: {
					id: 'roboto',
					isDraft: false,
					label: 'Roboto',
					fontStyles: {
						regular: new File(
							[new Uint8Array(0)],
							'Replacement-Regular.ttf'
						),
						italics: '',
						bold: '',
						bolditalics: '',
					},
				},
			},
		});
		const { findByText, getByRole } = renderWithStore(
			<FontManagerModal tabLocation="tools" />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [sample]);
		fireEvent.click(getByRole('button', { name: /save changes/i }));
		expect(
			await findByText(/this will apply to all PDFs using this font/i)
		).toBeInTheDocument();
	});

	test('on mount it dispatches GET_CUSTOM_FONT_LIST', () => {
		const store = createTestStore({
			fontManager: { fontList: [] },
		});
		const dispatchSpy = jest.spyOn(store, 'dispatch');
		renderWithStore(
			<FontManagerModal tabLocation="tools" />,
			{},
			{},
			store
		);
		expect(dispatchSpy).toHaveBeenCalledWith(
			expect.objectContaining({ type: 'GET_CUSTOM_FONT_LIST' })
		);
	});

	test('mobile single-pane: clicking a list row switches the data attribute to detail', () => {
		const store = createTestStore({
			fontManager: { fontList: [sample] },
		});
		const { getByRole, container } = renderWithStore(
			<FontManagerModal tabLocation="tools" />,
			{},
			{},
			store
		);
		flipLoadingOff(store, [sample]);
		const body = container.querySelector('.gfpdf-fm-body') as HTMLElement;
		expect(body).toHaveAttribute('data-mobile-view', 'list');
		act(() => {
			fireEvent.click(getByRole('option', { name: /roboto/i }));
		});
		expect(body).toHaveAttribute('data-mobile-view', 'detail');
	});
});
