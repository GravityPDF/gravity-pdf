/* Dependencies */
import { createSlice, PayloadAction } from '@reduxjs/toolkit';
/* Redux action types */
import {
	ADD_TO_CONSOLE,
	ADD_TO_RETRY_LIST,
	CLEAR_BUTTON_CLICKED_AND_RETRY_LIST,
	CLEAR_CONSOLE,
	GET_FILES_FROM_GITHUB_SUCCESS,
	GET_FILES_FROM_GITHUB_FAILED,
	REQUEST_SENT_COUNTER,
	CLEAR_REQUEST_REMAINING_DATA,
	GET_FILES_FROM_GITHUB,
} from '../actions/coreFonts';
/* Types */
import { CoreFontState, ConsoleLine } from '../types';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

export const initialState: CoreFontState = {
	buttonClicked: false,
	fontList: [],
	console: {},
	retry: [],
	getFilesFromGitHubFailed: '',
	requestDownload: '',
	downloadCounter: 0,
};

const coreFontSlice = createSlice({
	name: 'coreFonts',
	initialState,
	reducers: {},
	extraReducers: (builder) => {
		builder
			.addCase(ADD_TO_CONSOLE, (state: CoreFontState, action) => {
				const a = action as unknown as {
					type: typeof ADD_TO_CONSOLE;
					key: string;
					status: ConsoleLine['status'];
					message: string;
				};
				return {
					...state,
					console: {
						...state.console,
						[a.key]: {
							status: a.status,
							message: a.message,
						},
					},
				};
			})
			.addCase(CLEAR_CONSOLE, (state: CoreFontState) => ({
				...state,
				console: {},
			}))
			.addCase(ADD_TO_RETRY_LIST, (state: CoreFontState, action) => {
				const a = action as unknown as {
					type: typeof ADD_TO_RETRY_LIST;
					name: string;
				};

				/* Do not allow the same item in the retry list */
				if (state.retry.includes(a.name)) {
					return state;
				}

				return {
					...state,
					retry: [...state.retry, a.name],
				};
			})
			.addCase(
				CLEAR_BUTTON_CLICKED_AND_RETRY_LIST,
				(state: CoreFontState) => ({
					...state,
					retry: [],
					buttonClicked: false,
				})
			)
			.addCase(GET_FILES_FROM_GITHUB, (state: CoreFontState) => ({
				...state,
				buttonClicked: true,
			}))
			.addCase(
				GET_FILES_FROM_GITHUB_SUCCESS,
				(state: CoreFontState, action) => {
					const { payload } = action as unknown as PayloadAction<
						Array<{ name: string }>
					>;
					const files = payload.map((item) => item.name);

					return {
						...state,
						fontList: files,
						downloadCounter: files.length,
					};
				}
			)
			.addCase(
				GET_FILES_FROM_GITHUB_FAILED,
				(state: CoreFontState, action) => {
					const { payload } =
						action as unknown as PayloadAction<string>;
					return {
						...state,
						getFilesFromGitHubFailed: payload,
					};
				}
			)
			.addCase(REQUEST_SENT_COUNTER, (state: CoreFontState) => {
				/* Show the overall status once all the fonts have been downloaded (or tried to) */
				const errors = state.retry.length;
				const status = errors ? 'error' : 'success';
				const message = errors
					? GFPDF.coreFontError.replace('%s', String(errors))
					: GFPDF.coreFontSuccess;

				const newCounter = state.downloadCounter - 1;

				if (newCounter === 0) {
					/* Failed */
					if (state.retry.length > 0) {
						return {
							...state,
							console: {
								...state.console,
								completed: { status, message },
							},
							downloadCounter: state.retry.length,
							requestDownload: 'finished',
						};
					}
					/* Success */
					return {
						...state,
						console: {
							...state.console,
							completed: { status, message },
						},
						downloadCounter: state.fontList.length,
						requestDownload: 'finished',
					};
				}

				/* Counter still running — reset requestDownload (original fall-through behaviour) */
				return {
					...state,
					downloadCounter: newCounter,
					requestDownload: '',
				};
			})
			.addCase(CLEAR_REQUEST_REMAINING_DATA, (state: CoreFontState) => ({
				...state,
				requestDownload: '',
			}));
	},
});

export default coreFontSlice.reducer;
