import React from 'react';
import { mount, shallow } from 'enzyme';
import { findByTestAttr } from '../../testUtils';
import {
	DownloadedFonts,
	matchesKeyword,
} from '../../../../../src/assets/js/react/components/FontManager/DownloadedFonts';

describe('FontManager - DownloadedFonts.js', () => {
	const garuda = {
		id: 'garuda',
		label: 'Garuda (Thai)',
		source: 'core',
		files: [
			{ style: 'R', file: 'Garuda.ttf', size: 2048 },
			{ style: 'B', file: 'Garuda-Bold.ttf', size: 2048 },
		],
		size_label: '4 KB',
		in_use: [],
		auto_install: true,
	};

	const sunExtA = {
		id: 'sun-exta',
		label: 'Sun Ext',
		source: 'core',
		files: [{ style: 'R', file: 'Sun-ExtA.ttf', size: 22900000 }],
		size_label: '21.8 MB',
		in_use: ['Default font', 'Used by 3 PDFs'],
		auto_install: true,
	};

	const props = (overrides = {}) => ({
		loading: false,
		fonts: [garuda, sunExtA],
		canDelete: true,
		deletingId: '',
		keyword: '',
		msg: {},
		getDownloadedFonts: jest.fn(),
		deleteDownloadedFont: jest.fn(),
		...overrides,
	});

	const deleteButton = (wrapper, index) =>
		findByTestAttr(wrapper, 'downloaded-font').at(index).find('button');

	beforeAll(() => {
		Object.assign(window.GFPDF, {
			fontManagerDownloadedConfirm: 'Delete %s?',
			fontManagerDownloadedConfirmOffline:
				'Delete %s? Downloads are off.',
			fontManagerDownloadedDeleteAriaLabel: 'Delete %s',
			fontManagerDownloadedUnused: 'Not in use',
			fontManagerDownloadedEmpty: 'No fonts have been downloaded.',
			fontManagerDownloadedReadOnly: 'Read only',
		});
	});

	afterEach(() => {
		jest.restoreAllMocks();
	});

	test('requests the list on mount', () => {
		const p = props();
		mount(<DownloadedFonts {...p} />);

		expect(p.getDownloadedFonts).toHaveBeenCalledTimes(1);
	});

	test('lists each font with its styles, size and in-use reasons', () => {
		const wrapper = shallow(<DownloadedFonts {...props()} />);
		const rows = findByTestAttr(wrapper, 'downloaded-font');

		expect(rows.length).toBe(2);
		expect(rows.at(0).find('th').text()).toBe('Garuda (Thai)');
		expect(rows.at(0).find('td').at(0).text()).toBe('Regular, Bold');
		expect(rows.at(0).find('td').at(1).text()).toBe('4 KB');
		expect(rows.at(0).find('td').at(2).text()).toBe('Not in use');
		expect(rows.at(1).find('td').at(1).text()).toBe('21.8 MB');
		expect(rows.at(1).find('td').at(2).text()).toBe(
			'Default font, Used by 3 PDFs'
		);
		expect(deleteButton(wrapper, 0).prop('aria-label')).toBe(
			'Delete Garuda (Thai)'
		);
	});

	test('disables Delete for an in-use font', () => {
		const wrapper = shallow(<DownloadedFonts {...props()} />);

		expect(deleteButton(wrapper, 0).prop('disabled')).toBe(false);
		expect(deleteButton(wrapper, 1).prop('disabled')).toBe(true);
	});

	test('disables Delete and says why for a user who cannot delete', () => {
		const wrapper = shallow(
			<DownloadedFonts {...props({ canDelete: false })} />
		);

		expect(deleteButton(wrapper, 0).prop('disabled')).toBe(true);
		expect(wrapper.find('.description').text()).toBe('Read only');
	});

	test('shows a spinner for the font being deleted and disables the rest', () => {
		const wrapper = shallow(
			<DownloadedFonts {...props({ deletingId: 'garuda' })} />
		);
		const rows = findByTestAttr(wrapper, 'downloaded-font');

		expect(rows.at(0).find('Spinner').length).toBe(1);
		expect(deleteButton(wrapper, 1).prop('disabled')).toBe(true);
	});

	test('deletes after the confirm', () => {
		const p = props();
		const confirm = jest.spyOn(window, 'confirm').mockReturnValue(true);
		const wrapper = shallow(<DownloadedFonts {...p} />);

		deleteButton(wrapper, 0).simulate('click');

		expect(confirm).toHaveBeenCalledWith('Delete Garuda (Thai)?');
		expect(p.deleteDownloadedFont).toHaveBeenCalledWith({
			id: 'garuda',
			label: 'Garuda (Thai)',
		});
	});

	test('does not delete when the confirm is cancelled', () => {
		const p = props();
		jest.spyOn(window, 'confirm').mockReturnValue(false);
		const wrapper = shallow(<DownloadedFonts {...p} />);

		deleteButton(wrapper, 0).simulate('click');

		expect(p.deleteDownloadedFont).not.toHaveBeenCalled();
	});

	test('warns that a font will not come back with downloads off', () => {
		const confirm = jest.spyOn(window, 'confirm').mockReturnValue(false);
		const wrapper = shallow(
			<DownloadedFonts
				{...props({ fonts: [{ ...garuda, auto_install: false }] })}
			/>
		);

		deleteButton(wrapper, 0).simulate('click');

		expect(confirm).toHaveBeenCalledWith(
			'Delete Garuda (Thai)? Downloads are off.'
		);
	});

	test('filters by the search keyword', () => {
		const wrapper = shallow(
			<DownloadedFonts {...props({ keyword: 'Sun-ExtA' })} />
		);

		expect(findByTestAttr(wrapper, 'downloaded-font').length).toBe(1);

		wrapper.setProps({ keyword: 'nothing' });
		expect(findByTestAttr(wrapper, 'downloaded-fonts-empty').text()).toBe(
			GFPDF.searchResultEmpty
		);
	});

	test('says when nothing is downloaded', () => {
		const wrapper = shallow(<DownloadedFonts {...props({ fonts: [] })} />);

		expect(findByTestAttr(wrapper, 'downloaded-fonts-empty').text()).toBe(
			'No fonts have been downloaded.'
		);
		expect(wrapper.find('table').length).toBe(0);
	});

	test('shows a spinner while loading', () => {
		const wrapper = shallow(
			<DownloadedFonts {...props({ loading: true })} />
		);

		expect(wrapper.find('Spinner').length).toBe(1);
		expect(wrapper.find('table').length).toBe(0);
	});

	test('shows the success and error notices', () => {
		const wrapper = shallow(
			<DownloadedFonts {...props({ msg: { success: 'Gone.' } })} />
		);
		expect(wrapper.find('.notice-success').text()).toBe('Gone.');

		wrapper.setProps({ msg: { error: 'In use' } });
		expect(wrapper.find('Alert').prop('msg')).toBe('In use');
	});

	test('matchesKeyword() checks the label, key and file names', () => {
		expect(matchesKeyword(garuda, '')).toBe(true);
		expect(matchesKeyword(garuda, 'THAI')).toBe(true);
		expect(matchesKeyword(garuda, 'garuda-bold')).toBe(true);
		expect(matchesKeyword(garuda, 'sun')).toBe(false);
	});
});
