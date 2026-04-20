/* Dependencies */
import { createSlice } from '@reduxjs/toolkit';
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

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

/**
 * @typedef { Object } CoreFontReducerState
 * @property { boolean }       buttonClicked            - checker when a button is clicked
 * @property { Array<Object> } fontList                 - font list state
 * @property { Object }        console                  - messages
 * @property { Array<Object> } retry                    - retry state
 * @property { Object }        getFilesFromGithubFailed - failed state when getting files from github
 * @property { string }        requestDownload          - identifier when requesting a download
 * @property { number }        downloadCounter          - counter state of number of downloads
 */

/**
 * Setup the initial state of the "coreFont" portion of our Redux store
 *
 * @return { CoreFontReducerState } initialState
 *
 * @since 5.0
 */
export const initialState = {
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
			.addCase(ADD_TO_CONSOLE, (state, action) => ({
				...state,
				console: {
					...state.console,
					[action.key]: {
						status: action.status,
						message: action.message,
					},
				},
			}))
			.addCase(CLEAR_CONSOLE, (state) => ({
				...state,
				console: {},
			}))
			.addCase(ADD_TO_RETRY_LIST, (state, action) => {
				/* Do not allow the same item in the retry list */
				if (state.retry.includes(action.name)) {
					return state;
				}

				return {
					...state,
					retry: [...state.retry, action.name],
				};
			})
			.addCase(CLEAR_BUTTON_CLICKED_AND_RETRY_LIST, (state) => ({
				...state,
				retry: [],
				buttonClicked: false,
			}))
			.addCase(GET_FILES_FROM_GITHUB, (state) => ({
				...state,
				buttonClicked: true,
			}))
			.addCase(GET_FILES_FROM_GITHUB_SUCCESS, (state, action) => {
				const files = action.payload.map((item) => item.name);

				return {
					...state,
					fontList: files,
					downloadCounter: files.length,
				};
			})
			.addCase(GET_FILES_FROM_GITHUB_FAILED, (state, action) => ({
				...state,
				getFilesFromGitHubFailed: action.payload,
			}))
			.addCase(REQUEST_SENT_COUNTER, (state) => {
				/* Show the overall status once all the fonts have been downloaded (or tried to) */
				const errors = state.retry.length;
				const status = errors ? 'error' : 'success';
				const message = errors
					? GFPDF.coreFontError.replace('%s', errors)
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
			.addCase(CLEAR_REQUEST_REMAINING_DATA, (state) => ({
				...state,
				requestDownload: '',
			}));
	},
});

export default coreFontSlice.reducer;
