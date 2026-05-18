import React from 'react';
import { shallow } from 'enzyme';
import { findByTestAttr } from '../../testUtils';
import TemplateButton from '../../../../../src/assets/js/react/components/Template/TemplateButton';

describe('Template - TemplateButton.js', () => {
	const navigate = jest.fn();

	describe('Component functions', () => {
		test("handleClick() - When the button is clicked we'll display the `/template` route", () => {
			const wrapper = shallow(<TemplateButton navigate={navigate} />);
			const instance = wrapper.instance();
			instance.handleClick({
				preventDefault() {},
				stopPropagation() {},
			});

			expect(navigate.mock.calls.length).toBe(1);
		});
	});

	test('renders <TemplateButton /> component', () => {
		const wrapper = shallow(<TemplateButton />);
		const component = findByTestAttr(wrapper, 'component-templateButton');

		expect(component.length).toBe(1);
	});

	test('renders button text', () => {
		const wrapper = shallow(<TemplateButton buttonText="Advanced" />);

		expect(wrapper.find('button').text()).toBe('Advanced');
	});

	test('check button click', () => {
		const wrapper = shallow(<TemplateButton navigate={navigate} />);
		wrapper.simulate('click', {
			preventDefault() {},
			stopPropagation() {},
		});

		expect(navigate.mock.calls.length).toBe(1);
	});
});
