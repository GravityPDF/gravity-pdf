import React from 'react';
import { shallow } from 'enzyme';
import { FontStatus } from '../../../../../src/assets/js/react/components/FontManager/FontStatus';

describe('FontManager - FontStatus.js', () => {
	test('Shows the selected font as active', () => {
		const wrapper = shallow(
			<FontStatus
				id="arimo"
				selectedFont="arimo"
				selectFont={jest.fn()}
			/>
		);

		expect(wrapper.find('[data-test="font-active"]').exists()).toBe(true);
		expect(wrapper.find('button').exists()).toBe(false);
	});

	test('Offers to make another font active', () => {
		const selectFont = jest.fn();
		const wrapper = shallow(
			<FontStatus
				id="arimo"
				selectedFont="other"
				selectFont={selectFont}
			/>
		);

		wrapper.find('button').simulate('click');

		expect(selectFont).toHaveBeenCalledWith('arimo');
	});
});
