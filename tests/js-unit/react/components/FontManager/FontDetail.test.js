import React from 'react';
import { shallow } from 'enzyme';
import { FontDetail } from '../../../../../src/assets/js/react/components/FontManager/FontDetail';

describe('FontManager - FontDetail.js', () => {
	const core = {
		id: 'garuda',
		font_name: 'Garuda',
		type: 'core',
		regular: 'Garuda.ttf',
		italics: '',
		bold: '',
		bolditalics: '',
		size_label: '56 KB',
		can_delete: true,
		auto_install: true,
	};
	const render = (props) => shallow(<FontDetail font={core} {...props} />);

	beforeEach(() => {
		GFPDF.fontManagerCoreConfirm = 'Delete %s, it comes back';
		GFPDF.fontManagerCoreConfirmOffline = 'Delete %s, it stays gone';
	});

	test('A bundled font has no delete', () => {
		const wrapper = render({
			font: { ...core, type: 'bundled', can_delete: false },
		});

		expect(wrapper.find('Connect(FontDeleteButton)').exists()).toBe(false);
	});

	test('A core font can be deleted, saying whether it comes back', () => {
		const button = (font) =>
			render({ font }).find('Connect(FontDeleteButton)');

		expect(button(core).prop('message')).toBe(
			'Delete Garuda, it comes back'
		);
		expect(button({ ...core, auto_install: false }).prop('message')).toBe(
			'Delete Garuda, it stays gone'
		);
	});

	test('Says why when this user cannot delete core fonts', () => {
		GFPDF.fontManagerNetworkOnly = 'Network only';
		const wrapper = render({ font: { ...core, can_delete: false } });

		expect(wrapper.find('Connect(FontDeleteButton)').exists()).toBe(false);
		expect(
			wrapper.find('[data-test="font-list-network-note"]').text()
		).toBe('Network only');
	});

	test('An available font offers install in place of delete, with its preview and languages', () => {
		GFPDF.fontManagerAvailableHint = 'Install it';
		const wrapper = render({
			font: {
				...core,
				type: 'available',
				can_delete: false,
				can_install: true,
				languages: ['Thai', 'Lao'],
			},
		});

		expect(wrapper.find('Connect(FontInstallButton)').prop('id')).toBe(
			'garuda'
		);
		expect(wrapper.find('FontPreview').exists()).toBe(true);
		expect(wrapper.find('[data-test="font-languages"] p').text()).toBe(
			'Thai, Lao'
		);
		expect(wrapper.find('Connect(FontDeleteButton)').exists()).toBe(false);
		expect(wrapper.find('.font-detail-desc').text()).toBe('Install it');
	});

	test('A partly installed font offers install and delete', () => {
		const wrapper = render({ font: { ...core, can_install: true } });

		expect(wrapper.find('Connect(FontInstallButton)').exists()).toBe(true);
		expect(wrapper.find('Connect(FontDeleteButton)').exists()).toBe(true);
	});

	test('An available font says why this user cannot install it', () => {
		GFPDF.fontManagerNetworkOnly = 'Network only';
		const wrapper = render({
			font: {
				...core,
				type: 'available',
				can_delete: false,
				can_install: false,
			},
		});

		expect(wrapper.find('Connect(FontInstallButton)').exists()).toBe(false);
		expect(
			wrapper.find('[data-test="font-list-network-note"]').text()
		).toBe('Network only');
	});
});
