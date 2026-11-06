/* Dependencies */
import { call, put, takeLatest } from 'redux-saga/effects';
import { sprintf } from 'sprintf-js';
/* APIs */
import {
	apiGetDownloadedFonts,
	apiDeleteDownloadedFont,
} from '../api/downloadedFonts';
/* Redux action types */
import {
	GET_DOWNLOADED_FONTS,
	GET_DOWNLOADED_FONTS_SUCCESS,
	GET_DOWNLOADED_FONTS_ERROR,
	DELETE_DOWNLOADED_FONT,
	DELETE_DOWNLOADED_FONT_SUCCESS,
	DELETE_DOWNLOADED_FONT_ERROR,
} from '../actions/downloadedFonts';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.19.0
 */

/**
 * @since 6.19.0
 */
export function* watchGetDownloadedFonts() {
	yield takeLatest(GET_DOWNLOADED_FONTS, getDownloadedFonts);
}

/**
 * Load the downloaded fonts
 *
 * @since 6.19.0
 */
export function* getDownloadedFonts() {
	try {
		const response = yield call(apiGetDownloadedFonts);

		if (!response.ok) {
			throw response;
		}

		yield put({
			type: GET_DOWNLOADED_FONTS_SUCCESS,
			payload: response.body,
		});
	} catch (error) {
		yield put({
			type: GET_DOWNLOADED_FONTS_ERROR,
			payload: GFPDF.addFatalError,
		});
	}
}

/**
 * @since 6.19.0
 */
export function* watchDeleteDownloadedFont() {
	yield takeLatest(DELETE_DOWNLOADED_FONT, deleteDownloadedFont);
}

/**
 * Delete a downloaded font, showing the server's reason when it refuses (in use, or already gone)
 *
 * @param {Object}                        params
 * @param {{ id: string, label: string }} params.payload
 *
 * @since 6.19.0
 */
export function* deleteDownloadedFont({ payload }) {
	try {
		const response = yield call(apiDeleteDownloadedFont, payload.id);

		if (!response.ok) {
			throw response;
		}

		yield put({
			type: DELETE_DOWNLOADED_FONT_SUCCESS,
			payload: {
				id: payload.id,
				// eslint-disable-next-line @wordpress/valid-sprintf
				msg: sprintf(GFPDF.fontManagerDownloadedDeleted, payload.label),
			},
		});
	} catch (error) {
		const refused =
			error && [404, 409].includes(error.status) && error.body?.message;

		yield put({
			type: DELETE_DOWNLOADED_FONT_ERROR,
			payload: refused || GFPDF.addFatalError,
		});
	}
}
