/* Dependencies */
import { call, put, takeLatest } from 'redux-saga/effects';
/* APIs */
import {
	apiGetCustomFontList,
	apiGetFontUsage,
	apiAddFont,
	apiEditFont,
	apiDeleteFont,
	apiInstallFont,
} from '../api/fontManager';
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
	EDIT_FONT_SUCCESS,
	EDIT_FONT_ERROR,
	DELETE_FONT,
	DELETE_FONT_SUCCESS,
	DELETE_FONT_ERROR,
	INSTALL_FONT,
	INSTALL_FONT_SUCCESS,
	INSTALL_FONT_ERROR,
} from '../actions/fontManager';
import { associatedFontManagerSelectBox } from '../utilities/FontManager/associatedFontManagerSelectBox';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

/**
 * A watcher that get triggered when custom font list is requested
 *
 * @since 6.0
 */
export function* watchGetCustomFontList() {
	yield takeLatest(GET_CUSTOM_FONT_LIST, getCustomFontList);
}

/**
 * Generate response for custom font list request
 *
 * @since 6.0
 */
export function* getCustomFontList() {
	try {
		const response = yield call(apiGetCustomFontList);

		if (!response.ok) {
			throw response;
		}

		const responseBody = response.body;

		yield put({
			type: GET_CUSTOM_FONT_LIST_SUCCESS,
			payload: responseBody,
		});
	} catch (error) {
		yield put({
			type: GET_CUSTOM_FONT_LIST_ERROR,
			payload: GFPDF.addFatalError,
		});
	}
}

/**
 * A watcher that get triggered when custom font list is successfully requested
 *
 * @since 6.0
 */
export function* watchGetCustomFontListSuccess() {
	yield takeLatest(GET_CUSTOM_FONT_LIST_SUCCESS, function (response) {
		const fontList = response.payload;

		associatedFontManagerSelectBox(fontList);
	});
}

/**
 * A watcher that loads what's in use once the font list has loaded
 *
 * @since 6.19.0
 */
export function* watchGetFontUsage() {
	yield takeLatest(GET_CUSTOM_FONT_LIST_SUCCESS, getFontUsage);
}

/**
 * Generate response for the font usage request
 *
 * @since 6.19.0
 */
export function* getFontUsage() {
	try {
		const response = yield call(apiGetFontUsage);

		if (!response.ok) {
			throw response;
		}

		yield put({
			type: GET_FONT_USAGE_SUCCESS,
			payload: response.body,
		});
	} catch (error) {
		yield put({
			type: GET_FONT_USAGE_ERROR,
			payload: GFPDF.addFatalError,
		});
	}
}

/**
 * A watcher that get triggered when a new add font request is submitted
 *
 * @since 6.0
 */
export function* watchAddFont() {
	yield takeLatest(ADD_FONT, addFont);
}

/**
 * Generate response for add font request
 *
 * @param {Object}                                                                                                     params
 * @param {{ label: string, regular: string|File, italics: string|File, bold: string|File, bolditalics: string|File }} params.payload
 *
 * @since 6.0
 */
export function* addFont({ payload }) {
	try {
		const response = yield call(apiAddFont, payload);

		if (!response.ok) {
			throw response;
		}

		const responseBody = response.body;

		const data = {
			font: responseBody,
			msg: '<strong>' + GFPDF.addUpdateFontSuccess + '</strong>',
		};

		yield put({
			type: ADD_FONT_SUCCESS,
			payload: data,
		});
	} catch (error) {
		if (error.status === 500) {
			return yield put({
				type: ADD_FONT_ERROR,
				payload: GFPDF.addFatalError,
			});
		}

		const response = error.body;

		if (error.status === 400 && response.code === 'font_validation_error') {
			return yield put({
				type: ADD_FONT_ERROR,
				payload: {
					fontValidationError: GFPDF.fontFileInvalid,
					msg: response.message,
				},
			});
		}

		yield put({
			type: ADD_FONT_ERROR,
			payload: response.message,
		});
	}
}

/**
 * A watcher that get triggered when a new edit font request is submitted
 *
 * @since 6.0
 */
export function* watchEditFont() {
	yield takeLatest(EDIT_FONT, editFont);
}

/**
 * Generate response for edit font request
 *
 * @param {Object}                                                                                                                           params
 * @param {{ id: string, font: { label: string, regular: string|File, italics: string|File, bold: string|File, bolditalics: string|File } }} params.payload
 *
 * @since 6.0
 */
export function* editFont({ payload }) {
	try {
		const response = yield call(apiEditFont, payload);

		if (!response.ok) {
			throw response;
		}

		const responseBody = response.body;

		const data = {
			font: responseBody,
			msg: '<strong>' + GFPDF.addUpdateFontSuccess + '</strong>',
		};

		yield put({
			type: EDIT_FONT_SUCCESS,
			payload: data,
		});
	} catch (error) {
		const response = error.body;

		if (
			error.status === 500 &&
			response.code !== 'font_file_gone_missing'
		) {
			return yield put({
				type: EDIT_FONT_ERROR,
				payload: GFPDF.addFatalError,
			});
		}

		if (error.status === 400 && response.code === 'font_validation_error') {
			return yield put({
				type: EDIT_FONT_ERROR,
				payload: {
					fontValidationError: GFPDF.fontFileInvalid,
					msg: response.message,
				},
			});
		}

		yield put({
			type: EDIT_FONT_ERROR,
			payload:
				response.message === ''
					? GFPDF.addFatalError
					: response.message,
		});
	}
}

/**
 * A watcher that get triggered when a delete font request has been made
 *
 * @since 6.0
 */
export function* watchDeleteFont() {
	yield takeLatest(DELETE_FONT, deleteFont);
}

/**
 * Generate response for delete font request
 *
 * @param {Object} params
 * @param {string} params.payload  - ID of the font to delete
 * @param {string} params.fontType - The row's `type`
 *
 * @since 6.0
 */
export function* deleteFont({ payload, fontType }) {
	try {
		const response = yield call(apiDeleteFont, payload, fontType);

		if (!response.ok) {
			throw response;
		}

		yield put({
			type: DELETE_FONT_SUCCESS,
			payload,
			font: response.body?.font,
		});
	} catch (error) {
		/* Show the server's reason when it refuses (in use, or already gone) */
		const refused =
			error && [404, 409].includes(error.status) && error.body?.message;

		yield put({
			type: DELETE_FONT_ERROR,
			payload: refused || GFPDF.addFatalError,
		});
	}
}

/**
 * A watcher that gets triggered when a core font is to be installed
 *
 * @since 6.19.0
 */
export function* watchInstallFont() {
	yield takeLatest(INSTALL_FONT, installFont);
}

/**
 * Generate response for the install font request
 *
 * @param {Object} params
 * @param {string} params.payload - ID of the font to install
 *
 * @since 6.19.0
 */
export function* installFont({ payload }) {
	try {
		const response = yield call(apiInstallFont, payload);

		if (!response.ok) {
			throw response;
		}

		yield put({
			type: INSTALL_FONT_SUCCESS,
			payload: response.body,
		});
	} catch (error) {
		yield put({
			type: INSTALL_FONT_ERROR,
			payload: error?.body?.message || GFPDF.addFatalError,
		});
	}
}
