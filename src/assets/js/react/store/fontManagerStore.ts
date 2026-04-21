/* Dependencies */
import { createReduxStore } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
/* Action type constants */
import {
	GET_CUSTOM_FONT_LIST,
	GET_CUSTOM_FONT_LIST_SUCCESS,
	GET_CUSTOM_FONT_LIST_ERROR,
	ADD_FONT,
	ADD_FONT_SUCCESS,
	ADD_FONT_ERROR,
	EDIT_FONT,
	EDIT_FONT_SUCCESS,
	EDIT_FONT_ERROR,
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
	/* Sync action creators yielded inside generators */
	getCustomFontList as getCustomFontListAction,
	addFont as addFontSyncAction,
	editFont as editFontSyncAction,
	deleteFont as deleteFontSyncAction,
	/* Sync action creators exported directly to components */
	validationError,
	deleteVariantError,
	clearAddFontMsg,
	clearDropzoneError,
	searchFontList,
	resetSearchResult,
	selectFont,
	moveSelectedFontToTop,
} from '../actions/fontManager';
/* APIs */
import {
	apiGetCustomFontList,
	apiAddFont,
	apiEditFont,
	apiDeleteFont,
} from '../api/fontManager';
/* Utilities */
import {
	findAndRemove,
	reduceFontFileName,
	checkFontListIncludes,
} from '../utilities/FontManager/fontManagerReducer';
import { associatedFontManagerSelectBox } from '../utilities/FontManager/associatedFontManagerSelectBox';
/* Types */
import { FontItem, FontFormData, FontManagerState, ApiResponse } from '../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

export const FONT_MANAGER_STORE_NAME = 'gravity-pdf/font-manager' as const;

type ThunkArgs = { dispatch: (action: unknown) => unknown };

function taggedThunk<TArgs extends unknown[]>(
	type: string,
	factory: (...args: TArgs) => (args: ThunkArgs) => Promise<void>
): (...args: TArgs) => ((args: ThunkArgs) => Promise<void>) & { type: string } {
	return (...args: TArgs) => {
		const thunk = factory(...args) as ((args: ThunkArgs) => Promise<void>) & { type: string };
		thunk.type = type;
		return thunk;
	};
}

