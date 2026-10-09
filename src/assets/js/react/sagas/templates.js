/* Dependencies */
import {
	takeLatest,
	actionChannel,
	take,
	call,
	put,
	select,
} from 'redux-saga/effects';
/* Redux action types & actions */
import {
	addTemplate,
	updateTemplateParam,
	updateSelectBoxSuccess,
	updateSelectBoxFailed,
	templateProcessingSuccess,
	templateProcessingFailed,
	templateUploadProcessingSuccess,
	templateUploadProcessingFailed,
	UPDATE_SELECT_BOX,
	TEMPLATE_PROCESSING,
	POST_TEMPLATE_UPLOAD_PROCESSING,
} from '../actions/templates';
/* APIs */
import {
	apiPostUpdateSelectBox,
	apiPostTemplateProcessing,
	apiPostTemplateUploadProcessing,
} from '../api/templates';

/**
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Worker Saga updateSelectBox - Success and error handling of AJAX call
 *
 * @since 5.2
 */
export function* updateSelectBox() {
	try {
		const response = yield call(apiPostUpdateSelectBox);
		yield put(updateSelectBoxSuccess(response.body));
	} catch (error) {
		yield put(updateSelectBoxFailed());
	}
}

/**
 * Worker Saga templateProcessing - Success and error handling of AJAX call
 *
 * @param {Object} action
 *
 * @since 5.2
 */
export function* templateProcessing(action) {
	try {
		yield call(apiPostTemplateProcessing, action.payload);
		yield put(templateProcessingSuccess('success'));
	} catch (error) {
		yield put(templateProcessingFailed('failed'));
	}
}

/**
 * Worker Saga templateUploadProcessing - Success and error handling of AJAX call
 *
 * @param {Object} action
 *
 * @since 5.2
 */
export function* templateUploadProcessing(action) {
	const { file, filename } = action.payload;
	let message;

	try {
		const response = yield call(
			apiPostTemplateUploadProcessing,
			file,
			filename
		);

		if (response.ok && Array.isArray(response.body?.templates)) {
			yield* addTemplatesToList(response.body.templates);
			yield put(templateUploadProcessingSuccess(response.body, filename));
			return;
		}

		message = response.body?.error;
	} catch (error) {
		message = error.message;
	}

	yield put(
		templateUploadProcessingFailed({ message: message || '' }, filename)
	);
}

/**
 * Add newly-installed templates to the list, and flag the ones that were already there as updated
 *
 * @param {Array<Object>} templates
 *
 * @since 6.18.0
 */
export function* addTemplatesToList(templates) {
	const list = yield select((state) => state.template.list);

	for (const template of templates) {
		if (list.some((item) => item.id === template.id)) {
			yield put(
				updateTemplateParam(
					template.id,
					'message',
					GFPDF.templateSuccessfullyUpdated
				)
			);
		} else {
			/* `new` sorts it to the end of the list */
			yield put(
				addTemplate({
					...template,
					new: true,
					message: GFPDF.templateSuccessfullyInstalled,
				})
			);
		}
	}
}

/**
 * Watcher Saga watchUpdateSelectBox for updateSelectBox()
 *
 * @since 5.2
 */
export function* watchUpdateSelectBox() {
	yield takeLatest(UPDATE_SELECT_BOX, updateSelectBox);
}

/**
 * Watcher Saga watchTemplateProcessing for templateProcessing()
 *
 * @since 5.2
 */
export function* watchTemplateProcessing() {
	yield takeLatest(TEMPLATE_PROCESSING, templateProcessing);
}

/**
 * Watcher Saga watchTemplateProcessing for templateUploadProcessing()
 *
 * Uploads one zip at a time, so a large drop doesn't tie up every PHP worker
 *
 * @since 5.2
 */
export function* watchpostTemplateUploadProcessing() {
	const uploads = yield actionChannel(POST_TEMPLATE_UPLOAD_PROCESSING);

	while (true) {
		yield call(templateUploadProcessing, yield take(uploads));
	}
}
