/* Dependencies */
import { createReduxStore } from '@wordpress/data';
/* Actions */
import {
	ADD_TO_CONSOLE,
	ADD_TO_RETRY_LIST,
	CLEAR_CONSOLE,
	CLEAR_BUTTON_CLICKED_AND_RETRY_LIST,
	GET_FILES_FROM_GITHUB,
	GET_FILES_FROM_GITHUB_SUCCESS,
	GET_FILES_FROM_GITHUB_FAILED,
	DOWNLOAD_FONTS_API_CALL,
	REQUEST_SENT_COUNTER,
	CLEAR_REQUEST_REMAINING_DATA,
	addToConsole,
	clearConsole,
	addToRetryList,
	clearButtonClickedAndRetryList,
	getFilesFromGitHub as getFilesFromGitHubAction,
	getFilesFromGitHubSuccess,
	getFilesFromGitHubFailed,
	downloadFontsApiCall,
	currentDownload,
	clearRequestRemainingData,
} from '../actions/coreFonts';
/* APIs */
import {
	apiGetFilesFromGitHub,
	apiPostDownloadFonts,
} from '../api/coreFonts';
/* Types */
import { CoreFontState, ConsoleLine } from '../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

export const CORE_FONTS_STORE_NAME = 'gravity-pdf/core-fonts' as const;

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

export function createCoreFontsStore(overrideInitial?: Partial<CoreFontState>) {
	const defaultInitial: CoreFontState = {
		buttonClicked: false,
		fontList: [],
		console: {},
		retry: [],
		getFilesFromGitHubFailed: '',
		requestDownload: '',
		downloadCounter: 0,
	};

	const initial: CoreFontState = { ...defaultInitial, ...overrideInitial };

	function reducer(
		state: CoreFontState = initial,
		action: { type: string; [key: string]: unknown }
	): CoreFontState {
		switch (action.type) {
			case ADD_TO_CONSOLE:
				return {
					...state,
					console: {
						...state.console,
						[action.key as string]: {
							status: action.status as ConsoleLine['status'],
							message: action.message as string,
						},
					},
				};
			case CLEAR_CONSOLE:
				return { ...state, console: {} };
			case ADD_TO_RETRY_LIST:
				if (state.retry.includes(action.name as string)) {
					return state;
				}
				return {
					...state,
					retry: [...state.retry, action.name as string],
				};
			case CLEAR_BUTTON_CLICKED_AND_RETRY_LIST:
				return { ...state, retry: [], buttonClicked: false };
			case GET_FILES_FROM_GITHUB:
				return { ...state, buttonClicked: true };
			case GET_FILES_FROM_GITHUB_SUCCESS: {
				const files = (
					action.payload as Array<{ name: string }>
				).map((item) => item.name);
				return {
					...state,
					fontList: files,
					downloadCounter: files.length,
				};
			}
			case GET_FILES_FROM_GITHUB_FAILED:
				return {
					...state,
					getFilesFromGitHubFailed: action.payload as string,
				};
			case REQUEST_SENT_COUNTER: {
				const errors = state.retry.length;
				const status = errors ? 'error' : 'success';
				const message = errors
					? GFPDF.coreFontError.replace('%s', String(errors))
					: GFPDF.coreFontSuccess;
				const newCounter = state.downloadCounter - 1;

				if (newCounter === 0) {
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
				return {
					...state,
					downloadCounter: newCounter,
					requestDownload: '',
				};
			}
			case CLEAR_REQUEST_REMAINING_DATA:
				return { ...state, requestDownload: '' };
			default:
				return state;
		}
	}

	return createReduxStore(CORE_FONTS_STORE_NAME, {
		reducer,
		actions: {
			/* Sync action creators */
			addToConsole,
			clearConsole,
			addToRetryList,
			clearButtonClickedAndRetryList,
			getFilesFromGitHubSuccess,
			getFilesFromGitHubFailed,
			downloadFontsApiCall,
			currentDownload,
			clearRequestRemainingData,

			/* Thunk action creators (tagged for spy compatibility) */
			getFilesFromGitHub: taggedThunk(
				GET_FILES_FROM_GITHUB,
				() => async ({ dispatch }: ThunkArgs) => {
					dispatch(getFilesFromGitHubAction());
					try {
						const response = await apiGetFilesFromGitHub();
						dispatch(getFilesFromGitHubSuccess(response.body));
					} catch {
						dispatch(getFilesFromGitHubFailed(GFPDF.coreFontGithubError));
					}
				}
			),

			downloadFonts: taggedThunk(
				DOWNLOAD_FONTS_API_CALL,
				(file: string) => async ({ dispatch }: ThunkArgs) => {
					dispatch(downloadFontsApiCall(file));
					try {
						await apiPostDownloadFonts(file);
						dispatch(addToConsole(
							file,
							'success',
							GFPDF.coreFontItemSuccessMessage.replace('%s', file)
						));
					} catch {
						dispatch(addToConsole(
							file,
							'error',
							GFPDF.coreFontItemErrorMessage.replace('%s', file)
						));
						dispatch(addToRetryList(file));
					} finally {
						dispatch(currentDownload());
					}
				}
			),
		},
		selectors: {
			getButtonClicked: (state: CoreFontState) => state.buttonClicked,
			getFontList: (state: CoreFontState) => state.fontList,
			getConsole: (state: CoreFontState) => state.console,
			getRetry: (state: CoreFontState) => state.retry,
			getFilesFromGitHubFailed: (state: CoreFontState) =>
				state.getFilesFromGitHubFailed,
			getRequestDownload: (state: CoreFontState) => state.requestDownload,
			getDownloadCounter: (state: CoreFontState) => state.downloadCounter,
		},
	});
}

export const coreFontsStore = createCoreFontsStore();
