import React from 'react';
import { shallow } from 'enzyme';
import { FontInstallButton } from '../../../../../src/assets/js/react/components/FontManager/FontInstallButton';

describe('FontManager - FontInstallButton.js', () => {
	const render = (props) =>
		shallow(
			<FontInstallButton
				id="garuda"
				installingFont={null}
				installFont={jest.fn()}
				{...props}
			/>
		);

	test('Installs the font', () => {
		const installFont = jest.fn();

		render({ installFont })
			.find('[data-test="font-install"]')
			.simulate('click');

		expect(installFont).toHaveBeenCalledWith('garuda');
	});

	test('Shows a spinner while this font installs', () => {
		const wrapper = render({ installingFont: 'garuda' });

		expect(wrapper.find('Spinner').exists()).toBe(true);
		expect(wrapper.find('[data-test="font-install"]').exists()).toBe(false);
	});

	test('Waits while another font installs', () => {
		const wrapper = render({ installingFont: 'other' });

		expect(
			wrapper.find('[data-test="font-install"]').prop('disabled')
		).toBe(true);
	});
});
