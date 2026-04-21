/* Dependencies */
import { takeLatest, call, put } from 'redux-saga/effects';
/* Redux action types & actions */
import {
	updateSelectBoxSuccess,
	updateSelectBoxFailed,
	templateProcessingSuccess,
	templateProcessingFailed,
	templateUploadProcessingSuccess,
	templateUploadProcessingFailed,
	UPDATE_SELECT_BOX,
	TEMPLATE_PROCESSING,
	POST_TEMPLATE_UPLOAD_PROCESSING,
	templateProcessing as templateProcessingAction,
	postTemplateUploadProcessing as postTemplateUploadProcessingAction,
} from '../actions/templates';
/* APIs */
import {
	apiPostUpdateSelectBox,
	apiPostTemplateProcessing,
	apiPostTemplateUploadProcessing,
} from '../api/templates';
/* Types */
import { ApiResponse } from '../types';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

export function* updateSelectBox(): Generator {
	try {
		const response = (yield call(
			apiPostUpdateSelectBox
		)) as ApiResponse<string>;
		yield put(updateSelectBoxSuccess(response.body));
	} catch (error) {
		yield put(updateSelectBoxFailed());
	}
}

export function* templateProcessing(
	action: ReturnType<typeof templateProcessingAction>
): Generator {
	try {
		yield call(apiPostTemplateProcessing, action.payload);
		yield put(templateProcessingSuccess('success'));
	} catch (error) {
		yield put(templateProcessingFailed('failed'));
	}
}

export function* templateUploadProcessing(
	action: ReturnType<typeof postTemplateUploadProcessingAction>
): Generator {
	try {
		const response = (yield call(
			apiPostTemplateUploadProcessing,
			action.payload.file,
			action.payload.filename
		)) as ApiResponse<Record<string, unknown>>;
		yield put(templateUploadProcessingSuccess(response.body));
	} catch (error) {
		yield put(
			templateUploadProcessingFailed({
				message: (error as Error).message,
			})
		);
	}
}

export function* watchUpdateSelectBox(): Generator {
	yield takeLatest(UPDATE_SELECT_BOX, updateSelectBox);
}

export function* watchTemplateProcessing(): Generator {
	yield takeLatest(TEMPLATE_PROCESSING, templateProcessing);
}

export function* watchpostTemplateUploadProcessing(): Generator {
	yield takeLatest(POST_TEMPLATE_UPLOAD_PROCESSING, templateUploadProcessing);
}
