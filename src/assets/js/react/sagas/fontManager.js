/* Dependencies */
import { call, put, takeLatest } from 'redux-saga/effects';
/* APIs */
import {
	apiGetCustomFontList,
	apiAddFont,
	apiEditFont,
	apiDeleteFont,
} from '../api/fontManager';
/* Redux action types */
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
	DELETE_FONT,
	DELETE_FONT_SUCCESS,
	DELETE_FONT_ERROR,
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
 * A watcher that get triggered when a new add font request is submitted
 *
 * @since 6.0
 */
export function* watchAddFont() {
	yield takeLatest(ADD_FONT, addFont);
}

/**
 * A watcher that get triggered when custom font list is successfully requested
 *
 * @since 6.14.2
 */
export function* watchGetCustomFontListSuccess() {
	yield takeLatest(GET_CUSTOM_FONT_LIST_SUCCESS, function (response) {
		const fontList = response.payload;

		associatedFontManagerSelectBox(fontList);
	});
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
 * @param {Object}                                                                                                   params
 * @param {{ id: string, font: { label: string, regular?: File, italics?: File, bold?: File, bolditalics?: File } }} params.payload
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
 * @param {string} params.payload - ID of the font to delete
 *
 * @since 6.0
 */
export function* deleteFont({ payload }) {
	try {
		const response = yield call(apiDeleteFont, payload);

		if (!response.ok) {
			throw response;
		}

		yield put({
			type: DELETE_FONT_SUCCESS,
			payload,
		});
	} catch (error) {
		yield put({
			type: DELETE_FONT_ERROR,
			payload: GFPDF.addFatalError,
		});
	}
}
