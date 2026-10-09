import {
	searchTemplates,
	SEARCH_TEMPLATES,
	selectTemplate,
	SELECT_TEMPLATE,
	addTemplate,
	ADD_TEMPLATE,
	updateTemplateParam,
	UPDATE_TEMPLATE_PARAM,
	deleteTemplate,
	DELETE_TEMPLATE,
	updateSelectBox,
	UPDATE_SELECT_BOX,
	updateSelectBoxSuccess,
	UPDATE_SELECT_BOX_SUCCESS,
	updateSelectBoxFailed,
	UPDATE_SELECT_BOX_FAILED,
	templateProcessing,
	TEMPLATE_PROCESSING,
	templateProcessingSuccess,
	TEMPLATE_PROCESSING_SUCCESS,
	templateProcessingFailed,
	TEMPLATE_PROCESSING_FAILED,
	clearTemplateProcessing,
	CLEAR_TEMPLATE_PROCESSING,
	postTemplateUploadProcessing,
	POST_TEMPLATE_UPLOAD_PROCESSING,
	templateUploadProcessingSuccess,
	TEMPLATE_UPLOAD_PROCESSING_SUCCESS,
	templateUploadProcessingFailed,
	TEMPLATE_UPLOAD_PROCESSING_FAILED,
	templateUploadRejected,
	TEMPLATE_UPLOAD_REJECTED,
	dismissTemplateUploadSuccess,
	DISMISS_TEMPLATE_UPLOAD_SUCCESS,
} from '../../../../src/assets/js/react/actions/templates';

describe('Actions - templates', () => {
	let results;
	let data;

	test('searchTemplates - check if it returns the correct action', () => {
		results = searchTemplates('My Text');

		expect(results.type).toEqual(SEARCH_TEMPLATES);
		expect(results.text).toBe('My Text');
	});

	test('selectTemplate - check if it returns the correct action', () => {
		results = selectTemplate('my-id');

		expect(results.type).toEqual(SELECT_TEMPLATE);
		expect(results.id).toBe('my-id');
	});

	test('addTemplate - check if it returns the correct action', () => {
		results = addTemplate({});

		expect(results.type).toEqual(ADD_TEMPLATE);
		expect(results.template).toEqual({});
	});

	test('updateTemplateParam - check if it returns the correct action', () => {
		results = updateTemplateParam('my-id', 'my-name', 'my-value');

		expect(results.type).toEqual(UPDATE_TEMPLATE_PARAM);
		expect(results.id).toEqual('my-id');
		expect(results.name).toEqual('my-name');
		expect(results.value).toEqual('my-value');
	});

	test('deleteTemplate - check if it returns the correct action', () => {
		results = deleteTemplate('my-id');

		expect(results.type).toEqual(DELETE_TEMPLATE);
		expect(results.id).toEqual('my-id');
	});

	test('updateSelectBox - check if it returns the correct action', () => {
		results = updateSelectBox();

		expect(results.type).toEqual(UPDATE_SELECT_BOX);
	});

	test('updateSelectBoxSuccess - check if it returns the correct action', () => {
		results = updateSelectBoxSuccess('data');

		expect(results.type).toEqual(UPDATE_SELECT_BOX_SUCCESS);
		expect(results.payload).toEqual('data');
	});

	test('updateSelectBoxFailed - check if it returns the correct action', () => {
		results = updateSelectBoxFailed();

		expect(results.type).toEqual(UPDATE_SELECT_BOX_FAILED);
	});

	test('templateProcessing - check if it returns the correct action', () => {
		results = templateProcessing('templateId');

		expect(results.type).toEqual(TEMPLATE_PROCESSING);
		expect(results.payload).toEqual('templateId');
	});

	test('templateProcessingSuccess - check if it returns the correct action', () => {
		results = templateProcessingSuccess('data');

		expect(results.type).toEqual(TEMPLATE_PROCESSING_SUCCESS);
		expect(results.payload).toEqual('data');
	});

	test('templateProcessingFailed - check if it returns the correct action', () => {
		results = templateProcessingFailed('data');

		expect(results.type).toEqual(TEMPLATE_PROCESSING_FAILED);
		expect(results.payload).toEqual('data');
	});

	test('clearTemplateProcessing - check if it returns the correct action', () => {
		results = clearTemplateProcessing();

		expect(results.type).toEqual(CLEAR_TEMPLATE_PROCESSING);
	});

	test('postTemplateUploadProcessing - check if it returns the correct action', () => {
		data = { file: { data: 'text' }, filename: 'filename' };
		results = postTemplateUploadProcessing(data.file, data.filename);

		expect(results.type).toEqual(POST_TEMPLATE_UPLOAD_PROCESSING);
		expect(results.payload.file).toEqual({ data: 'text' });
		expect(results.payload.filename).toEqual('filename');
		expect(
			postTemplateUploadProcessing(data.file, data.filename).payload.id
		).not.toBe(results.payload.id);
	});

	test('templateUploadProcessingSuccess - check if it returns the correct action', () => {
		results = templateUploadProcessingSuccess(1, [{ id: 'rubix' }]);

		expect(results.type).toEqual(TEMPLATE_UPLOAD_PROCESSING_SUCCESS);
		expect(results.payload).toEqual({
			id: 1,
			templates: [{ id: 'rubix' }],
		});
	});

	test('templateUploadProcessingFailed - check if it returns the correct action', () => {
		results = templateUploadProcessingFailed(1, 'error');

		expect(results.type).toEqual(TEMPLATE_UPLOAD_PROCESSING_FAILED);
		expect(results.payload).toEqual({ id: 1, message: 'error' });
	});

	test('templateUploadRejected - check if it returns the correct action', () => {
		data = [{ filename: 'a.txt', message: 'notZip' }];
		results = templateUploadRejected(data);

		expect(results.type).toEqual(TEMPLATE_UPLOAD_REJECTED);
		expect(results.payload).toEqual(data);
	});

	test('dismissTemplateUploadSuccess - check if it returns the correct action', () => {
		results = dismissTemplateUploadSuccess();

		expect(results.type).toEqual(DISMISS_TEMPLATE_UPLOAD_SUCCESS);
	});
});
