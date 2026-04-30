import {
	getCustomFontList,
	GET_CUSTOM_FONT_LIST,
	addFont,
	ADD_FONT,
	editFont,
	EDIT_FONT,
	validationError,
	VALIDATION_ERROR,
	deleteVariantError,
	DELETE_VARIANT_ERROR,
	deleteFont,
	DELETE_FONT,
	clearAddFontMsg,
	CLEAR_ADD_FONT_MSG,
	clearDropzoneError,
	CLEAR_DROPZONE_ERROR,
	searchFontList,
	SEARCH_FONT_LIST,
	resetSearchResult,
	RESET_SEARCH_RESULT,
	selectFont,
	SELECT_FONT,
	moveSelectedFontToTop,
	startEditing,
	START_EDITING,
	setEditingState,
	SET_EDITING_STATE,
	resetEditingState,
	RESET_EDITING_STATE,
} from '../../../../src/assets/js/react/actions/fontManager';
import type { EditingFontState } from '../../../../src/assets/js/react/types';

describe('Redux actions - fontManager.js', () => {
	let results;

	test('getCustomFontList - check if it returns the correct action', () => {
		results = getCustomFontList();

		expect(results.type).toEqual(GET_CUSTOM_FONT_LIST);
	});

	test('addFont - check if it returns the correct action', () => {
		results = addFont(
			'gotham' as unknown as import('../../../../src/assets/js/react/types').FontFormData
		);

		expect(results.type).toEqual(ADD_FONT);
		expect(results.payload).toBe('gotham');
	});

	test('editFont - check if it returns the correct action', () => {
		results = editFont({} as unknown as Parameters<typeof editFont>[0]);

		expect(results.type).toEqual(EDIT_FONT);
		expect(results.payload).toEqual({});
	});

	test('validationError - check if it returns the correct action', () => {
		results = validationError();

		expect(results.type).toEqual(VALIDATION_ERROR);
	});

	test('deleteVariantError - check if it returns the correct action', () => {
		results = deleteVariantError('italic');

		expect(results.type).toEqual(DELETE_VARIANT_ERROR);
		expect(results.payload).toBe('italic');
	});

	test('deleteFont - check if it returns the correct action', () => {
		results = deleteFont('gotham');

		expect(results.type).toEqual(DELETE_FONT);
		expect(results.payload).toBe('gotham');
	});

	test('clearAddFontMsg - check if it returns the correct action', () => {
		results = clearAddFontMsg();

		expect(results.type).toEqual(CLEAR_ADD_FONT_MSG);
	});

	test('clearDropzoneError - check if it returns the correct action', () => {
		results = clearDropzoneError('error');

		expect(results.type).toEqual(CLEAR_DROPZONE_ERROR);
		expect(results.payload).toEqual('error');
	});

	test('searchFontList - check if it returns the correct action', () => {
		results = searchFontList('Gotham');

		expect(results.type).toEqual(SEARCH_FONT_LIST);
		expect(results.payload).toEqual('Gotham');
	});

	test('resetSearchResult - check if it returns the correct action', () => {
		results = resetSearchResult();

		expect(results.type).toEqual(RESET_SEARCH_RESULT);
	});

	test('selectFont - check if it returns the correct action', () => {
		results = selectFont('gotham');

		expect(results.type).toEqual(SELECT_FONT);
		expect(results.payload).toBe('gotham');
	});

	test('moveSelectedFontToTop - check if it returns the correct action', () => {
		results = moveSelectedFontToTop('roboto');

		expect(results.type).toEqual('MOVE_SELECTED_FONT_TO_TOP');
		expect(results.payload).toBe('roboto');
	});

	test('startEditing - returns null payload when called without an id', () => {
		const action = startEditing();
		expect(action.type).toEqual(START_EDITING);
		expect(action.payload).toBeNull();
	});

	test('startEditing - returns the id payload when called with an id', () => {
		const action = startEditing('roboto');
		expect(action.type).toEqual(START_EDITING);
		expect(action.payload).toBe('roboto');
	});

	test('setEditingState - returns the action with the editing state payload', () => {
		const editing: EditingFontState = {
			id: 'roboto',
			isDraft: false,
			label: 'Roboto',
			fontStyles: {
				regular: 'roboto.ttf',
				italics: '',
				bold: '',
				bolditalics: '',
			},
		};
		const action = setEditingState(editing);
		expect(action.type).toEqual(SET_EDITING_STATE);
		expect(action.payload).toEqual(editing);
	});

	test('resetEditingState - returns the reset action', () => {
		const action = resetEditingState();
		expect(action.type).toEqual(RESET_EDITING_STATE);
	});
});
