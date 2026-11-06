import reducer, {
	initialState,
} from '../../../../src/assets/js/react/reducers/downloadedFontsReducer';
import {
	GET_DOWNLOADED_FONTS,
	GET_DOWNLOADED_FONTS_SUCCESS,
	GET_DOWNLOADED_FONTS_ERROR,
	DELETE_DOWNLOADED_FONT,
	DELETE_DOWNLOADED_FONT_SUCCESS,
	DELETE_DOWNLOADED_FONT_ERROR,
} from '../../../../src/assets/js/react/actions/downloadedFonts';
import {
	SEARCH_FONT_LIST,
	RESET_SEARCH_RESULT,
} from '../../../../src/assets/js/react/actions/fontManager';

describe('Reducers - downloadedFontsReducer.js', () => {
	const fonts = [{ id: 'garuda' }, { id: 'freeserif' }];

	test('returns the initial state', () => {
		expect(reducer(undefined, { type: 'UNKNOWN' })).toEqual(initialState);
	});

	test('loads the list', () => {
		const loading = reducer(
			{ ...initialState, msg: { error: 'old' } },
			{ type: GET_DOWNLOADED_FONTS }
		);
		expect(loading.loading).toBe(true);
		expect(loading.msg).toEqual({});

		const loaded = reducer(loading, {
			type: GET_DOWNLOADED_FONTS_SUCCESS,
			payload: { fonts, can_delete: true },
		});
		expect(loaded.loading).toBe(false);
		expect(loaded.fonts).toEqual(fonts);
		expect(loaded.canDelete).toBe(true);
	});

	test('records a list error', () => {
		const state = reducer(
			{ ...initialState, loading: true },
			{ type: GET_DOWNLOADED_FONTS_ERROR, payload: 'error' }
		);

		expect(state.loading).toBe(false);
		expect(state.msg).toEqual({ error: 'error' });
	});

	test('removes a deleted font', () => {
		const deleting = reducer(
			{ ...initialState, fonts, msg: { success: 'old' } },
			{ type: DELETE_DOWNLOADED_FONT, payload: { id: 'garuda' } }
		);
		expect(deleting.deletingId).toBe('garuda');
		expect(deleting.msg).toEqual({});

		const deleted = reducer(deleting, {
			type: DELETE_DOWNLOADED_FONT_SUCCESS,
			payload: { id: 'garuda', msg: 'deleted' },
		});
		expect(deleted.deletingId).toBe('');
		expect(deleted.fonts).toEqual([{ id: 'freeserif' }]);
		expect(deleted.msg).toEqual({ success: 'deleted' });
	});

	test('keeps a font whose delete failed', () => {
		const state = reducer(
			{ ...initialState, fonts, deletingId: 'garuda' },
			{ type: DELETE_DOWNLOADED_FONT_ERROR, payload: 'In use' }
		);

		expect(state.deletingId).toBe('');
		expect(state.fonts).toEqual(fonts);
		expect(state.msg).toEqual({ error: 'In use' });
	});

	test('follows the Font Manager search', () => {
		const searched = reducer(initialState, {
			type: SEARCH_FONT_LIST,
			payload: 'thai',
		});
		expect(searched.keyword).toBe('thai');

		expect(reducer(searched, { type: RESET_SEARCH_RESULT }).keyword).toBe(
			''
		);
	});
});
