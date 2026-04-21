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
	addFont as addFontAction,
	editFont as editFontAction,
	deleteFont as deleteFontAction,
} from '../actions/fontManager';
import { associatedFontManagerSelectBox } from '../utilities/FontManager/associatedFontManagerSelectBox';
/* Types */
import { ApiResponse, FontItem } from '../types';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.0
 */

export function* watchGetCustomFontList(): Generator {
	yield takeLatest(GET_CUSTOM_FONT_LIST, getCustomFontList);
}

export function* getCustomFontList(): Generator {
	try {
		const response = (yield call(apiGetCustomFontList)) as ApiResponse<
			FontItem[]
		>;

		if (!response.ok) {
			throw response;
		}

		yield put({
			type: GET_CUSTOM_FONT_LIST_SUCCESS,
			payload: response.body,
		});
	} catch (error) {
		yield put({
			type: GET_CUSTOM_FONT_LIST_ERROR,
			payload: GFPDF.addFatalError,
		});
	}
}

export function* watchGetCustomFontListSuccess(): Generator {
	yield takeLatest(
		GET_CUSTOM_FONT_LIST_SUCCESS,
		function (response: unknown) {
			const { payload } = response as { payload: FontItem[] };
			associatedFontManagerSelectBox(payload);
		}
	);
}

export function* watchAddFont(): Generator {
	yield takeLatest(ADD_FONT, addFont);
}

export function* addFont(action: ReturnType<typeof addFontAction>): Generator {
	try {
		const response = (yield call(
			apiAddFont,
			action.payload
		)) as ApiResponse<FontItem>;

		if (!response.ok) {
			throw response;
		}

		const data = {
			font: response.body,
			msg: '<strong>' + GFPDF.addUpdateFontSuccess + '</strong>',
		};

		yield put({
			type: ADD_FONT_SUCCESS,
			payload: data,
		});
	} catch (error) {
		const err = error as ApiResponse<{
			code?: string;
			message?: string;
			status?: number;
		}>;
		const response = err.body;

		if (!response || err.status === 500) {
			return yield put({
				type: ADD_FONT_ERROR,
				payload: GFPDF.addFatalError,
			});
		}

		if (err.status === 400 && response.code === 'font_validation_error') {
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
			payload: response.message || GFPDF.addFatalError,
		});
	}
}

export function* watchEditFont(): Generator {
	yield takeLatest(EDIT_FONT, editFont);
}

export function* editFont(
	action: ReturnType<typeof editFontAction>
): Generator {
	try {
		const response = (yield call(
			apiEditFont,
			action.payload
		)) as ApiResponse<FontItem>;

		if (!response.ok) {
			throw response;
		}

		const data = {
			font: response.body,
			msg: '<strong>' + GFPDF.addUpdateFontSuccess + '</strong>',
		};

		yield put({
			type: EDIT_FONT_SUCCESS,
			payload: data,
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

		if (status === 500 && code !== 'font_file_gone_missing') {
			return yield put({
				type: EDIT_FONT_ERROR,
				payload: GFPDF.addFatalError,
			});
		}

		if (status === 400 && code === 'font_validation_error') {
			return yield put({
				type: EDIT_FONT_ERROR,
				payload: {
					fontValidationError: GFPDF.fontFileInvalid,
					msg: response?.message || GFPDF.addFatalError,
				},
			});
		}

		yield put({
			type: EDIT_FONT_ERROR,
			payload: response?.message || GFPDF.addFatalError,
		});
	}
}

export function* watchDeleteFont(): Generator {
	yield takeLatest(DELETE_FONT, deleteFont);
}

export function* deleteFont(
	action: ReturnType<typeof deleteFontAction>
): Generator {
	try {
		const response = (yield call(
			apiDeleteFont,
			action.payload
		)) as Response;

		if (!response.ok) {
			throw response;
		}

		yield put({
			type: DELETE_FONT_SUCCESS,
			payload: action.payload,
		});
	} catch (error) {
		yield put({
			type: DELETE_FONT_ERROR,
			payload: GFPDF.addFatalError,
		});
	}
}
