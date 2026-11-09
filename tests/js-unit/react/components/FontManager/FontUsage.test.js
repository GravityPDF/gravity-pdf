import React from 'react';
import { shallow } from 'enzyme';
import { FontUsage } from '../../../../../src/assets/js/react/components/FontManager/FontUsage';

describe('FontManager - FontUsage.js', () => {
	const fontUsage = {
		arimo: {
			reasons: ['Default font'],
			pdfs_label: 'Used by 1 PDF',
			pdfs: [
				{ form: 'Contact', name: 'Invoice', url: 'http://x/?pid=a' },
			],
		},
	};

	test('Shows nothing until usage loads', () => {
		const wrapper = shallow(<FontUsage id="arimo" fontUsage={null} />);

		expect(wrapper.isEmptyRender()).toBe(true);
	});

	test('Says when a font is not in use', () => {
		const wrapper = shallow(<FontUsage id="other" fontUsage={fontUsage} />);

		expect(wrapper.find('.font-unused').text()).toBe('Not in use');
	});

	test('Lists the reasons and links to each PDF in a new tab', () => {
		const wrapper = shallow(<FontUsage id="arimo" fontUsage={fontUsage} />);
		const link = wrapper.find('[data-test="font-list-pdfs"] a');

		expect(wrapper.text()).toContain('Default font');
		expect(wrapper.text()).toContain('Used by 1 PDF');
		expect(link.prop('href')).toBe('http://x/?pid=a');
		expect(link.prop('target')).toBe('_blank');
		expect(link.text()).toContain('Invoice');
		expect(wrapper.find('.font-pdf-form').text()).toBe('Contact');
	});
});
