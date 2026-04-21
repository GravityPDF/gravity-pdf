/* Redux Action Type Constants */
export const ADD_TO_CONSOLE = 'ADD_TO_CONSOLE' as const;
export const ADD_TO_RETRY_LIST = 'ADD_TO_RETRY_LIST' as const;
export const CLEAR_CONSOLE = 'CLEAR_CONSOLE' as const;
export const CLEAR_BUTTON_CLICKED_AND_RETRY_LIST =
	'CLEAR_BUTTON_CLICKED_AND_RETRY_LIST' as const;
export const GET_FILES_FROM_GITHUB = 'GET_FILES_FROM_GITHUB' as const;
export const GET_FILES_FROM_GITHUB_SUCCESS =
	'GET_FILES_FROM_GITHUB_SUCCESS' as const;
export const GET_FILES_FROM_GITHUB_FAILED =
	'GET_FILES_FROM_GITHUB_FAILED' as const;
export const DOWNLOAD_FONTS_API_CALL = 'DOWNLOAD_FONTS_API_CALL' as const;
export const REQUEST_SENT_COUNTER = 'REQUEST_SENT_COUNTER' as const;
export const CLEAR_REQUEST_REMAINING_DATA =
	'CLEAR_REQUEST_REMAINING_DATA' as const;

/**
 * Redux Actions - payloads of information that send data from your application to your store
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.0
 */

import { ConsoleLine } from '../types';

export const addToConsole = (
	key: string,
	status: ConsoleLine['status'],
	message: string
) => {
	return {
		type: ADD_TO_CONSOLE,
		key,
		status,
		message,
	};
};

export const clearConsole = () => {
	return {
		type: CLEAR_CONSOLE,
	};
};

export const addToRetryList = (name: string) => {
	return {
		type: ADD_TO_RETRY_LIST,
		name,
	};
};

export const clearButtonClickedAndRetryList = () => {
	return {
		type: CLEAR_BUTTON_CLICKED_AND_RETRY_LIST,
	};
};

export const getFilesFromGitHub = () => {
	return {
		type: GET_FILES_FROM_GITHUB,
	};
};

export const getFilesFromGitHubSuccess = (files: string[]) => {
	return {
		type: GET_FILES_FROM_GITHUB_SUCCESS,
		payload: files,
	};
};

export const getFilesFromGitHubFailed = (error: string) => {
	return {
		type: GET_FILES_FROM_GITHUB_FAILED,
		payload: error,
	};
};

export const downloadFontsApiCall = (file: string) => {
	return {
		type: DOWNLOAD_FONTS_API_CALL,
		payload: file,
	};
};

export const currentDownload = () => {
	return {
		type: REQUEST_SENT_COUNTER,
	};
};

export const clearRequestRemainingData = () => {
	return {
		type: CLEAR_REQUEST_REMAINING_DATA,
	};
};
