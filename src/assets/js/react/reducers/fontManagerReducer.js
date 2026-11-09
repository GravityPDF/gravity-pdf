/* Dependencies */
import { sprintf } from 'sprintf-js';
/* Redux action types */
import {
	GET_CUSTOM_FONT_LIST,
	GET_CUSTOM_FONT_LIST_SUCCESS,
	GET_CUSTOM_FONT_LIST_ERROR,
	GET_FONT_USAGE_SUCCESS,
	GET_FONT_USAGE_ERROR,
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
	INSTALL_FONT,
	INSTALL_FONT_SUCCESS,
	INSTALL_FONT_ERROR,
	CLEAR_ADD_FONT_MSG,
	CLEAR_DROPZONE_ERROR,
	RESET_SEARCH_RESULT,
	SEARCH_FONT_LIST,
	SELECT_FONT,
} from '../actions/fontManager';
/* Utilities */
import {
	findAndUpdate,
	findAndReplace,
	findAndRemove,
	reduceFontFileName,
	checkFontListIncludes,
	clearMsg,
	removeVariantError,
} from '../utilities/FontManager/fontManagerReducer';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * @typedef FontManagerReducerState
 * @property { boolean }       loading           - general loading state
 * @property { boolean }       addFontLoading    - loading state when adding font
 * @property { boolean }       deleteFontLoading - loading state when deleting font
 * @property { null | string } installingFont    - ID of the font being installed
 * @property { Array<Object> } fontList          - font list state
 * @property { null | Object } fontUsage         - why fonts are in use by ID, null until loaded
 * @property { null }          searchResult      - search result state
 * @property { string }        selectedFont      - state of selected font
 * @property { Object }        msg               - message object
 */

/**
 * Initial state setup for the "fontManager" portion of our redux store
 *
 * @return { FontManagerReducerState } initialState
 *
 * @since 6.0
 */
export const initialState = {
	loading: false,
	addFontLoading: false,
	deleteFontLoading: false,
	installingFont: null,
	fontList: [],
	fontUsage: null,
	searchResult: null,
	selectedFont: '',
	msg: {},
};

/**
 * Forget the outcome of the last font install or delete
 *
 * @param { Object } current
 *
 * @return { Object } msg
 *
 * @since 6.19.0
 */
const clearFontActionMsg = (current) => {
	const msg = { ...current };

	delete msg.success;

	if (msg.error) {
		delete msg.error.deleteFont;
	}

	return msg;
};

/**
 * Apply a change to the font list and any search of it, ending a search left with no results
 *
 * @param { FontManagerReducerState } state
 * @param { Function }                update
 *
 * @return {{ fontList: Array, searchResult: null | Array }} The new lists
 *
 * @since 6.19.0
 */
const updateFontLists = (state, update) => {
	const searchResult = state.searchResult && update(state.searchResult);

	return {
		fontList: update(state.fontList),
		searchResult: searchResult?.length ? searchResult : null,
	};
};

/**
 * The action for "fontManager" reducer which updates its state
 *
 * @param { FontManagerReducerState } state
 * @param { Object }                  action
 * @param { Object }                  action.payload
 * @param { string }                  action.type
 *
 * @return { FontManagerReducerState } state
 *
 * @since 6.0
 */
