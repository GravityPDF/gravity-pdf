import React from 'react';
import { shallow } from 'enzyme';
import FontPreview from '../../../../../src/assets/js/react/components/FontManager/FontPreview';

describe('FontManager - FontPreview.js', () => {
	const font = {
		id: 'brand',
		regular: '/var/www/fonts/Brand Regular.ttf',
		italics: '',
		bold: 'Brand-Bold.ttf',
		bolditalics: '',
	};
	const samples = (wrapper) =>
		wrapper.find('[data-test="font-preview-sample"]');

	test('Has a row and a face for each style with a file', () => {
		const wrapper = shallow(<FontPreview font={font} />);
		const faces = wrapper.find('style').text();

		expect(samples(wrapper).length).toBe(2);
		expect(faces).toContain('http://x/fonts/Brand%20Regular.ttf');
		expect(faces).toContain('http://x/fonts/Brand-Bold.ttf');
		expect(faces).not.toContain('/var/www');
	});

	test('A bundled font loads from its own folder', () => {
		const wrapper = shallow(
			<FontPreview font={{ ...font, base_url: 'http://p/fonts/' }} />
		);

		expect(wrapper.find('style').text()).toContain(
			'http://p/fonts/Brand-Bold.ttf'
		);
	});

	test('Shows typed text at the chosen size', () => {
		const wrapper = shallow(<FontPreview font={font} />);

		wrapper
			.find('.font-preview-text')
			.simulate('change', { target: { value: 'Hello' } });
		wrapper
			.find('#gfpdf-font-preview-size')
			.simulate('change', { target: { value: '20' } });

		expect(samples(wrapper).at(0).text()).toBe('Hello');
		expect(samples(wrapper).at(0).prop('style').fontSize).toBe('20pt');
	});
});
