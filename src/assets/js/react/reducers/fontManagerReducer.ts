/* Dependencies */
import { createSlice, PayloadAction } from '@reduxjs/toolkit';
import { sprintf } from 'sprintf-js';
/* Redux action types */
import {
	GET_CUSTOM_FONT_LIST,
	GET_CUSTOM_FONT_LIST_SUCCESS,
	GET_CUSTOM_FONT_LIST_ERROR,
	ADD_FONT,
	ADD_FONT_SUCCESS,
	ADD_FONT_ERROR,
	EDIT_FONT,
	EDIT_FONT_ERROR,
	EDIT_FONT_SUCCESS,
	VALIDATION_ERROR,
	DELETE_VARIANT_ERROR,
	DELETE_FONT,
	DELETE_FONT_SUCCESS,
	DELETE_FONT_ERROR,
	CLEAR_ADD_FONT_MSG,
	CLEAR_DROPZONE_ERROR,
	RESET_SEARCH_RESULT,
	SEARCH_FONT_LIST,
	SELECT_FONT,
	MOVE_SELECTED_FONT_TO_TOP,
} from '../actions/fontManager';
/* Utilities */
import {
	findAndRemove,
	reduceFontFileName,
	checkFontListIncludes,
	clearMsg,
} from '../utilities/FontManager/fontManagerReducer';
/* Types */
import { FontItem, FontManagerState } from '../types';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

export const initialState: FontManagerState = {
	loading: false,
	addFontLoading: false,
	deleteFontLoading: false,
	fontList: [],
	searchResult: null,
	selectedFont: '',
	msg: {},
};

