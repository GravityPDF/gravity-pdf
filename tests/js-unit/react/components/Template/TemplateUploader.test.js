import React from 'react';
import { shallow, mount } from 'enzyme';
import { storeFactory, findByTestAttr } from '../../testUtils';
import ConnectedTemplateUploader, {
	TemplateUploader,
	DEFAULT_MAX_FILE_SIZE,
	mapDispatchToProps,
} from '../../../../../src/assets/js/react/components/Template/TemplateUploader';

const file = (name, size = 1137334) => ({
	lastModified: 1552267520000,
	name,
	path: name,
	size,
	type: 'application/zip',
	webkitRelativePath: '',
});

describe('Template - TemplateUploader.js', () => {
	let wrapper;
	let component;
	const postTemplateUploadProcessingMock = jest.fn();
	const addNewTemplateMock = jest.fn();
	const clearTemplateUploadProcessingMock = jest.fn();
	const updateTemplateParamMock = jest.fn();

	const setupUploader = (props = {}) =>
		shallow(
			<TemplateUploader
				postTemplateUploadProcessing={postTemplateUploadProcessingMock}
				clearTemplateUploadProcessing={
					clearTemplateUploadProcessingMock
				}
				{...props}
			/>
		);

	beforeEach(() => jest.clearAllMocks());

	describe('Check for redux properties', () => {
		const setup = (state = {}) => {
			const store = storeFactory(state);
			wrapper = shallow(<ConnectedTemplateUploader store={store} />)
				.dive()
				.dive();

			return wrapper;
		};
		const dispatch = jest.fn();

		test('has access to `list` state', () => {
			wrapper = setup();
			const templatesProp = wrapper.instance().props.templates;

			expect(templatesProp).toBeInstanceOf(Array);
		});

		test('has access to `templateUploadResults` state', () => {
			wrapper = setup({
				template: {
					templateUploadResults: [
						{ filename: 'one.zip', success: true, templates: [] },
					],
				},
			});
			const results = wrapper.instance().props.templateUploadResults;

			expect(results).toEqual([
				{ filename: 'one.zip', success: true, templates: [] },
			]);
		});

		test('check for mapDispatchToProps addNewTemplate()', () => {
			mapDispatchToProps(dispatch).addNewTemplate();

			expect(dispatch.mock.calls[0][0]).toEqual({
				type: 'ADD_TEMPLATE',
			});
		});

		test('check for mapDispatchToProps updateTemplateParam()', () => {
			mapDispatchToProps(dispatch).updateTemplateParam();

			expect(dispatch.mock.calls[0][0]).toEqual({
				type: 'UPDATE_TEMPLATE_PARAM',
			});
		});

		test('check for mapDispatchToProps postTemplateUploadProcessing()', () => {
			mapDispatchToProps(dispatch).postTemplateUploadProcessing();

			expect(dispatch.mock.calls[0][0].type).toBe(
				'POST_TEMPLATE_UPLOAD_PROCESSING'
			);
		});

		test('check for mapDispatchToProps clearTemplateUploadProcessing()', () => {
			mapDispatchToProps(dispatch).clearTemplateUploadProcessing();

			expect(dispatch.mock.calls[0][0]).toEqual({
				type: 'CLEAR_TEMPLATE_UPLOAD_PROCESSING',
			});
		});
	});

	describe('Component functions', () => {
		test('handleOndrop() - uploads every zip that was dropped', () => {
			wrapper = setupUploader();
			wrapper
				.instance()
				.handleOndrop([
					file('gpdf-cellulose-1.4.0.zip'),
					file('gpdf-blueprint-1.0.0.zip'),
					file('gpdf-flow-2.0.0.zip'),
				]);

			expect(wrapper.instance().isUploading).toBe(true);
			expect(wrapper.state('total')).toBe(3);
			expect(wrapper.state('completed')).toBe(0);
			expect(wrapper.state('errors')).toEqual([]);
			expect(postTemplateUploadProcessingMock.mock.calls.length).toBe(3);
		});

		test('handleOndrop() - reports each invalid file and uploads the rest', () => {
			wrapper = setupUploader({
				filenameErrorText: 'notZip',
				filesizeErrorText: 'tooBig',
			});
			wrapper
				.instance()
				.handleOndrop([
					file('gpdf-cellulose-1.4.0.zip'),
					file('not-a-template.txt'),
					file('huge.zip', DEFAULT_MAX_FILE_SIZE + 1),
				]);

			expect(wrapper.state('errors')).toEqual([
				{ filename: 'not-a-template.txt', message: 'notZip' },
				{ filename: 'huge.zip', message: 'tooBig' },
			]);
			expect(wrapper.state('total')).toBe(1);
			expect(postTemplateUploadProcessingMock.mock.calls.length).toBe(1);
		});

		test('handleOndrop() - ignores an empty drop', () => {
			wrapper = setupUploader();
			wrapper.instance().handleOndrop([]);

			expect(wrapper.instance().isUploading).toBe(false);
			expect(postTemplateUploadProcessingMock.mock.calls.length).toBe(0);
		});

		test('validateFile() - only accepts a .zip extension', () => {
			wrapper = setupUploader({ filenameErrorText: 'notZip' });

			expect(
				wrapper.instance().validateFile(file('gpdf-cellulose.zip'))
			).toBe('');
			expect(
				wrapper.instance().validateFile(file('gpdf-cellulose.tar'))
			).toBe('notZip');
		});

		test('validateFile() - rejects anything over the upload limit', () => {
			wrapper = setupUploader({
				filesizeErrorText: 'tooBig',
				maxFileSize: 1000,
			});

			expect(wrapper.instance().validateFile(file('a.zip', 1000))).toBe(
				''
			);
			expect(wrapper.instance().validateFile(file('a.zip', 1001))).toBe(
				'tooBig'
			);
		});

		test('validateFile() - accepts the stringified limit wp_localize_script() produces', () => {
			wrapper = setupUploader({
				filesizeErrorText: 'tooBig',
				maxFileSize: '1000',
			});

			expect(wrapper.instance().validateFile(file('a.zip', 1000))).toBe(
				''
			);
			expect(wrapper.instance().validateFile(file('a.zip', 1001))).toBe(
				'tooBig'
			);
		});

		test('validateFile() - falls back to the default limit when the server sent none', () => {
			wrapper = setupUploader({ filesizeErrorText: 'tooBig' });

			expect(
				wrapper
					.instance()
					.validateFile(file('a.zip', DEFAULT_MAX_FILE_SIZE))
			).toBe('');
			expect(
				wrapper
					.instance()
					.validateFile(file('a.zip', DEFAULT_MAX_FILE_SIZE + 1))
			).toBe('tooBig');
		});

		test('addTemplatesToStore() - adds new templates and flags existing ones as updated', () => {
			const templates = [
				{ template: 'Blank Slate', id: 'blank-slate' },
				{ template: 'Rubix', id: 'rubix' },
			];

			wrapper = setupUploader({
				templates,
				addNewTemplate: addNewTemplateMock,
				updateTemplateParam: updateTemplateParamMock,
				installSuccessText: 'installed',
				installUpdatedText: 'updated',
			});

			wrapper.instance().addTemplatesToStore([
				{ template: 'Cellulose', id: 'gpdf-cellulose' },
				{ template: 'Rubix', id: 'rubix' },
			]);

			expect(addNewTemplateMock.mock.calls.length).toBe(1);
			expect(addNewTemplateMock.mock.calls[0][0].message).toBe(
				'installed'
			);
			expect(updateTemplateParamMock.mock.calls[0]).toEqual([
				'rubix',
				'message',
				'updated',
			]);
		});

		test('removeMessage() - Remove message from state once the timeout has finished', () => {
			wrapper = setupUploader();
			wrapper.setState({ showSuccess: true });
			wrapper.instance().removeMessage();

			expect(wrapper.state('showSuccess')).toBe(false);
		});
	});

	describe('Run Lifecycle methods', () => {
		const templates = [{ template: 'Rubix', id: 'rubix' }];

		const setupBatch = (total, props = {}) => {
			const uploader = setupUploader({
				templates,
				addNewTemplate: addNewTemplateMock,
				updateTemplateParam: updateTemplateParamMock,
				templateSuccessfullyInstalledUpdated: 'successText',
				genericUploadErrorText: 'genericError',
				templateUploadResults: [],
				...props,
			});

			uploader.setState({ total, completed: 0 });

			return uploader;
		};

		/* The shallow renderer doesn't run lifecycle hooks, so drive componentDidUpdate ourselves */
		const applyResults = (uploader, results) => {
			const prevProps = { ...uploader.instance().props };
			uploader.setProps({ templateUploadResults: results });
			uploader.instance().componentDidUpdate(prevProps);
		};

		test('componentDidUpdate() - keeps the spinner up until every upload in the batch reports back', () => {
			wrapper = setupBatch(2);

			applyResults(wrapper, [
				{
					success: true,
					filename: 'one.zip',
					templates: [{ template: 'Cellulose', id: 'cellulose' }],
				},
			]);

			expect(wrapper.state('completed')).toBe(1);
			expect(wrapper.instance().isUploading).toBe(true);
			expect(clearTemplateUploadProcessingMock.mock.calls.length).toBe(0);

			applyResults(wrapper, [
				{
					success: true,
					filename: 'one.zip',
					templates: [{ template: 'Cellulose', id: 'cellulose' }],
				},
				{
					success: true,
					filename: 'two.zip',
					templates: [{ template: 'Flow', id: 'flow' }],
				},
			]);

			expect(wrapper.state('completed')).toBe(2);
			expect(wrapper.instance().isUploading).toBe(false);
			expect(wrapper.state('showSuccess')).toBe(true);
			expect(addNewTemplateMock.mock.calls.length).toBe(2);
			expect(clearTemplateUploadProcessingMock.mock.calls.length).toBe(1);
		});

		test('componentDidUpdate() - handles several results landing in the same render', () => {
			wrapper = setupBatch(2);

			applyResults(wrapper, [
				{ success: true, filename: 'one.zip', templates: [] },
				{ success: false, filename: 'two.zip', message: 'boom' },
			]);

			expect(wrapper.state('completed')).toBe(2);
			expect(wrapper.instance().isUploading).toBe(false);
			expect(wrapper.state('errors')).toEqual([
				{ filename: 'two.zip', message: 'boom' },
			]);
			expect(wrapper.state('showSuccess')).toBe(true);
		});

		test('componentDidUpdate() - still reports success when the last zip in the batch fails', () => {
			wrapper = setupBatch(2);

			applyResults(wrapper, [
				{
					success: true,
					filename: 'one.zip',
					templates: [{ template: 'Cellulose', id: 'cellulose' }],
				},
			]);

			applyResults(wrapper, [
				{ success: true, filename: 'one.zip', templates: [] },
				{ success: false, filename: 'two.zip', message: 'boom' },
			]);

			expect(wrapper.state('showSuccess')).toBe(true);
			expect(wrapper.state('errors')).toEqual([
				{ filename: 'two.zip', message: 'boom' },
			]);
		});

		test('componentDidUpdate() - falls back to the generic error when the server gave no reason', () => {
			wrapper = setupBatch(1);

			applyResults(wrapper, [
				{ success: false, filename: 'one.zip', message: '' },
			]);

			expect(wrapper.state('errors')).toEqual([
				{ filename: 'one.zip', message: 'genericError' },
			]);
			expect(wrapper.state('showSuccess')).toBe(false);
			expect(wrapper.instance().isUploading).toBe(false);
		});

		test('componentDidUpdate() - ignores the store being cleared', () => {
			wrapper = setupBatch(1);
			wrapper.setState({ completed: 1 });

			applyResults(wrapper, []);

			expect(wrapper.state('completed')).toBe(1);
			expect(addNewTemplateMock.mock.calls.length).toBe(0);
		});
	});

	test('renders <TemplateUploader /> component', () => {
		wrapper = shallow(<TemplateUploader />);
		component = findByTestAttr(wrapper, 'component-dropzone');

		expect(component.length).toBe(1);
	});

	test('renders <ShowMessage /> component for each error in state.errors', async () => {
		wrapper = mount(<TemplateUploader />);
		React.act(() =>
			wrapper.setState({
				errors: [
					{ filename: 'one.zip', message: 'errorText' },
					{ filename: 'two.zip', message: 'errorText' },
				],
			})
		);
		component = findByTestAttr(wrapper, 'component-stateError-showMessage');

		expect(component.length).toBe(2);
		expect(component.first().prop('text')).toBe('one.zip: errorText');
	});

	test('renders the upload progress notice while a batch is in flight', async () => {
		wrapper = mount(<TemplateUploader uploadInProgressText="uploading" />);
		React.act(() => wrapper.setState({ total: 1, completed: 0 }));

		expect(
			findByTestAttr(wrapper, 'component-templateUploaderStatus').text()
		).toContain('uploading');
	});
});
