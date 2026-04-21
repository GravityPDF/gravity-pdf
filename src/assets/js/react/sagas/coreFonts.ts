/* Dependencies */
import { channel, Channel } from 'redux-saga';
import { call, fork, take, takeLatest, put } from 'redux-saga/effects';
/* Redux action types & actions */
import {
	getFilesFromGitHubSuccess,
	getFilesFromGitHubFailed,
	addToConsole,
	addToRetryList,
	currentDownload,
	GET_FILES_FROM_GITHUB,
	DOWNLOAD_FONTS_API_CALL,
} from '../actions/coreFonts';
/* APIs */
import { apiGetFilesFromGitHub, apiPostDownloadFonts } from '../api/coreFonts';
/* Types */
import { ApiResponse } from '../types';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

export function* getFilesFromGitHub(): Generator {
	try {
		const response = (yield call(apiGetFilesFromGitHub)) as ApiResponse<
			string[]
		>;
		yield put(getFilesFromGitHubSuccess(response.body));
	} catch (error) {
		yield put(getFilesFromGitHubFailed(GFPDF.coreFontGithubError));
	}
}

export function* getDownloadFonts(chan: Channel<string>): Generator {
	while (true) {
		const payload = (yield take(chan)) as string;

		yield put(
			addToConsole(
				payload,
				'pending',
				GFPDF.coreFontItemPendingMessage.replace('%s', payload)
			)
		);

		try {
			const response = (yield call(
				apiPostDownloadFonts,
				payload
			)) as ApiResponse<unknown>;

			if (!response.body) {
				throw response;
			}

			yield put(
				addToConsole(
					payload,
					'success',
					GFPDF.coreFontItemSuccessMessage.replace('%s', payload)
				)
			);
		} catch (error) {
			yield put(
				addToConsole(
					payload,
					'error',
					GFPDF.coreFontItemErrorMessage.replace('%s', payload)
				)
			);
			yield put(addToRetryList(payload));
		} finally {
			yield put(currentDownload());
		}
	}
}

export function* watchGetFilesFromGitHub(): Generator {
	yield takeLatest(GET_FILES_FROM_GITHUB, getFilesFromGitHub);
}

export function* watchDownloadFonts(): Generator {
	const chan = (yield call(channel)) as Channel<string>;

	for (let i = 0; i < 5; i++) {
		yield fork(getDownloadFonts, chan);
	}

	while (true) {
		const { payload } = (yield take(DOWNLOAD_FONTS_API_CALL)) as {
			payload: string;
		};
		yield put(chan, payload);
	}
}
