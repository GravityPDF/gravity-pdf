import {
	GROUP_ATTRIBUTE,
	INSTALL_SENTINEL,
	addInstallSentinel,
	setSelectValue,
	syncSelect,
} from '../../../../../src/assets/js/react/fontManager/utils/selectBox';

const fonts = {
	bundled: [{ id: 'gfpdf-arimo', label: 'Arimo', enabled: true }],
	groups: [
		{
			source: 'packs',
			entry: 'emoji',
			label: 'Emoji',
			fonts: [{ id: 'notoemoji', label: 'Noto Emoji', enabled: true }],
		},
	],
	custom: [{ id: 'brandsans', label: 'Brand Sans', enabled: true }],
};

const build = (html = '') => {
	document.body.innerHTML = `<select id="font">${html}</select>`;

	return document.getElementById('font');
};

describe('Font Manager - utils/selectBox.js', () => {
	describe('syncSelect()', () => {
		test('builds one group per installed group, in sidebar order', () => {
			const select = build();

			syncSelect(select, fonts);

			expect(
				[...select.querySelectorAll('optgroup')].map((group) =>
					group.getAttribute('label')
				)
			).toEqual(['Bundled', 'Emoji', 'User-Defined Fonts']);
		});

		test('leaves an optgroup it does not own exactly where it was', () => {
			const select = build(
				'<optgroup label="Add-on Fonts"><option value="addon">Add-on</option></optgroup>'
			);

			syncSelect(select, fonts);
			syncSelect(select, fonts);

			expect(
				select.querySelectorAll('optgroup[label="Add-on Fonts"]')
			).toHaveLength(1);
		});

		test('marks every group it owns, so the next rebuild can find them', () => {
			const select = build();

			syncSelect(select, fonts);

			expect(
				select.querySelectorAll(`optgroup[${GROUP_ATTRIBUTE}]`)
			).toHaveLength(3);
		});

		test('keeps a saved font that is no longer installed', () => {
			const select = build();

			syncSelect(select, {
				...fonts,
				custom: [{ id: 'gonefont', label: 'Gone', enabled: true }],
			});

			select.value = 'gonefont';

			syncSelect(select, { ...fonts, custom: [] });

			expect(select.value).toBe('gonefont');
			expect(select.selectedOptions[0].text).toContain('(not installed)');
		});

		test('says so when a font is hidden on this site', () => {
			const select = build();

			syncSelect(select, {
				...fonts,
				custom: [
					{ id: 'brandsans', label: 'Brand Sans', enabled: false },
				],
			});

			expect(select.innerHTML).toContain('(hidden on this site)');
		});
	});

	describe('addInstallSentinel()', () => {
		test('adds the option once, however often it is asked', () => {
			const select = build('<option value="lato">Lato</option>');

			addInstallSentinel(select, jest.fn(), () => 'lato');
			addInstallSentinel(select, jest.fn(), () => 'lato');

			expect(
				[...select.options].filter(
					(option) => option.value === INSTALL_SENTINEL
				)
			).toHaveLength(1);
		});

		test('never becomes the value: it restores the active font and opens the modal', () => {
			const select = build('<option value="lato">Lato</option>');
			const open = jest.fn();

			addInstallSentinel(select, open, () => 'lato');

			select.value = INSTALL_SENTINEL;
			select.dispatchEvent(new Event('change'));

			expect(open).toHaveBeenCalledTimes(1);
			expect(select.value).toBe('lato');
		});
	});

	describe('setSelectValue()', () => {
		test('points the select at the font and tells the page', () => {
			const select = build(
				'<option value="lato">Lato</option><option value="arimo">Arimo</option>'
			);
			const onChange = jest.fn();

			select.addEventListener('change', onChange);
			setSelectValue(select, 'arimo');

			expect(select.value).toBe('arimo');
			expect(onChange).toHaveBeenCalledTimes(1);
		});

		test('does nothing for a font the select does not carry', () => {
			const select = build('<option value="lato">Lato</option>');

			setSelectValue(select, 'nope');

			expect(select.value).toBe('lato');
		});
	});
});
