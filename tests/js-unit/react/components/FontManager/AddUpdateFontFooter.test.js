import React from 'react';
import { shallow } from 'enzyme';
import { findByTestAttr } from '../../testUtils';
import { AddUpdateFontFooter } from '../../../../../src/assets/js/react/components/FontManager/AddUpdateFontFooter';

describe('FontManager - AddFontFooter.js', () => {
	// Mock component props
	const props = {
		msg: {
			success: { addFont: 'success' },
			error: { addFont: 'error' },
		},
		loading: true,
	};

	describe('RENDERS COMPONENT', () => {
		test('render <AddFontFooter /> component', () => {
			const wrapper = shallow(<AddUpdateFontFooter {...props} />);
			const component = findByTestAttr(
				wrapper,
				'component-AddFontFooter'
			);

			expect(component.length).toBe(1);
		});

		test('render cancel button', () => {
			const wrapper = shallow(
				<AddUpdateFontFooter {...props} id="active" type="update" />
			);

			expect(wrapper.find('button.cancel').length).toBe(1);
		});

		test('render add font button', () => {
			const wrapper = shallow(<AddUpdateFontFooter {...props} />);
			expect(wrapper.find('button.primary').text()).toBe('Add Font →');
		});

		test('render update font button', () => {
			const wrapper = shallow(
				<AddUpdateFontFooter {...props} id="active" type="update" />
			);

			expect(wrapper.find('button.primary').text()).toBe('Update Font →');
		});

		test('render update panel delete icon', () => {
			const wrapper = shallow(
				<AddUpdateFontFooter {...props} id="roboto" />
			);

			expect(wrapper.find('Connect(FontDeleteButton)').length).toBe(1);
		});

		test('The primary button submits the form it was given', () => {
			const wrapper = shallow(
				<AddUpdateFontFooter {...props} formId="the-form" />
			);

			expect(wrapper.find('button.primary').prop('form')).toBe(
				'the-form'
			);
		});

		test('render loading spinner', () => {
			const wrapper = shallow(<AddUpdateFontFooter {...props} />);
			expect(wrapper.find('Spinner').length).toBe(1);
		});

		test('render success message', () => {
			const wrapper = shallow(<AddUpdateFontFooter {...props} />);
			expect(wrapper.find('span.success').length).toBe(1);
		});

		test('render error message', () => {
			const wrapper = shallow(<AddUpdateFontFooter {...props} />);
			expect(wrapper.find('span.error').length).toBe(1);
		});
	});
});
