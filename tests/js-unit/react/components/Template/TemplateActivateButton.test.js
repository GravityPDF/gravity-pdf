import React from 'react';
import { shallow } from 'enzyme';
import { findByTestAttr } from '../../testUtils';
import {
	TemplateActivateButton,
	mapDispatchToProps,
} from '../../../../../src/assets/js/react/components/Template/TemplateActivateButton';

describe('Template - TemplateActivateButton.js', () => {
	const navigate = jest.fn();
	const onTemplateSelectMock = jest.fn();

	describe('Check for redux properties', () => {
		const dispatch = jest.fn();

		test('check for mapDispatchToProps onTemplateSelect()', () => {
			mapDispatchToProps(dispatch).onTemplateSelect();

			expect(dispatch.mock.calls[0][0]).toEqual({
				type: 'SELECT_TEMPLATE',
			});
		});
	});

	describe('Component functions', () => {
		test('handleSelectTemplate() - Update our route and trigger a Redux action to select the current template', () => {
			const wrapper = shallow(
				<TemplateActivateButton
					navigate={navigate}
					onTemplateSelect={onTemplateSelectMock}
					template={{}}
				/>
			);
			const instance = wrapper.instance();
			instance.handleSelectTemplate({
				preventDefault() {},
				stopPropagation() {},
			});

			expect(navigate.mock.calls.length).toBe(1);
			expect(onTemplateSelectMock.mock.calls.length).toBe(1);
		});
	});

	test('renders <TemplateActivateButton /> component', () => {
		const wrapper = shallow(<TemplateActivateButton navigate={navigate} />);
		const component = findByTestAttr(
			wrapper,
			'component-templateActivateButton'
		);

		expect(component.length).toBe(1);
	});

	test('renders button text', () => {
		const wrapper = shallow(
			<TemplateActivateButton navigate={navigate} buttonText="Select" />
		);

		expect(wrapper.find('button').text()).toBe('Select');
	});

	test('check button click', () => {
		const wrapper = shallow(
			<TemplateActivateButton
				navigate={navigate}
				onTemplateSelect={onTemplateSelectMock}
				template={{}}
			/>
		);
		wrapper.simulate('click', {
			preventDefault() {},
			stopPropagation() {},
		});

		expect(navigate.mock.calls.length).toBe(1);
		expect(onTemplateSelectMock.mock.calls.length).toBe(1);
	});
});
