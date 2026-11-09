import React from 'react';
import { shallow } from 'enzyme';
import { FontDeleteButton } from '../../../../../src/assets/js/react/components/FontManager/FontDeleteButton';

describe('FontManager - FontDeleteButton.js', () => {
	const render = (props) =>
		shallow(
			<FontDeleteButton
				id="garuda"
				type="core"
				message="Sure?"
				fontUsage={{}}
				loading={false}
				deleteFont={jest.fn()}
				{...props}
			/>
		);

	test('Asks to confirm, then deletes', () => {
		const deleteFont = jest.fn();
		const wrapper = render({ deleteFont });

		wrapper.find('.font-delete').simulate('click');
		expect(deleteFont).not.toHaveBeenCalled();

		const confirm = wrapper.find('DeleteConfirm');
		expect(confirm.prop('message')).toBe('Sure?');

		confirm.prop('onConfirm')();
		expect(deleteFont).toHaveBeenCalledWith('garuda', 'core');
		expect(wrapper.find('DeleteConfirm').exists()).toBe(false);
	});

	test('Cancel keeps the font', () => {
		const deleteFont = jest.fn();
		const wrapper = render({ deleteFont });

		wrapper.find('.font-delete').simulate('click');
		wrapper.find('DeleteConfirm').prop('onCancel')();

		expect(wrapper.find('DeleteConfirm').exists()).toBe(false);
		expect(deleteFont).not.toHaveBeenCalled();
	});

	test('Is off until usage loads', () => {
		const wrapper = render({ fontUsage: null });

		expect(wrapper.find('.font-delete').prop('aria-disabled')).toBe(true);

		wrapper.find('.font-delete').simulate('click');
		expect(wrapper.find('DeleteConfirm').exists()).toBe(false);
	});

	test('Is off while in use, and says why', () => {
		const wrapper = render({
			fontUsage: {
				garuda: {
					reasons: ['Default font'],
					pdfs_label: 'Used by 1 PDF',
					pdfs: [],
				},
			},
		});
		const button = wrapper.find('.font-delete');

		expect(button.prop('aria-disabled')).toBe(true);
		expect(button.prop('title')).toBe('Default font, Used by 1 PDF');

		button.simulate('click');
		expect(wrapper.find('DeleteConfirm').exists()).toBe(false);
	});

	test('Shows a spinner while deleting', () => {
		const wrapper = render({ loading: true });

		expect(wrapper.find('Spinner').exists()).toBe(true);
		expect(wrapper.find('.font-delete').exists()).toBe(false);
	});
});
