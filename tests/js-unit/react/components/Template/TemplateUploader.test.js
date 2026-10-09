import React from 'react';
import { shallow, mount } from 'enzyme';
import { storeFactory, findByTestAttr } from '../../testUtils';
import ConnectedTemplateUploader, {
	TemplateUploader,
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
	const clearTemplateUploadProcessingMock = jest.fn();

	const uploaderProps = (props = {}) => ({
		postTemplateUploadProcessing: postTemplateUploadProcessingMock,
		clearTemplateUploadProcessing: clearTemplateUploadProcessingMock,
		templateUploadTotal: 0,
		templateUploadResults: [],
		...props,
	});

	const setupUploader = (props = {}) =>
		shallow(<TemplateUploader {...uploaderProps(props)} />);

	const statusMessages = (uploader, testAttr) =>
		findByTestAttr(shallow(uploader.instance().renderStatus()), testAttr);

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

		test('has access to the upload batch state', () => {
			wrapper = setup({
				template: {
					templateUploadTotal: 2,
					templateUploadResults: [
						{ filename: 'one.zip', success: true, templates: [] },
					],
				},
			});

			expect(wrapper.instance().props.templateUploadTotal).toBe(2);
			expect(wrapper.instance().props.templateUploadResults).toEqual([
				{ filename: 'one.zip', success: true, templates: [] },
			]);
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

	describe('handleOndrop()', () => {
		test('starts a new batch and uploads every zip that was dropped', () => {
			wrapper = setupUploader();
			wrapper
				.instance()
				.handleOndrop([
					file('gpdf-cellulose-1.4.0.zip'),
					file('gpdf-blueprint-1.0.0.zip'),
					file('gpdf-flow-2.0.0.zip'),
				]);

			expect(clearTemplateUploadProcessingMock.mock.calls.length).toBe(1);
			expect(wrapper.state('rejections')).toEqual([]);
			expect(postTemplateUploadProcessingMock.mock.calls.length).toBe(3);
		});

		test('reports each rejected file and uploads the rest', () => {
			wrapper = setupUploader();
			wrapper.instance().handleOndrop(
				[file('gpdf-cellulose-1.4.0.zip')],
				[
					{
						file: file('not-a-template.txt'),
						errors: [{ code: 'file-invalid-type' }],
					},
					{
						file: file('huge.zip'),
						errors: [{ code: 'file-too-large' }],
					},
				]
			);

			expect(wrapper.state('rejections')).toEqual([
				{ filename: 'not-a-template.txt', message: 'notZip' },
				{ filename: 'huge.zip', message: 'tooBig' },
			]);
			expect(postTemplateUploadProcessingMock.mock.calls.length).toBe(1);
		});

		test('ignores an empty drop', () => {
			wrapper = setupUploader();
			wrapper.instance().handleOndrop([], []);

			expect(postTemplateUploadProcessingMock.mock.calls.length).toBe(0);
			expect(clearTemplateUploadProcessingMock.mock.calls.length).toBe(0);
		});

		test('a drop during an upload joins the batch in flight', () => {
			wrapper = setupUploader({ templateUploadTotal: 1 });
			wrapper.setState({
				rejections: [{ filename: 'a.txt', message: 'notZip' }],
			});
			wrapper.instance().handleOndrop(
				[file('two.zip')],
				[
					{
						file: file('b.txt'),
						errors: [{ code: 'file-invalid-type' }],
					},
				]
			);

			expect(clearTemplateUploadProcessingMock.mock.calls.length).toBe(0);
			expect(wrapper.state('rejections').length).toBe(2);
			expect(postTemplateUploadProcessingMock.mock.calls.length).toBe(1);
		});
	});

	describe('Upload status', () => {
		test('keeps the progress notice up until every upload in the batch reports back', () => {
			wrapper = setupUploader({
				templateUploadTotal: 2,
				templateUploadResults: [
					{ success: true, filename: 'one.zip', templates: [] },
				],
			});

			expect(wrapper.instance().isUploading).toBe(true);

			wrapper.setProps({
				templateUploadResults: [
					{ success: true, filename: 'one.zip', templates: [] },
					{ success: true, filename: 'two.zip', templates: [] },
				],
			});

			expect(wrapper.instance().isUploading).toBe(false);
		});

		test('still reports success when the last zip in the batch fails, alongside its error', () => {
			wrapper = setupUploader({
				templateUploadTotal: 2,
				templateUploadResults: [
					{ success: true, filename: 'one.zip', templates: [] },
					{ success: false, filename: 'two.zip', message: 'boom' },
				],
			});

			const errors = statusMessages(
				wrapper,
				'component-stateError-showMessage'
			);

			expect(errors.length).toBe(1);
			expect(errors.prop('text')).toBe('two.zip: boom');
			expect(
				statusMessages(wrapper, 'component-stateMessage-showMessage')
					.length
			).toBe(1);
		});

		test('falls back to the generic error when the server gave no reason', () => {
			wrapper = setupUploader({
				templateUploadTotal: 1,
				templateUploadResults: [
					{ success: false, filename: 'one.zip', message: '' },
				],
			});

			expect(
				statusMessages(
					wrapper,
					'component-stateError-showMessage'
				).prop('text')
			).toBe('one.zip: genericError');
		});

		test('lists the rejected files before the failed uploads', () => {
			wrapper = setupUploader({
				templateUploadTotal: 1,
				templateUploadResults: [
					{ success: false, filename: 'one.zip', message: 'boom' },
				],
			});
			wrapper.setState({
				rejections: [{ filename: 'a.txt', message: 'notZip' }],
			});

			const errors = statusMessages(
				wrapper,
				'component-stateError-showMessage'
			);

			expect(errors.map((error) => error.prop('text'))).toEqual([
				'a.txt: notZip',
				'one.zip: boom',
			]);
		});

		test('removeMessage() hides the success message', () => {
			wrapper = setupUploader({
				templateUploadTotal: 1,
				templateUploadResults: [
					{ success: true, filename: 'one.zip', templates: [] },
				],
			});
			wrapper.instance().removeMessage();

			expect(wrapper.instance().renderStatus()).toBe(null);
		});
	});

	describe('componentWillUnmount()', () => {
		test('clears a finished batch', () => {
			wrapper = setupUploader({
				templateUploadTotal: 1,
				templateUploadResults: [
					{ success: true, filename: 'one.zip', templates: [] },
				],
			});
			wrapper.instance().componentWillUnmount();

			expect(clearTemplateUploadProcessingMock.mock.calls.length).toBe(1);
		});

		test('leaves a batch in flight alone', () => {
			wrapper = setupUploader({ templateUploadTotal: 1 });
			wrapper.instance().componentWillUnmount();

			expect(clearTemplateUploadProcessingMock.mock.calls.length).toBe(0);
		});
	});

	test('renders <TemplateUploader /> component', () => {
		wrapper = setupUploader();
		component = findByTestAttr(wrapper, 'component-dropzone');

		expect(component.length).toBe(1);
	});

	test('has react-dropzone reject anything but a zip within the size limit', () => {
		wrapper = setupUploader();
		component = findByTestAttr(wrapper, 'component-dropzone');

		expect(component.prop('accept')).toEqual({
			'application/zip': ['.zip'],
		});
		expect(component.prop('maxSize')).toBe(1000);
	});

	test('renders the upload progress notice while a batch is in flight', () => {
		wrapper = mount(
			<TemplateUploader {...uploaderProps({ templateUploadTotal: 1 })} />
		);

		expect(
			findByTestAttr(wrapper, 'component-templateUploaderStatus').text()
		).toContain('uploading');
	});
});
