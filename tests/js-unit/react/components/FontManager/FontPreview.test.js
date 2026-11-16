import React from 'react';
import { act } from 'react-dom/test-utils';
import { mount } from 'enzyme';
import FontPreview, {
	FontPreviewCache,
} from '../../../../../src/assets/js/react/components/FontManager/FontPreview';

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
	const styleSelect = (wrapper) =>
		wrapper.find('[data-test="font-preview-style"]');
	const sheets = (wrapper) =>
		wrapper
			.find('style')
			.map((sheet) => sheet.text())
			.join('');
	const loadButton = (wrapper) =>
		wrapper.find('[data-test="font-preview-load"]');
	const largeFont = {
		...font,
		file_sizes: { regular: 5 * 1024 * 1024, bold: 1024 },
	};

	// The Font Manager's cache, which also holds the font's style sheet
	const Preview = (props) => (
		<FontPreviewCache>
			<FontPreview {...props} />
		</FontPreviewCache>
	);

	let onIntersect;

	const setVisible = (isIntersecting) =>
		act(() => onIntersect([{ isIntersecting }]));
	const wait = (wrapper, ms) => {
		act(() => jest.advanceTimersByTime(ms));
		wrapper.update();
	};
	const mountLoaded = (props) => {
		const wrapper = mount(<Preview font={font} {...props} />);

		setVisible(true);
		wait(wrapper, 200);

		return wrapper;
	};
	const pickStyle = (wrapper, value) =>
		styleSelect(wrapper).simulate('change', { target: { value } });

	beforeEach(() => {
		jest.useFakeTimers();
		global.IntersectionObserver = jest.fn((callback) => {
			onIntersect = callback;

			return { observe: jest.fn(), disconnect: jest.fn() };
		});
	});

	afterEach(() => {
		jest.useRealTimers();
		delete global.IntersectionObserver;
	});

	test('Loads the font once on screen for a moment', () => {
		const wrapper = mount(<Preview font={font} />);

		setVisible(true);
		wait(wrapper, 199);
		expect(wrapper.find('style').exists()).toBe(false);

		wait(wrapper, 1);
		expect(wrapper.find('style').exists()).toBe(true);
	});

	test('Loads nothing when scrolled away before the delay', () => {
		const wrapper = mount(<Preview font={font} />);

		setVisible(true);
		setVisible(false);
		wait(wrapper, 200);

		expect(wrapper.find('style').exists()).toBe(false);
	});

	test('Keeps the old font loaded and waits for the next one', () => {
		const wrapper = mountLoaded();

		wrapper.setProps({ font: { ...font, id: 'other' } });
		expect(sheets(wrapper)).toContain('gfpdf-preview-brand');
		expect(sheets(wrapper)).not.toContain('gfpdf-preview-other');

		setVisible(true);
		wait(wrapper, 200);
		expect(sheets(wrapper)).toContain('gfpdf-preview-brand');
		expect(sheets(wrapper)).toContain('gfpdf-preview-other');

		// Going back shows it straight away
		wrapper.setProps({ font });
		expect(samples(wrapper).length).toBe(1);
	});

	test("Drops a font's faces when its files change", () => {
		const wrapper = mountLoaded();
		const changed = { ...font, bold: 'Brand-Bold-1.ttf' };

		wrapper.setProps({ font: { ...font, id: 'other' } });
		wrapper.setProps({ font: changed });
		wrapper.update();
		expect(wrapper.find('style').exists()).toBe(false);

		setVisible(true);
		wait(wrapper, 200);
		expect(sheets(wrapper)).toContain('Brand-Bold-1.ttf');

		wrapper.setProps({ font: { ...changed, file_sizes: { regular: 1 } } });
		wrapper.update();
		expect(sheets(wrapper).match(/@font-face/g).length).toBe(2);
	});

	test('Has a swapping face for each style with a file', () => {
		const faces = sheets(mountLoaded());

		expect(faces).toContain('http://x/fonts/Brand%20Regular.ttf');
		expect(faces).toContain('http://x/fonts/Brand-Bold.ttf');
		expect(faces).not.toContain('/var/www');
		expect(faces.match(/font-display:swap/g).length).toBe(2);
	});

	test("Shows the font's own sample until text is typed", () => {
		const wrapper = mountLoaded({ font: { ...font, sample: 'สวัสดี' } });

		expect(samples(wrapper).text()).toBe('สวัสดี');
		expect(samples(wrapper).prop('dir')).toBe('auto');
	});

	test('A bundled font loads from its own folder', () => {
		const wrapper = mountLoaded({
			font: { ...font, base_url: 'http://p/fonts/' },
		});

		expect(sheets(wrapper)).toContain('http://p/fonts/Brand-Bold.ttf');
	});

	describe('While the font face loads', () => {
		const loading = (wrapper) =>
			wrapper.find('[data-test="font-preview-loading"]');
		const settle = async (wrapper, finish) => {
			await act(async () => finish());
			wrapper.update();
		};
		let finish;

		beforeEach(() => {
			document.fonts = {
				load: jest.fn(
					() =>
						new Promise((resolve, reject) => {
							finish = { resolve, reject };
						})
				),
			};
		});

		afterEach(() => {
			delete document.fonts;
		});

		test('Shows the indicator until the face has loaded', async () => {
			const wrapper = mountLoaded();

			expect(loading(wrapper).exists()).toBe(true);
			expect(document.fonts.load).toHaveBeenCalledWith(
				'normal 400 16px "gfpdf-preview-brand"'
			);

			await settle(wrapper, () => finish.resolve([]));
			expect(loading(wrapper).exists()).toBe(false);
		});

		test('Stops the indicator if the face fails to load', async () => {
			const wrapper = mountLoaded();

			await settle(wrapper, () => finish.reject(new Error('Nope')));
			expect(loading(wrapper).exists()).toBe(false);
			expect(
				wrapper.find('[data-test="font-preview-failed"]').text()
			).toBe('Failed');
		});

		test('Shows the indicator again for another style', async () => {
			const wrapper = mountLoaded();

			await settle(wrapper, () => finish.resolve([]));
			pickStyle(wrapper, 'bold');
			wrapper.update();

			expect(loading(wrapper).exists()).toBe(true);
			expect(document.fonts.load).toHaveBeenLastCalledWith(
				'normal 700 16px "gfpdf-preview-brand"'
			);
		});

		test('Each style in Show all has its own indicator', async () => {
			const wrapper = mountLoaded();

			await settle(wrapper, () => finish.resolve([]));
			pickStyle(wrapper, 'all');
			wrapper.update();

			expect(samples(wrapper).length).toBe(2);
			expect(samples(wrapper).at(0).find('img').exists()).toBe(false);
			expect(samples(wrapper).at(1).find('img').exists()).toBe(true);
			expect(document.fonts.load).toHaveBeenLastCalledWith(
				'normal 700 16px "gfpdf-preview-brand"'
			);

			await settle(wrapper, () => finish.resolve([]));
			expect(loading(wrapper).exists()).toBe(false);
		});

		test('A loaded face shows without the indicator when viewed again', async () => {
			const wrapper = mountLoaded();

			await settle(wrapper, () => finish.resolve([]));
			wrapper.setProps({ font: { ...font, id: 'other' } });
			wrapper.setProps({ font });

			expect(loading(wrapper).exists()).toBe(false);
			expect(document.fonts.load).toHaveBeenCalledTimes(1);
		});

		test('A failed face is tried again after viewing another font', async () => {
			const wrapper = mountLoaded();

			await settle(wrapper, () => finish.reject(new Error('Nope')));
			wrapper.setProps({ font: { ...font, id: 'other' } });
			wrapper.setProps({ font });

			expect(
				wrapper.find('[data-test="font-preview-failed"]').exists()
			).toBe(false);

			setVisible(true);
			wait(wrapper, 200);
			expect(loading(wrapper).exists()).toBe(true);
			expect(document.fonts.load).toHaveBeenLastCalledWith(
				'normal 400 16px "gfpdf-preview-brand"'
			);
		});

		test('Waits for a click on a large font', () => {
			const wrapper = mountLoaded({ font: largeFont });

			expect(loading(wrapper).exists()).toBe(false);
			expect(document.fonts.load).not.toHaveBeenCalled();
		});
	});

	describe('When the fonts folder has no URL', () => {
		const { fontsUrl } = GFPDF;

		beforeEach(() => {
			GFPDF.fontsUrl = '';
		});

		afterEach(() => {
			GFPDF.fontsUrl = fontsUrl;
		});

		test('Says the preview is unavailable', () => {
			const wrapper = mount(<Preview font={font} />);

			expect(
				wrapper.find('[data-test="font-preview-unavailable"]').length
			).toBe(1);
			expect(wrapper.find('style').length).toBe(0);
		});

		test('A bundled font still previews', () => {
			const wrapper = mountLoaded({
				font: { ...font, base_url: 'http://p/fonts/' },
			});

			expect(sheets(wrapper)).toContain('http://p/fonts/');
		});
	});

	test('Samples the first style until another is picked', () => {
		const wrapper = mountLoaded();

		expect(samples(wrapper).length).toBe(1);
		expect(samples(wrapper).prop('style').fontWeight).toBe(400);

		pickStyle(wrapper, 'bold');
		expect(samples(wrapper).length).toBe(1);
		expect(samples(wrapper).prop('style').fontWeight).toBe(700);
		expect(styleSelect(wrapper).prop('value')).toBe('bold');

		pickStyle(wrapper, 'all');
		expect(samples(wrapper).length).toBe(2);
		expect(styleSelect(wrapper).prop('value')).toBe('all');
	});

	test('Offers all four styles and show all, disabling the missing ones', () => {
		const options = styleSelect(mountLoaded()).find('option');

		expect(options.map((option) => option.prop('value'))).toEqual([
			'all',
			'regular',
			'italics',
			'bold',
			'bolditalics',
		]);
		expect(options.map((option) => !!option.prop('disabled'))).toEqual([
			false,
			false,
			true,
			false,
			true,
		]);
	});

	test('A large file waits for a click', () => {
		const wrapper = mountLoaded({ font: largeFont });

		expect(wrapper.find('style').exists()).toBe(false);
		expect(samples(wrapper).exists()).toBe(false);

		loadButton(wrapper).simulate('click');

		expect(loadButton(wrapper).exists()).toBe(false);
		expect(samples(wrapper).length).toBe(1);
		expect(wrapper.find('style').exists()).toBe(true);
	});

	test('Only what is shown counts towards the limit', () => {
		const wrapper = mountLoaded({ font: largeFont });

		pickStyle(wrapper, 'bold');

		expect(loadButton(wrapper).exists()).toBe(false);
		expect(wrapper.find('style').exists()).toBe(true);
	});

	test('Show all can go over the limit when each style is under it', () => {
		const wrapper = mountLoaded({
			font: {
				...font,
				file_sizes: { regular: 3 * 1024 * 1024, bold: 3 * 1024 * 1024 },
			},
		});

		expect(loadButton(wrapper).exists()).toBe(false);

		pickStyle(wrapper, 'all');
		expect(loadButton(wrapper).exists()).toBe(true);
	});

	test('A click only loads that font', () => {
		const wrapper = mountLoaded({ font: largeFont });

		loadButton(wrapper).simulate('click');
		wrapper.setProps({ font: { ...largeFont, id: 'other' } });

		expect(loadButton(wrapper).exists()).toBe(true);
	});

	test('Drops the least recently viewed fonts once over 64 MB', () => {
		const hugeFont = {
			...font,
			bold: '',
			file_sizes: { regular: 30 * 1024 * 1024 },
		};
		const wrapper = mountLoaded({ font: hugeFont });
		const view = (id) => {
			wrapper.setProps({ font: { ...hugeFont, id } });
			setVisible(true);
			wait(wrapper, 200);
		};
		const load = () => {
			loadButton(wrapper).simulate('click');
			wrapper.update();
		};

		load();
		view('b');
		load();
		view('brand');
		expect(loadButton(wrapper).exists()).toBe(false);

		view('c');
		load();
		expect(sheets(wrapper)).not.toContain('gfpdf-preview-b"');
		expect(sheets(wrapper)).toContain('gfpdf-preview-brand');

		view('b');
		expect(loadButton(wrapper).exists()).toBe(true);
		view('brand');
		expect(loadButton(wrapper).exists()).toBe(false);
	});

	test('Returns to the first style for the next font', () => {
		const wrapper = mountLoaded();

		pickStyle(wrapper, 'all');
		wrapper.setProps({ font: { ...font, id: 'other' } });

		expect(samples(wrapper).length).toBe(1);
		expect(styleSelect(wrapper).prop('value')).toBe('regular');
	});

	test('Forgets the picked style after viewing another font', () => {
		const wrapper = mountLoaded();

		pickStyle(wrapper, 'bold');
		wrapper.setProps({ font: { ...font, id: 'other' } });
		wrapper.setProps({ font });

		expect(styleSelect(wrapper).prop('value')).toBe('regular');
	});

	test('Has no style picker for a single style', () => {
		const wrapper = mountLoaded({ font: { ...font, bold: '' } });

		expect(styleSelect(wrapper).exists()).toBe(false);
	});

	test('Shows typed text at the chosen size', () => {
		const wrapper = mount(<Preview font={font} />);

		wrapper
			.find('.font-preview-text')
			.simulate('change', { target: { value: 'Hello' } });
		wrapper
			.find('#gfpdf-font-preview-size')
			.simulate('change', { target: { value: '20' } });

		expect(samples(wrapper).at(0).text()).toBe('Hello');
		expect(samples(wrapper).at(0).prop('style').fontSize).toBe('20pt');

		wrapper.setProps({ font: { ...font, id: 'other' } });

		expect(samples(wrapper).at(0).text()).toBe('Hello');
		expect(samples(wrapper).at(0).prop('style').fontSize).toBe('20pt');
	});
});
