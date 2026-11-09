import React from 'react';
import { shallow } from 'enzyme';
import { findByTestAttr } from '../../testUtils';
import DeleteConfirm from '../../../../../src/assets/js/react/components/FontManager/DeleteConfirm';

describe('FontManager - DeleteConfirm.js', () => {
	test('Says what deleting does and calls back for each choice', () => {
		const onConfirm = jest.fn();
		const onCancel = jest.fn();
		const wrapper = shallow(
			<DeleteConfirm
				message="Sure?"
				onConfirm={onConfirm}
				onCancel={onCancel}
			/>
		);

		expect(
			findByTestAttr(wrapper, 'component-DeleteConfirm').find('p').text()
		).toBe('Sure?');

		wrapper.find('.font-delete-confirm-delete').simulate('click');
		expect(onConfirm).toHaveBeenCalledTimes(1);

		wrapper.find('.font-delete-confirm-cancel').simulate('click');
		expect(onCancel).toHaveBeenCalledTimes(1);
	});

	test('Titles the dialog, and Escape or the scrim cancels without closing the Font Manager', () => {
		const onCancel = jest.fn();
		const stopPropagation = jest.fn();
		const wrapper = shallow(
			<DeleteConfirm
				message="Sure?"
				onConfirm={jest.fn()}
				onCancel={onCancel}
			/>
		);
		const scrim = findByTestAttr(wrapper, 'component-DeleteConfirm');

		expect(wrapper.find('[role="alertdialog"] h3').text()).toBe(
			'Delete font?'
		);

		scrim.simulate('keydown', { key: 'Enter', stopPropagation });
		expect(onCancel).not.toHaveBeenCalled();

		scrim.simulate('keydown', { key: 'Escape', stopPropagation });
		expect(onCancel).toHaveBeenCalledTimes(1);
		expect(stopPropagation).toHaveBeenCalledTimes(1);

		scrim.simulate('click');
		expect(onCancel).toHaveBeenCalledTimes(2);

		wrapper
			.find('[role="alertdialog"]')
			.simulate('click', { stopPropagation });
		expect(onCancel).toHaveBeenCalledTimes(2);
	});
});
