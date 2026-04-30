/* Redux action types */
export const GET_CUSTOM_FONT_LIST = 'GET_CUSTOM_FONT_LIST' as const;
export const GET_CUSTOM_FONT_LIST_SUCCESS =
	'GET_CUSTOM_FONT_LIST_SUCCESS' as const;
export const GET_CUSTOM_FONT_LIST_ERROR = 'GET_CUSTOM_FONT_LIST_ERROR' as const;
export const ADD_FONT = 'ADD_FONT' as const;
export const ADD_FONT_SUCCESS = 'ADD_FONT_SUCCESS' as const;
export const ADD_FONT_ERROR = 'ADD_FONT_ERROR' as const;
export const EDIT_FONT = 'EDIT_FONT' as const;
export const EDIT_FONT_SUCCESS = 'EDIT_FONT_SUCCESS' as const;
export const EDIT_FONT_ERROR = 'EDIT_FONT_ERROR' as const;
export const VALIDATION_ERROR = 'VALIDATION_ERROR' as const;
export const DELETE_VARIANT_ERROR = 'DELETE_VARIANT_ERROR' as const;
export const DELETE_FONT = 'DELETE_FONT' as const;
export const DELETE_FONT_SUCCESS = 'DELETE_FONT_SUCCESS' as const;
export const DELETE_FONT_ERROR = 'DELETE_FONT_ERROR' as const;
export const CLEAR_ADD_FONT_MSG = 'CLEAR_ADD_FONT_MSG' as const;
export const CLEAR_DROPZONE_ERROR = 'CLEAR_DROPZONE_ERROR' as const;
export const RESET_SEARCH_RESULT = 'RESET_SEARCH_RESULT' as const;
export const SEARCH_FONT_LIST = 'SEARCH_FONT_LIST' as const;
export const SELECT_FONT = 'SELECT_FONT' as const;
export const MOVE_SELECTED_FONT_TO_TOP = 'MOVE_SELECTED_FONT_TO_TOP' as const;
export const START_EDITING = 'START_EDITING' as const;
export const SET_EDITING_STATE = 'SET_EDITING_STATE' as const;
export const RESET_EDITING_STATE = 'RESET_EDITING_STATE' as const;

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

import { EditingFontState, FontFormData } from '../types';

export const getCustomFontList = () => {
	return {
		type: GET_CUSTOM_FONT_LIST,
	};
};

export const addFont = (font: FontFormData) => {
	return {
		type: ADD_FONT,
		payload: font,
	};
};

export const editFont = (fontDetails: {
	id: string;
	font: Partial<FontFormData>;
}) => {
	return {
		type: EDIT_FONT,
		payload: fontDetails,
	};
};

export const validationError = () => {
	return {
		type: VALIDATION_ERROR,
	};
};

export const deleteVariantError = (fontVariant: string) => {
	return {
		type: DELETE_VARIANT_ERROR,
		payload: fontVariant,
	};
};

export const deleteFont = (id: string) => {
	return {
		type: DELETE_FONT,
		payload: id,
	};
};

export const clearAddFontMsg = () => {
	return {
		type: CLEAR_ADD_FONT_MSG,
	};
};

export const clearDropzoneError = (key: string) => {
	return {
		type: CLEAR_DROPZONE_ERROR,
		payload: key,
	};
};

export const searchFontList = (data: string) => {
	return {
		type: SEARCH_FONT_LIST,
		payload: data,
	};
};

export const resetSearchResult = () => {
	return {
		type: RESET_SEARCH_RESULT,
	};
};

export const selectFont = (fontId: string) => {
	return {
		type: SELECT_FONT,
		payload: fontId,
	};
};

export const moveSelectedFontToTop = (fontId: string) => {
	return {
		type: MOVE_SELECTED_FONT_TO_TOP,
		payload: fontId,
	};
};

/**
 * Start editing — either an existing saved font (by id) or a brand-new
 * unsaved draft when `id` is omitted. The reducer hydrates `editingFont`
 * from `fontList` for the saved case, or initialises an empty draft.
 * @param id
 */
export const startEditing = (id?: string) => {
	return {
		type: START_EDITING,
		payload: id ?? null,
	};
};

export const setEditingState = (state: EditingFontState) => {
	return {
		type: SET_EDITING_STATE,
		payload: state,
	};
};

export const resetEditingState = () => {
	return {
		type: RESET_EDITING_STATE,
	};
};
