import React from 'react';
import { shallow } from 'enzyme';
import { findByTestAttr } from '../../testUtils';
import AddFont from '../../../../../src/assets/js/react/components/FontManager/AddFont';

describe('FontManager - AddFont.js', () => {
	// Mock component props
	const props = {
		label: '',
		onHandleInputChange: jest.fn(),
		onHandleUpload: jest.fn(),
		onHandleDeleteFontStyle: jest.fn(),
		onHandleSubmit: jest.fn(),
		validateLabel: false,
		validateRegular: false,
		fontStyles: {},
		msg: {},
		loading: false,
		tabIndexFontName: '',
		tabIndexFontFiles: '',
		tabIndexFooterButtons: '',
	};

	describe('RENDERS COMPONENT', () => {
		test('render <AddFont /> component', () => {
			const wrapper = shallow(<AddFont {...props} />);
			const component = findByTestAttr(wrapper, 'component-AddFont');

			expect(component.length).toBe(1);
		});

		test('render font name input box', () => {
			const wrapper = shallow(<AddFont {...props} />);
			expect(wrapper.find('input#gfpdf-add-font-name-input').length).toBe(
				1
			);
		});

		test('call input box onChange event', () => {
			const wrapper = shallow(<AddFont {...props} />);
			wrapper
				.find('input#gfpdf-add-font-name-input')
				.simulate('change', { target: { value: 'Your new Value' } });

			expect(props.onHandleInputChange).toHaveBeenCalledTimes(1);
		});

		test('render font name validation error', () => {
			const wrapper = shallow(<AddFont {...props} />);
			expect(wrapper.find('span.required').length).toBe(1);
		});

		test('hide font name validation error', () => {
			const validateLabel = true;
			const wrapper = shallow(
				<AddFont {...props} validateLabel={validateLabel} />
			);

			expect(wrapper.find('span.required').length).toBe(0);
		});

		test('render font files label text', () => {
			const wrapper = shallow(<AddFont {...props} />);
			expect(wrapper.find('label').at(1).text()).toBe('Font Files');
		});

		test('render <FontVariant /> component', () => {
			const wrapper = shallow(<AddFont {...props} />);
			expect(wrapper.find('FontVariant').length).toBe(1);
		});

		test('render <AddUpdateFontFooter /> component', () => {
			const wrapper = shallow(<AddFont {...props} />);
			expect(wrapper.find('Connect(AddUpdateFontFooter)').length).toBe(1);
		});
	});
});
