import React from 'react';
import { mount, shallow } from 'enzyme';
import { FontUsage } from '../../../../../src/assets/js/react/components/FontManager/FontUsage';

describe('FontManager - FontUsage.js', () => {
	const fontUsage = {
		arimo: {
			reasons: ['Default font'],
			pdfs_label: 'Used by 1 PDF',
		},
		system: {
			reasons: ['Default font'],
			pdfs_label: '',
		},
	};
	const pdfs = [{ form: 'Contact', name: 'Invoice', url: 'http://x/?pid=a' }];

	const render = (props) =>
		mount(
			<FontUsage
				id="arimo"
				fontUsage={fontUsage}
				getFontPdfs={jest.fn()}
				{...props}
			/>
		);

	test('Shows nothing until usage loads', () => {
		const wrapper = shallow(
			<FontUsage id="arimo" fontUsage={null} getFontPdfs={jest.fn()} />
		);

		expect(wrapper.isEmptyRender()).toBe(true);
	});

	test('Says when a font is not in use', () => {
		const wrapper = render({ id: 'other' });

		expect(wrapper.find('.font-unused').text()).toBe('Not in use');
	});

	test('Lists the reasons and links to each PDF in a new tab', () => {
		const wrapper = render({ pdfs });
		const link = wrapper.find('[data-test="font-list-pdfs"] a');

		expect(wrapper.text()).toContain('Default font');
		expect(wrapper.text()).toContain('Used by 1 PDF');
		expect(link.prop('href')).toBe('http://x/?pid=a');
		expect(link.prop('target')).toBe('_blank');
		expect(link.text()).toContain('Invoice');
		expect(wrapper.find('.font-pdf-form').text()).toBe('Contact');
	});

	test('Loads the PDFs of a font that has some', () => {
		const getFontPdfs = jest.fn();

		render({ getFontPdfs });

		expect(getFontPdfs).toHaveBeenCalledWith('arimo');
	});

	test('Requests nothing for a font no PDF uses, before usage loads, or once loaded', () => {
		const getFontPdfs = jest.fn();

		render({ id: 'system', getFontPdfs });
		render({ fontUsage: null, getFontPdfs });
		render({ pdfs, getFontPdfs });

		expect(getFontPdfs).not.toHaveBeenCalled();
	});

	test('Still shows the count while its PDFs have not loaded', () => {
		const wrapper = render();

		expect(wrapper.text()).toContain('Used by 1 PDF');
		expect(wrapper.find('[data-test="font-list-pdfs"] a')).toHaveLength(0);
	});
});
