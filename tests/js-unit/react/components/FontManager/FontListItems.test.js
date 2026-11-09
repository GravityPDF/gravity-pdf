import React from 'react';
import { shallow } from 'enzyme';
import { findByTestAttr } from '../../testUtils';
import { FontListItems } from '../../../../../src/assets/js/react/components/FontManager/FontListItems';
import * as utilities from '../../../../../src/assets/js/react/utilities/FontManager/toggleUpdateFont';

describe('FontManager - FontListItems.js', () => {
	// Mock component props
	const props = {
		history: {
			location: { pathname: 'abc' },
			push: jest.fn(),
		},
		navigate: jest.fn(),
		location: {
			pathname: 'abc',
		},
		clearAddFontMsg: jest.fn(),
		msg: { success: { addFont: 'success' } },
		selectFont: jest.fn(),
		fontList: [
			{
				font_name: 'Fira Sans Light',
				id: 'firasanslight',
				regular: 'FiraSans-Light.ttf',
				italics: 'FiraSans-LightItalic.ttf',
				bold: 'FiraSans-Medium.ttf',
				bolditalics: 'FiraSans-MediumItalic.ttf',
				type: 'custom',
				can_delete: true,
			},
		],
		searchResult: null,
		selectedFont: 'roboto',
		loading: true,
		fontUsage: {},
	};

	describe('RUN LIFECYCLE METHODS', () => {
		test('componentDidMount() - Call the method handleSetSelectedFontNameValue()', () => {
			// Mock font manager select box DOM
			document.body.innerHTML =
				'<select id="gfpdf_settings[font]" name="gfpdf_settings[font]">' +
				' <optgroup label="User-Defined Fonts">' +
				'   <option value="z" />' +
				'   <option value="c" />' +
				' </optgroup>' +
				'</select>';

			const wrapper = shallow(<FontListItems {...props} />);
			const instance = wrapper.instance();
			const handleSetSelectedFontNameValue = jest.spyOn(
				instance,
				'handleSetSelectedFontNameValue'
			);

			instance.componentDidMount();

			expect(handleSetSelectedFontNameValue).toHaveBeenCalledTimes(1);
		});
	});

	describe('RUN COMPONENT METHODS', () => {
		test.each([
			['the form Font field', 'gfpdf_settings[font]'],
			['the global Default Font field', 'gfpdf_settings[default_font]'],
		])('handleSetSelectedFontNameValue() - Reads %s', (name, id) => {
			const select = (value) => {
				document.body.innerHTML = `<select id="${id}"><option value="${value}" selected></option></select>`;
				props.selectFont.mockClear();
				shallow(<FontListItems {...props} />)
					.instance()
					.handleSetSelectedFontNameValue();
			};

			select('firasanslight');
			expect(props.selectFont).toHaveBeenCalledWith('firasanslight');

			select('not-listed');
			expect(props.selectFont).toHaveBeenCalledWith('');
		});

		test('handleFontClick() - Opens a font, or closes it when it is open', () => {
			props.navigate.mockClear();
			shallow(<FontListItems {...props} />)
				.instance()
				.handleFontClick({ id: 'garuda', type: 'core' });
			expect(props.navigate).toHaveBeenCalledWith('/fontmanager/garuda');

			props.navigate.mockClear();
			shallow(<FontListItems {...props} id="garuda" />)
				.instance()
				.handleFontClick({ id: 'garuda', type: 'core' });
			expect(props.navigate).toHaveBeenCalledWith('/fontmanager/');
		});

		test('handleFontClick() - Display or hide update font panel', () => {
			// Mock update font panel DOM
			document.body.innerHTML =
				'<div class="update-font show">' + '</div>';

			const toggleUpdateFont = jest.spyOn(utilities, 'toggleUpdateFont');
			const wrapper = shallow(<FontListItems {...props} />);
			const instance = wrapper.instance();

			instance.handleFontClick({ id: 'arial', type: 'custom' });

			expect(props.clearAddFontMsg).toHaveBeenCalledTimes(1);
			expect(toggleUpdateFont).toHaveBeenCalledTimes(1);
		});

		describe('render() - Groups', () => {
			const base = {
				regular: 'a.ttf',
				italics: '',
				bold: '',
				bolditalics: '',
			};
			const fontList = [
				{
					...base,
					id: 'freeserif',
					font_name: 'Free Serif',
					type: 'core',
					bold: 'b.ttf',
				},
				{ ...base, id: 'arimo', font_name: 'Arimo', type: 'bundled' },
				{ ...base, id: 'khmeros', font_name: 'Khmer', type: 'core' },
				{ ...base, id: 'brand', font_name: 'Brand', type: 'custom' },
			];
			const render = (extra) =>
				shallow(
					<FontListItems
						{...props}
						selectedFont="brand"
						loading={false}
						fontList={fontList}
						fontUsage={{ freeserif: { reasons: [] } }}
						{...extra}
					/>
				);
			const rows = (wrapper) =>
				wrapper.find('[data-test="font-list-item"]');
			const titles = (wrapper) =>
				wrapper
					.find('.font-list-group-name')
					.map((title) => title.text());

			beforeEach(() => {
				document.body.innerHTML = '';
			});

			test('Fonts are listed as custom, bundled then core, keeping their order', () => {
				const wrapper = render({ selectedFont: '' });

				expect(titles(wrapper)).toEqual(['Custom', 'Bundled', 'Core']);
				expect(
					rows(wrapper).map((row) => row.prop('data-font'))
				).toEqual(['brand', 'arimo', 'freeserif', 'khmeros']);
			});

			test('The selected font comes first, out of its group, and the groups stay in order', () => {
				const wrapper = render({ selectedFont: 'khmeros' });

				expect(titles(wrapper)).toEqual(['Custom', 'Bundled', 'Core']);
				expect(
					rows(wrapper).map((row) => row.prop('data-font'))
				).toEqual(['khmeros', 'brand', 'arimo', 'freeserif']);
				expect(
					wrapper
						.find('[data-test="font-list-selected"]')
						.find('[data-test="font-list-item"]')
						.prop('data-font')
				).toBe('khmeros');
				expect(
					wrapper
						.find('[data-test="font-list-group-core"] .count')
						.text()
				).toBe('1');
			});

			test('A group left empty by the selected font is hidden', () => {
				expect(titles(render({ selectedFont: 'arimo' }))).toEqual([
					'Custom',
					'Core',
				]);
			});

			test('A selected font the search left out changes nothing', () => {
				const wrapper = render({
					selectedFont: 'khmeros',
					searchResult: [fontList[1], fontList[3]],
				});

				expect(titles(wrapper)).toEqual(['Custom', 'Bundled']);
				expect(
					wrapper.find('[data-test="font-list-selected"]').exists()
				).toBe(false);
			});

			test('A type with no fonts has no group', () => {
				expect(titles(render({ fontList: [fontList[1]] }))).toEqual([
					'Bundled',
				]);
			});

			test('A search result is grouped too', () => {
				const wrapper = render({ searchResult: [fontList[2]] });

				expect(
					rows(wrapper).map((row) => row.prop('data-font'))
				).toEqual(['khmeros']);
				expect(titles(wrapper)).toEqual(['Core']);
			});

			test('A row has its style count and says when it is in use', () => {
				const wrapper = render();

				expect(rows(wrapper).at(2).find('.font-sub').text()).toBe(
					'2 styles · In use'
				);
				expect(rows(wrapper).at(3).find('.font-sub').text()).toBe(
					'1 style'
				);
			});

			test('Only the selected font is tagged active and only bundled fonts are locked', () => {
				const wrapper = render();

				expect(wrapper.find('.font-active-tag').length).toBe(1);
				expect(
					rows(wrapper).at(0).find('.font-active-tag').exists()
				).toBe(true);
				expect(rows(wrapper).at(1).find('.font-lock').exists()).toBe(
					true
				);
				expect(rows(wrapper).at(2).find('.font-lock').exists()).toBe(
					false
				);
			});

			test('The open row is marked', () => {
				const row = rows(render({ id: 'brand' })).at(0);

				expect(row.hasClass('active')).toBe(true);
				expect(row.prop('aria-current')).toBe('true');
			});
		});
	});

	describe('RENDERS COMPONENT', () => {
		test('render <FontListItems /> component', () => {
			const wrapper = shallow(<FontListItems {...props} />);
			const component = findByTestAttr(
				wrapper,
				'component-FontListItems'
			);

			expect(component.length).toBe(1);
		});

		test('render font name', () => {
			const wrapper = shallow(<FontListItems {...props} />);
			expect(wrapper.find('.font-name').text()).toBe('Fira Sans Light');
		});
	});
});