const fontManagerSlice = createSlice({
	name: 'fontManager',
	initialState,
	reducers: {},
	extraReducers: (builder) => {
		builder
			.addCase(GET_CUSTOM_FONT_LIST, (state: FontManagerState) => ({
				...state,
				loading: true,
				msg: {},
			}))
			.addCase(
				GET_CUSTOM_FONT_LIST_SUCCESS,
				(state: FontManagerState, action) => {
					const { payload } = action as unknown as PayloadAction<
						FontItem[]
					>;
					return {
						...state,
						loading: false,
						fontList: payload,
					};
				}
			)
			.addCase(
				GET_CUSTOM_FONT_LIST_ERROR,
				(state: FontManagerState, action) => {
					const { payload } =
						action as unknown as PayloadAction<string>;
					return {
						...state,
						loading: false,
						msg: { error: { fontList: payload } },
					};
				}
			)
			.addCase(ADD_FONT, (state: FontManagerState) => {
				const msg = { ...state.msg };

				/* Clear previous fontValidation error msg */
				if (msg.error && msg.error.fontValidationError) {
					delete msg.error.fontValidationError;
				}

				return {
					...state,
					addFontLoading: true,
					msg: clearMsg({ ...msg }),
				};
			})
			.addCase(ADD_FONT_SUCCESS, (state: FontManagerState, action) => {
				const { payload } = action as unknown as PayloadAction<{
					font: FontItem;
					msg: string;
				}>;

				if (state.msg.error && state.msg.error.fontList) {
					return {
						...state,
						addFontLoading: false,
						msg: {
							...state.msg,
							success: { addFont: payload.msg },
						},
					};
				}

				const updatedFontList = [...state.fontList, payload.font];

				return {
					...state,
					addFontLoading: false,
					fontList: updatedFontList,
					searchResult: state.searchResult ? updatedFontList : null,
					msg: { success: { addFont: payload.msg } },
				};
			})
			.addCase(ADD_FONT_ERROR, (state: FontManagerState, action) => {
				const { payload } = action as unknown as PayloadAction<{
					msg?: string;
					fontValidationError?: string;
					[key: string]: string | undefined;
				}>;
				let msg;

				msg = {
					...state.msg,
					error: {
						...state.msg.error,
						addFont: payload as unknown as Record<string, string>,
					},
				};

				if (payload.fontValidationError) {
					msg = {
						...state.msg,
						error: {
							...state.msg.error,
							addFont: payload.msg,
							// %s is found inside fontValidationError
							// eslint-disable-next-line @wordpress/valid-sprintf
							fontValidationError: sprintf(
								payload.fontValidationError,
								'<strong>',
								'</strong>'
							),
						},
					};
				}

				/* Clear deleteFont error msg */
				if (msg.error && msg.error.deleteFont) {
					delete msg.error.deleteFont;
				}

				return {
					...state,
					addFontLoading: false,
					msg,
				};
			})
			.addCase(EDIT_FONT, (state: FontManagerState) => {
				const msg = { ...state.msg };

				/* Clear previous success msg */
				if (msg.success) {
					delete msg.success;
				}

				/* Clear previous addFont error msg */
				if (msg.error && msg.error.addFont) {
					delete msg.error.addFont;
				}

				/* Clear previous fontValidation error msg */
				if (msg.error && msg.error.fontValidationError) {
					delete msg.error.fontValidationError;
				}

				return {
					...state,
					addFontLoading: true,
					msg,
				};
			})
			.addCase(EDIT_FONT_ERROR, (state: FontManagerState, action) => {
				const { payload } = action as unknown as PayloadAction<{
					msg?: string;
					fontValidationError?: string;
					[key: string]: string | undefined;
				}>;
				let msg;

				msg = {
					...state.msg,
					error: {
						...state.msg.error,
						addFont: payload as unknown as Record<string, string>,
					},
				};

				if (payload.fontValidationError) {
					msg = {
						...state.msg,
						error: {
							...state.msg.error,
							addFont: payload.msg,
							// %s is found inside fontValidationError
							// eslint-disable-next-line @wordpress/valid-sprintf
							fontValidationError: sprintf(
								payload.fontValidationError,
								'<strong>',
								'</strong>'
							),
						},
					};
				}

				/* Clear deleteFont error msg */
				if (msg.error && msg.error.deleteFont) {
					delete msg.error.deleteFont;
				}

				return {
					...state,
					addFontLoading: false,
					msg,
				};
			})
			.addCase(EDIT_FONT_SUCCESS, (state: FontManagerState, action) => {
				const { font, msg } = (
					action as unknown as PayloadAction<{
						font: FontItem;
						msg: string;
					}>
				).payload;
				state.addFontLoading = false;
				state.msg = { success: { addFont: msg } };

				const applyUpdate = (list: FontItem[]) => {
					const item = list.find((f) => f.id === font.id);
					if (item) {
						item.font_name = font.font_name;
						item.regular = font.regular;
						item.italics = font.italics;
						item.bold = font.bold;
						item.bolditalics = font.bolditalics;
					}
				};

				applyUpdate(state.fontList);
				if (state.searchResult) {
					applyUpdate(state.searchResult);
				}
			})
			.addCase(VALIDATION_ERROR, (state: FontManagerState) => ({
				...state,
				msg: {
					error: {
						...(state.msg.error ?? {}),
						// %s is found inside addUpdateFontError
						// eslint-disable-next-line @wordpress/valid-sprintf
						addFont: sprintf(
							GFPDF.addUpdateFontError,
							'<strong>',
							'</strong>'
						),
					},
				},
			}))
			.addCase(
				DELETE_VARIANT_ERROR,
				(state: FontManagerState, action) => {
					const { payload } =
						action as unknown as PayloadAction<string>;
					const addFont = {
						...(state.msg.error?.addFont as Record<string, string>),
					};
					delete addFont[payload];

					return {
						...state,
						msg: { error: { ...state.msg.error, addFont } },
					};
				}
			)
			.addCase(DELETE_FONT, (state: FontManagerState) => {
				const msg = { ...state.msg };

				/* Clear previous success msg */
				if (msg.success) {
					delete msg.success;
				}

				/* Clear previous deleteFont error msg */
				if (msg.error && msg.error.deleteFont) {
					delete msg.error.deleteFont;
				}

				return {
					...state,
					deleteFontLoading: true,
					msg,
				};
			})
			.addCase(DELETE_FONT_SUCCESS, (state: FontManagerState, action) => {
				const { payload } = action as unknown as PayloadAction<string>;

				/* Delete from the list during active search */
				if (state.searchResult) {
					return {
						...state,
						deleteFontLoading: false,
						fontList: findAndRemove([...state.fontList], payload),
						searchResult:
							findAndRemove([...state.searchResult], payload)
								.length === 0
								? null
								: findAndRemove(
										[...state.searchResult],
										payload
									),
					};
				}

				return {
					...state,
					deleteFontLoading: false,
					fontList: findAndRemove([...state.fontList], payload),
				};
			})
			.addCase(DELETE_FONT_ERROR, (state: FontManagerState, action) => {
				const { payload } = action as unknown as PayloadAction<string>;
				return {
					...state,
					deleteFontLoading: false,
					msg: {
						...state.msg,
						error: { ...state.msg.error, deleteFont: payload },
					},
				};
			})
			.addCase(CLEAR_ADD_FONT_MSG, (state: FontManagerState) => ({
				...state,
				msg: clearMsg({ ...state.msg }),
			}))
			.addCase(
				CLEAR_DROPZONE_ERROR,
				(state: FontManagerState, action) => {
					const { payload } =
						action as unknown as PayloadAction<string>;
					const addFont = state.msg.error?.addFont;
					if (typeof addFont === 'object' && addFont !== null) {
						delete (
							state.msg.error!.addFont as Record<string, string>
						)[payload];
					}
				}
			)
			.addCase(RESET_SEARCH_RESULT, (state: FontManagerState) => ({
				...state,
				searchResult: null,
			}))
			.addCase(SEARCH_FONT_LIST, (state: FontManagerState, action) => {
				const { payload } = action as unknown as PayloadAction<string>;
				const fontList = [...state.fontList];

				if (payload === '') {
					state.searchResult = fontList;
					return;
				}

				const keyword = payload.toLowerCase();
				const searchResult: FontItem[] = [];
				const modifiedFontList = fontList.map((font) => {
					font.regular = reduceFontFileName(font.regular);
					font.italics = reduceFontFileName(font.italics);
					font.bold = reduceFontFileName(font.bold);
					font.bolditalics = reduceFontFileName(font.bolditalics);

					return { ...font };
				});

				modifiedFontList.map((font) => {
					if (
						checkFontListIncludes(font.font_name, keyword) ||
						checkFontListIncludes(font.regular, keyword) ||
						checkFontListIncludes(font.italics, keyword) ||
						checkFontListIncludes(font.bold, keyword) ||
						checkFontListIncludes(font.bolditalics, keyword)
					) {
						return searchResult.push(font);
					}

					return false;
				});

				const relevant: FontItem[] = [];
				const related: FontItem[] = [];

				/* Construct 2 arrays containing the most relevant and the related results */
				searchResult.map((item) => {
					if (item.font_name.toLowerCase().includes(keyword)) {
						return relevant.push(item);
					}

					return related.push(item);
				});

				/* Sort and combine mostRelevant and related array into 1 array */
				state.searchResult = [
					...relevant.sort((a, b) =>
						a.font_name.localeCompare(b.font_name)
					),
					...related.sort((a, b) =>
						a.font_name.localeCompare(b.font_name)
					),
				];
			})
			.addCase(SELECT_FONT, (state: FontManagerState, action) => {
				const { payload } = action as unknown as PayloadAction<string>;
				return {
					...state,
					selectedFont: payload,
				};
			})
			.addCase(
				MOVE_SELECTED_FONT_TO_TOP,
				(state: FontManagerState, action) => {
					const { payload } =
						action as unknown as PayloadAction<string>;
					const fontList = [...state.fontList];
					const filterFontList = fontList.filter(
						(item) => item.id !== payload
					);
					const getPayloadItem = fontList.filter(
						(item) => item.id === payload
					);
					const list = [...getPayloadItem, ...filterFontList];

					return {
						...state,
						fontList: list,
					};
				}
			);
	},
});

export default fontManagerSlice.reducer;