export function createFontManagerStore(
	overrideInitial?: Partial<FontManagerState>
) {
	const defaultInitial: FontManagerState = {
		loading: false,
		addFontLoading: false,
		deleteFontLoading: false,
		fontList: [],
		searchResult: null,
		selectedFont: '',
		msg: {},
	};

	const initial: FontManagerState = { ...defaultInitial, ...overrideInitial };

	function reducer(
		state: FontManagerState = initial,
		action: { type: string; [key: string]: unknown }
	): FontManagerState {
		switch (action.type) {
			case GET_CUSTOM_FONT_LIST:
				return { ...state, loading: true, msg: {} };

			case GET_CUSTOM_FONT_LIST_SUCCESS:
				return {
					...state,
					loading: false,
					fontList: action.payload as FontItem[],
				};

			case GET_CUSTOM_FONT_LIST_ERROR:
				return {
					...state,
					loading: false,
					msg: { error: { fontList: action.payload as string } },
				};

			case ADD_FONT: {
				const prevError = state.msg.error ?? {};
				const {
					fontValidationError: _fve,
					addFont: _af,
					...restError
				} = prevError;
				const newMsg =
					Object.keys(restError).length > 0
						? { error: restError }
						: {};
				return { ...state, addFontLoading: true, msg: newMsg };
			}

			case ADD_FONT_SUCCESS: {
				const payload = action.payload as { font: FontItem; msg: string };
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
			}

			case ADD_FONT_ERROR: {
				const payload = action.payload as {
					msg?: string;
					fontValidationError?: string;
					[key: string]: string | undefined;
				};
				let errorUpdate: NonNullable<FontManagerState['msg']['error']>;

				if (payload.fontValidationError) {
					errorUpdate = {
						...state.msg.error,
						addFont: payload.msg,
						fontValidationError: payload.fontValidationError,
					};
				} else {
					errorUpdate = {
						...state.msg.error,
						addFont: payload as unknown as Record<string, string>,
					};
				}

				const { deleteFont: _df, ...finalError } = errorUpdate;
				return {
					...state,
					addFontLoading: false,
					msg: { ...state.msg, error: finalError },
				};
			}

			case EDIT_FONT: {
				const prevError = state.msg.error ?? {};
				const {
					addFont: _af,
					fontValidationError: _fve,
					...restError
				} = prevError;
				const newMsg =
					Object.keys(restError).length > 0
						? { error: restError }
						: {};
				return { ...state, addFontLoading: true, msg: newMsg };
			}

			case EDIT_FONT_SUCCESS: {
				const { font, msg } = action.payload as {
					font: FontItem;
					msg: string;
				};
				const applyUpdate = (list: FontItem[]) =>
					list.map((f) =>
						f.id === font.id
							? {
									...f,
									font_name: font.font_name,
									regular: font.regular,
									italics: font.italics,
									bold: font.bold,
									bolditalics: font.bolditalics,
								}
							: f
					);
				return {
					...state,
					addFontLoading: false,
					msg: { success: { addFont: msg } },
					fontList: applyUpdate(state.fontList),
					searchResult: state.searchResult
						? applyUpdate(state.searchResult)
						: null,
				};
			}

			case EDIT_FONT_ERROR: {
				const payload = action.payload as {
					msg?: string;
					fontValidationError?: string;
					[key: string]: string | undefined;
				};
				let errorUpdate: NonNullable<FontManagerState['msg']['error']>;

				if (payload.fontValidationError) {
					errorUpdate = {
						...state.msg.error,
						addFont: payload.msg,
						fontValidationError: payload.fontValidationError,
					};
				} else {
					errorUpdate = {
						...state.msg.error,
						addFont: payload as unknown as Record<string, string>,
					};
				}

				const { deleteFont: _df, ...finalError } = errorUpdate;
				return {
					...state,
					addFontLoading: false,
					msg: { ...state.msg, error: finalError },
				};
			}

			case VALIDATION_ERROR:
				return {
					...state,
					msg: {
						error: {
							...(state.msg.error ?? {}),
							addFont: __(
								'<strong>The action could not be completed.</strong> Resolve the highlighted issues above and then try again.',
								'gravity-pdf'
							),
						},
					},
				};

			case DELETE_VARIANT_ERROR: {
				const key = action.payload as string;
				const addFont = {
					...(state.msg.error?.addFont as Record<string, string>),
				};
				delete addFont[key];
				return {
					...state,
					msg: { error: { ...state.msg.error, addFont } },
				};
			}

			case DELETE_FONT: {
				const prevError = state.msg.error ?? {};
				const { deleteFont: _df, ...restError } = prevError;
				const newMsg =
					Object.keys(restError).length > 0
						? { error: restError }
						: {};
				return { ...state, deleteFontLoading: true, msg: newMsg };
			}

			case DELETE_FONT_SUCCESS: {
				const id = action.payload as string;
				if (state.searchResult) {
					const updatedSearch = findAndRemove(
						[...state.searchResult],
						id
					);
					return {
						...state,
						deleteFontLoading: false,
						fontList: findAndRemove([...state.fontList], id),
						searchResult:
							updatedSearch.length === 0 ? null : updatedSearch,
					};
				}
				return {
					...state,
					deleteFontLoading: false,
					fontList: findAndRemove([...state.fontList], id),
				};
			}

			case DELETE_FONT_ERROR:
				return {
					...state,
					deleteFontLoading: false,
					msg: {
						...state.msg,
						error: {
							...state.msg.error,
							deleteFont: action.payload as string,
						},
					},
				};

			case CLEAR_ADD_FONT_MSG: {
				const prevError = state.msg.error ?? {};
				const { addFont: _af, ...restError } = prevError;
				const newMsg =
					Object.keys(restError).length > 0
						? { error: restError }
						: {};
				return { ...state, msg: newMsg };
			}

			case CLEAR_DROPZONE_ERROR: {
				const key = action.payload as string;
				const addFont = state.msg.error?.addFont;
				if (typeof addFont === 'object' && addFont !== null) {
					const { [key]: _removed, ...restAddFont } =
						addFont as Record<string, string>;
					return {
						...state,
						msg: {
							...state.msg,
							error: { ...state.msg.error, addFont: restAddFont },
						},
					};
				}
				return state;
			}

			case RESET_SEARCH_RESULT:
				return { ...state, searchResult: null };

			case SEARCH_FONT_LIST: {
				const term = action.payload as string;
				const fontList = [...state.fontList];

				if (term === '') {
					return { ...state, searchResult: fontList };
				}

				const keyword = term.toLowerCase();
				const modifiedFontList = fontList.map((font) => ({
					...font,
					regular: reduceFontFileName(font.regular),
					italics: reduceFontFileName(font.italics),
					bold: reduceFontFileName(font.bold),
					bolditalics: reduceFontFileName(font.bolditalics),
				}));

				const searchResult: FontItem[] = [];
				modifiedFontList.forEach((font) => {
					if (
						checkFontListIncludes(font.font_name, keyword) ||
						checkFontListIncludes(font.regular, keyword) ||
						checkFontListIncludes(font.italics, keyword) ||
						checkFontListIncludes(font.bold, keyword) ||
						checkFontListIncludes(font.bolditalics, keyword)
					) {
						searchResult.push(font);
					}
				});

				const relevant: FontItem[] = [];
				const related: FontItem[] = [];

				searchResult.forEach((item) => {
					if (item.font_name.toLowerCase().includes(keyword)) {
						relevant.push(item);
					} else {
						related.push(item);
					}
				});

				return {
					...state,
					searchResult: [
						...relevant.sort((a, b) =>
							a.font_name.localeCompare(b.font_name)
						),
						...related.sort((a, b) =>
							a.font_name.localeCompare(b.font_name)
						),
					],
				};
			}

			case SELECT_FONT:
				return { ...state, selectedFont: action.payload as string };

			case MOVE_SELECTED_FONT_TO_TOP: {
				const id = action.payload as string;
				const fontList = [...state.fontList];
				const match = fontList.filter((item) => item.id === id);
				const rest = fontList.filter((item) => item.id !== id);
				return { ...state, fontList: [...match, ...rest] };
			}

			default:
				return state;
		}
	}

	return createReduxStore(FONT_MANAGER_STORE_NAME, {
		reducer,
		actions: {
			/* Sync action creators */
			validationError,
			deleteVariantError,
			clearAddFontMsg,
			clearDropzoneError,
			searchFontList,
			resetSearchResult,
			selectFont,
			moveSelectedFontToTop,

			/* Thunk action creators (tagged for spy compatibility) */
			getCustomFontList: taggedThunk(
				GET_CUSTOM_FONT_LIST,
				() => async ({ dispatch }: ThunkArgs) => {
					dispatch(getCustomFontListAction());
					try {
						const response = await apiGetCustomFontList();
						if (!response.ok) {
							throw response;
						}
						dispatch({
							type: GET_CUSTOM_FONT_LIST_SUCCESS,
							payload: response.body,
						});
						associatedFontManagerSelectBox(response.body);
					} catch {
						dispatch({
							type: GET_CUSTOM_FONT_LIST_ERROR,
							payload: __( 'A problem occurred. Reload the page and try again.', 'gravity-pdf' ),
						});
					}
				}
			),

			addFont: taggedThunk(
				ADD_FONT,
				(font: FontFormData) => async ({ dispatch }: ThunkArgs) => {
					dispatch(addFontSyncAction(font));
					try {
						const response = await apiAddFont(font);
						if (!response.ok) {
							throw response;
						}
						dispatch({
							type: ADD_FONT_SUCCESS,
							payload: {
								font: response.body,
								msg: __( 'Your font has been saved.', 'gravity-pdf' ),
							},
						});
					} catch (error) {
						const err = error as ApiResponse<{
							code?: string;
							message?: string;
							status?: number;
						}>;
						const response = err.body;

						if (!response || err.status === 500) {
							dispatch({
								type: ADD_FONT_ERROR,
								payload: __( 'A problem occurred. Reload the page and try again.', 'gravity-pdf' ),
							});
							return;
						}

						if (
							err.status === 400 &&
							response.code === 'font_validation_error'
						) {
							dispatch({
								type: ADD_FONT_ERROR,
								payload: {
									fontValidationError: __( '<strong>Font file(s) are malformed</strong> and cannot be used with Gravity PDF.', 'gravity-pdf' ),
									msg: response.message,
								},
							});
							return;
						}

						dispatch({
							type: ADD_FONT_ERROR,
							payload:
								response.message || __( 'A problem occurred. Reload the page and try again.', 'gravity-pdf' ),
						});
					}
				}
			),

			editFont: taggedThunk(
				EDIT_FONT,
				(fontDetails: {
					id: string;
					font: Partial<FontFormData>;
				}) => async ({ dispatch }: ThunkArgs) => {
					dispatch(editFontSyncAction(fontDetails));
					try {
						const response = await apiEditFont(fontDetails);
						if (!response.ok) {
							throw response;
						}
						dispatch({
							type: EDIT_FONT_SUCCESS,
							payload: {
								font: response.body,
								msg: __( 'Your font has been saved.', 'gravity-pdf' ),
							},
						});
					} catch (error) {
						const err = error as ApiResponse<{
							code?: string;
							message?: string;
							status?: number;
						}>;
						const response = err?.body;
						const status = err?.status;
						const code = response?.code;

						if (
							status === 500 &&
							code !== 'font_file_gone_missing'
						) {
							dispatch({
								type: EDIT_FONT_ERROR,
								payload: __( 'A problem occurred. Reload the page and try again.', 'gravity-pdf' ),
							});
							return;
						}

						if (
							status === 400 &&
							code === 'font_validation_error'
						) {
							dispatch({
								type: EDIT_FONT_ERROR,
								payload: {
									fontValidationError: __( '<strong>Font file(s) are malformed</strong> and cannot be used with Gravity PDF.', 'gravity-pdf' ),
									msg:
										response?.message ||
										__( 'A problem occurred. Reload the page and try again.', 'gravity-pdf' ),
								},
							});
							return;
						}

						dispatch({
							type: EDIT_FONT_ERROR,
							payload:
								response?.message || __( 'A problem occurred. Reload the page and try again.', 'gravity-pdf' ),
						});
					}
				}
			),

			deleteFont: taggedThunk(
				DELETE_FONT,
				(id: string) => async ({ dispatch }: ThunkArgs) => {
					dispatch(deleteFontSyncAction(id));
					try {
						const response = await apiDeleteFont(id);
						if (!response.ok) {
							throw response;
						}
						dispatch({ type: DELETE_FONT_SUCCESS, payload: id });
					} catch {
						dispatch({
							type: DELETE_FONT_ERROR,
							payload: __( 'A problem occurred. Reload the page and try again.', 'gravity-pdf' ),
						});
					}
				}
			),
		},
		selectors: {
			getLoading: (state: FontManagerState) => state.loading,
			getAddFontLoading: (state: FontManagerState) =>
				state.addFontLoading,
			getDeleteFontLoading: (state: FontManagerState) =>
				state.deleteFontLoading,
			getFontList: (state: FontManagerState) => state.fontList,
			getSearchResult: (state: FontManagerState) => state.searchResult,
			getSelectedFont: (state: FontManagerState) => state.selectedFont,
			getMsg: (state: FontManagerState) => state.msg,
		},
	});
}

export const fontManagerStore = createFontManagerStore();
