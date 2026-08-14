import React from 'react';
import { shallow } from 'enzyme';
import { storeFactory, findByTestAttr } from '../../testUtils';
import ConnectedTemplateList, {
	TemplateList,
} from '../../../../../src/assets/js/react/components/Template/TemplateList';

describe('Template - TemplateList.js', () => {
	describe('Check for redux properties', () => {
		const setup = (state = {}) => {
			const store = storeFactory(state);
			const wrapper = shallow(<ConnectedTemplateList store={store} />)
				.dive()
				.dive();

			return wrapper;
		};

		test('has access to `list` state', () => {
			const wrapper = setup();
			const list = wrapper.instance().props.templates;

			expect(list).toBeInstanceOf(Array);
			expect(list.length).toBeGreaterThan(0);
		});
	});

	const templates = [
		{ id: 'blank-slate', template: 'Blank Slate' },
		{ id: 'focus-gravity', template: 'Focus Gravity' },
		{ id: 'rubix', template: 'Rubix' },
		{ id: 'zadani', template: 'Zadani' },
	];
	const wrapper = shallow(<TemplateList templates={templates} />);

	test('renders <TemplateList /> component', () => {
		const component = findByTestAttr(wrapper, 'component-templateList');

		expect(component.length).toBe(1);
	});

	test('renders <TemplateHeaderTitle /> component', () => {
		const header = findByTestAttr(wrapper, 'component-templateList').prop(
			'header'
		);

		expect(header.props['data-test']).toBe('component-templateHeaderTitle');
	});

	test('renders <TemplateUploaderTile /> component', () => {
		expect(
			findByTestAttr(wrapper, 'component-templateUploaderTile').length
		).toBe(1);
	});

	test('renders <TemplateSearch /> component', () => {
		expect(wrapper.find('Connect(TemplateSearch)').length).toBe(1);
	});

	test('renders <TemplateListItem /> component', () => {
		expect(
			wrapper
				.find({
					'data-test': 'component-templateListItem',
				})
				.exists()
		).toBe(true);
	});

	test('renders <TemplateUploader /> component', () => {
		expect(wrapper.find('Connect(TemplateUploader)').length).toBe(1);
	});
});
