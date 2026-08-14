import React from 'react';
import { mount } from 'enzyme';
import { findByTestAttr } from '../../testUtils';
import TemplateUploaderTile from '../../../../../src/assets/js/react/components/Template/TemplateUploaderTile';
import { TemplateUploaderContext } from '../../../../../src/assets/js/react/components/Template/TemplateUploaderContext';

describe('Template - TemplateUploaderTile.js', () => {
	const setup = (context = {}) =>
		mount(
			<TemplateUploaderContext.Provider
				value={{
					open: jest.fn(),
					ajax: false,
					addTemplateText: 'Add New Template',
					templateInstallInstructions: 'instructions',
					...context,
				}}
			>
				<TemplateUploaderTile />
			</TemplateUploaderContext.Provider>
		);

	test('renders the tile with the text supplied by <TemplateUploader />', () => {
		const wrapper = setup();

		expect(
			findByTestAttr(wrapper, 'component-templateUploaderTile').length
		).toBe(1);
		expect(wrapper.find('h2.theme-name').text()).toBe('Add New Template');
		expect(
			wrapper.find('.gfpdf-template-install-instructions').text()
		).toBe('instructions');
	});

	test('opens the file picker owned by <TemplateUploader /> instead of following the link', () => {
		const open = jest.fn();
		const preventDefault = jest.fn();
		const wrapper = setup({ open });

		wrapper.find('a').simulate('click', { preventDefault });

		expect(open).toHaveBeenCalledTimes(1);
		expect(preventDefault).toHaveBeenCalledTimes(1);
	});

	test('shows the spinner while an upload is in flight', () => {
		expect(setup({ ajax: true }).find('a').hasClass('doing-ajax')).toBe(
			true
		);
		expect(setup().find('a').hasClass('doing-ajax')).toBe(false);
	});
});