export default function (state = initialState, action) {
	const { payload } = action;

	switch (action.type) {
		/**
		 * Process GET_CUSTOM_FONT_LIST
		 *
		 * @since 6.0
		 */
		case GET_CUSTOM_FONT_LIST: {
			return {
				...state,
				loading: true,
				fontUsage: null,
				msg: {},
			};
		}

		/**
		 * Process GET_CUSTOM_FONT_LIST_SUCCESS
		 *
		 * @since 6.0
		 */
		case GET_CUSTOM_FONT_LIST_SUCCESS:
			return {
				...state,
				loading: false,
				fontList: payload,
			};

		/**
		 * Process GET_CUSTOM_FONT_LIST_ERROR
		 *
		 * @since 6.0
		 */
		case GET_CUSTOM_FONT_LIST_ERROR:
			return {
				...state,
				loading: false,
				msg: { error: { fontList: payload } },
			};

		/**
		 * Process GET_FONT_USAGE_SUCCESS
		 *
		 * @since 6.19.0
		 */
		case GET_FONT_USAGE_SUCCESS:
			return {
				...state,
				fontUsage: payload,
			};

		/**
		 * Process GET_FONT_USAGE_ERROR, which leaves Delete disabled
		 *
		 * @since 6.19.0
		 */
		case GET_FONT_USAGE_ERROR:
			return {
				...state,
				msg: {
					...state.msg,
					error: { ...state.msg.error, deleteFont: payload },
				},
			};

		/**
		 * Process ADD_FONT
		 *
		 * @since 6.0
		 */
		case ADD_FONT: {
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
		}

		/**
		 * Process ADD_FONT_SUCCESS
		 *
		 * @since 6.0
		 */
		case ADD_FONT_SUCCESS: {
			if (state.msg.error && state.msg.error.fontList) {
				return {
					...state,
					addFontLoading: false,
					msg: { ...state.msg, success: { addFont: payload.msg } },
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
		}

		/**
		 * Process ADD_FONT_ERROR & EDIT_FONT_ERROR
		 *
		 * @since 6.0
		 */
		case ADD_FONT_ERROR:
		case EDIT_FONT_ERROR: {
			let msg;

			msg = {
				...state.msg,
				error: { ...state.msg.error, addFont: payload },
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
		}

		/**
		 * Process EDIT_FONT
		 *
		 * @since 6.0
		 */
		case EDIT_FONT: {
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
		}

		/**
		 * Process EDIT_FONT_SUCCESS
		 *
		 * Update fontList state with the new font details
		 *
		 * @since 6.0
		 */
		case EDIT_FONT_SUCCESS: {
			const msg = { success: { addFont: payload.msg } };

			/* Update search result in case there's an ongoing search */
			if (state.searchResult) {
				return {
					...state,
					addFontLoading: false,
					fontList: findAndUpdate([...state.fontList], payload),
					searchResult: findAndUpdate(
						[...state.searchResult],
						payload
					),
					msg,
				};
			}

			return {
				...state,
				addFontLoading: false,
				fontList: findAndUpdate([...state.fontList], payload),
				msg,
			};
		}

		/**
		 * Process VALIDATION_ERROR
		 *
		 * @since 6.0
		 */
		case VALIDATION_ERROR:
			return {
				...state,
				msg: {
					error: {
						...state.msg.error,
						// %s is found inside addUpdateFontError
						// eslint-disable-next-line @wordpress/valid-sprintf
						addFont: sprintf(
							GFPDF.addUpdateFontError,
							'<strong>',
							'</strong>'
						),
					},
				},
			};

		/**
		 * Process DELETE_FONT
		 *
		 * @since 6.0
		 */
		case DELETE_FONT:
			return {
				...state,
				deleteFontLoading: true,
				msg: clearFontActionMsg(state.msg),
			};

		/**
		 * Process DELETE_FONT_SUCCESS
		 *
		 * @since 6.0
		 */
		case DELETE_FONT_SUCCESS: {
			const deleted = state.fontList.find((font) => font.id === payload);
			const msg = {
				...state.msg,
				success: {
					// %s is found inside fontManagerFontDeleted
					// eslint-disable-next-line @wordpress/valid-sprintf
					deleteFont: sprintf(
						GFPDF.fontManagerFontDeleted,
						deleted?.font_name ?? payload
					),
				},
			};

			/* A core font that can be installed again stays listed as available */
			const { font } = action;
			return {
				...state,
				deleteFontLoading: false,
				...updateFontLists(state, (list) =>
					font
						? findAndReplace(list, font)
						: findAndRemove(list, payload)
				),
				msg,
			};
		}

		/**
		 * Process DELETE_FONT_ERROR
		 *
		 * @since 6.0
		 */
		case DELETE_FONT_ERROR:
			return {
				...state,
				deleteFontLoading: false,
				msg: {
					...state.msg,
					error: { ...state.msg.error, deleteFont: payload },
				},
			};

		/**
		 * Process INSTALL_FONT
		 *
		 * @since 6.19.0
		 */
		case INSTALL_FONT:
			return {
				...state,
				installingFont: payload,
				msg: clearFontActionMsg(state.msg),
			};

		/**
		 * Process INSTALL_FONT_SUCCESS
		 *
		 * @since 6.19.0
		 */
		case INSTALL_FONT_SUCCESS:
			return {
				...state,
				installingFont: null,
				...updateFontLists(state, (list) =>
					findAndReplace(list, payload)
				),
				msg: {
					...state.msg,
					success: {
						// %s is found inside fontManagerFontJustInstalled
						// eslint-disable-next-line @wordpress/valid-sprintf
						installFont: sprintf(
							GFPDF.fontManagerFontJustInstalled,
							payload.font_name
						),
					},
				},
			};

		/**
		 * Process INSTALL_FONT_ERROR
		 *
		 * @since 6.19.0
		 */
		case INSTALL_FONT_ERROR:
			return {
				...state,
				installingFont: null,
				msg: {
					...state.msg,
					error: { ...state.msg.error, deleteFont: payload },
				},
			};

		/**
		 * Process CLEAR_ADD_FONT_MSG
		 *
		 * @since 6.0
		 */
		case CLEAR_ADD_FONT_MSG:
			return {
				...state,
				msg: clearMsg({ ...state.msg }),
			};

		/**
		 * Process DELETE_VARIANT_ERROR & CLEAR_DROPZONE_ERROR
		 *
		 * @since 6.0
		 */
		case DELETE_VARIANT_ERROR:
		case CLEAR_DROPZONE_ERROR:
			return {
				...state,
				msg: {
					...state.msg,
					error: removeVariantError(state.msg.error, payload),
				},
			};

		/**
		 * Process RESET_SEARCH_RESULT
		 *
		 * @since 6.0
		 */
		case RESET_SEARCH_RESULT:
			return {
				...state,
				searchResult: null,
			};

		/**
		 * Process SEARCH_FONT_LIST
		 *
		 * @since 6.0
		 */
		case SEARCH_FONT_LIST: {
			const fontList = [...state.fontList];

			if (payload === '') {
				return {
					...state,
					searchResult: fontList,
				};
			}

			const keyword = payload.toLowerCase();
			const searchResult = [];
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

			const relevant = [];
			const related = [];

			/* Construct 2 arrays containing the most relevant and the related results */
			searchResult.map((item) => {
				if (item.font_name.toLowerCase().includes(keyword)) {
					return relevant.push(item);
				}

				return related.push(item);
			});

			/* The most relevant first, each keeping the list's own order */
			return {
				...state,
				searchResult: [...relevant, ...related],
			};
		}

		/**
		 * Process SELECT_FONT
		 *
		 * @since 6.0
		 */
		case SELECT_FONT:
			return {
				...state,
				selectedFont: payload,
			};
	}

	return state;
}
