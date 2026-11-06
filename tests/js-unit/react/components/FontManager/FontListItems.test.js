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
		deleteFont: jest.fn(),
		selectFont: jest.fn(),
		moveSelectedFontToTop: jest.fn(),
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

		test('componentDidMount() - Call the method handleMoveSelectedFontAtTheTopOfTheList()', () => {
			// Mock font manager select box DOM
			document.body.innerHTML =
				'<select id="gfpdf_settings[font]" name="gfpdf_settings[font]">' +
				' <optgroup label="User-Defined Fonts">' +
				'   <option value="z" />' +
				'   <option value="c" />' +
				' </optgroup>' +
				'</select>';

			const wrapper = shallow(
				<FontListItems {...props} selectedFont="roboto" />
			);
			const instance = wrapper.instance();
			const handleMoveSelectedFontAtTheTopOfTheList = jest.spyOn(
				instance,
				'handleMoveSelectedFontAtTheTopOfTheList'
			);

			instance.componentDidMount();

			expect(
				handleMoveSelectedFontAtTheTopOfTheList
			).toHaveBeenCalledTimes(1);
			expect(props.moveSelectedFontToTop).toHaveBeenCalledTimes(1);
		});

		test('componentDidUpdate() - Call the method handleResetLoadingState() and handleMoveSelectedFontAtTheTopOfTheList()', () => {
			// Mock update font panel DOM
			document.body.innerHTML =
				'<div class="update-font show">' + '</div>';

			const wrapper = shallow(
				<FontListItems {...props} loading={false} />
			);
			const instance = wrapper.instance();
			const toggleUpdateFont = jest.spyOn(utilities, 'toggleUpdateFont');
			const handleResetLoadingState = jest.spyOn(
				instance,
				'handleResetLoadingState'
			);
			const prevProps = {
				fontList: [
					{
						font_name: 'Arial',
						id: 'arial',
						regular: 'Arial.ttf',
						italics: 'Arial-Italic.ttf',
						bold: 'Arial-Bold.ttf',
						bolditalics: 'Arial-BoldItalics.ttf',
					},
				],
				loading: true,
				selectedFont: '',
			};

			instance.componentDidUpdate(prevProps);

			expect(handleResetLoadingState).toHaveBeenCalledTimes(1);
			expect(toggleUpdateFont).toHaveBeenCalledTimes(1);
		});
	});

	describe('RUN COMPONENT METHODS', () => {
		test('handleSetSelectedFontNameValue() - Reads the global Default Font field', () => {
			// Mock font manager select box DOM
			document.body.innerHTML =
				'<select id="gfpdf_settings[default_font]" name="gfpdf_settings[default_font]">' +
				' <optgroup label="User-Defined Fonts">' +
				'   <option value="z" />' +
				'   <option value="c" />' +
				' </optgroup>' +
				'</select>';

			const wrapper = shallow(<FontListItems {...props} />);
			const instance = wrapper.instance();
			const handleCheckSelectBoxValue = jest.spyOn(
				instance,
				'handleCheckSelectBoxValue'
			);

			instance.handleSetSelectedFontNameValue();

			expect(handleCheckSelectBoxValue).toHaveBeenCalledTimes(1);
			expect(props.selectFont).toHaveBeenCalledTimes(1);
		});

		test('handleSetSelectedFontNameValue() - Reads the form Font field', () => {
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
			const handleCheckSelectBoxValue = jest.spyOn(
				instance,
				'handleCheckSelectBoxValue'
			);

			instance.handleSetSelectedFontNameValue();

			expect(handleCheckSelectBoxValue).toHaveBeenCalledTimes(1);
			expect(props.selectFont).toHaveBeenCalledTimes(1);
		});

		test('handleCheckSelectBoxValue() - Check if font manager default selected font type is listed on custom font list (true)', () => {
			const fontList = [{ id: 'z' }, { id: 'c' }];
			const value = 'z';
			const wrapper = shallow(<FontListItems {...props} />);
			const instance = wrapper.instance();

			expect(instance.handleCheckSelectBoxValue(fontList, value)).toBe(
				true
			);
		});

		test('handleCheckSelectBoxValue() - Check if font manager default selected font type is listed on custom font list (false)', () => {
			const fontList = [{ id: 'z' }, { id: 'c' }];
			const value = 'a';
			const wrapper = shallow(<FontListItems {...props} />);
			const instance = wrapper.instance();

			expect(instance.handleCheckSelectBoxValue(fontList, value)).toBe(
				false
			);
		});

		test('handleResetLoadingState() - Reset deleteId state', () => {
			const wrapper = shallow(<FontListItems {...props} />);
			const instance = wrapper.instance();

			instance.handleResetLoadingState();

			expect(wrapper.state('deleteId')).toBe('');
		});

		test('handleMoveSelectedFontAtTheTopOfTheList() - Move selected font at the very top of the font list', () => {
			const wrapper = shallow(<FontListItems {...props} />);
			const instance = wrapper.instance();

			instance.handleMoveSelectedFontAtTheTopOfTheList('roboto');

			expect(props.moveSelectedFontToTop).toHaveBeenCalledTimes(1);
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

		test("handleFontClickKeypress() - Listen to an 'enter' keyboard event on font list item", () => {
			// Mock update font panel DOM
			document.body.innerHTML =
				'<div class="update-font show">' + '</div>';

			const wrapper = shallow(<FontListItems {...props} />);
			const instance = wrapper.instance();
			const handleFontClick = jest.spyOn(instance, 'handleFontClick');
			const e = { key: 'Enter' };

			instance.handleFontClickKeypress(e, { id: 'arial' });

			expect(handleFontClick).toHaveBeenCalledTimes(1);
		});

		test('handleDeleteFont() - Handle request of font deletion', () => {
			global.confirm = () => true;

			const wrapper = shallow(<FontListItems {...props} />);
			const instance = wrapper.instance();
			const e = { stopPropagation: jest.fn() };

			instance.handleDeleteFont(e, {
				id: 'arial',
				type: 'custom',
				can_delete: true,
			});

			expect(wrapper.state('deleteId')).toBe('arial');
			expect(props.deleteFont).toHaveBeenCalledTimes(1);
			expect(props.deleteFont).toHaveBeenCalledWith('arial', 'custom');
		});

		test('handleFontClick() - A core or bundled font has no update font panel', () => {
			const toggleUpdateFont = jest.spyOn(utilities, 'toggleUpdateFont');
			toggleUpdateFont.mockClear();
			const instance = shallow(<FontListItems {...props} />).instance();

			instance.handleFontClick({ id: 'freeserif', type: 'core' });
			instance.handleFontClick({ id: 'arimo', type: 'bundled' });

			expect(toggleUpdateFont).not.toHaveBeenCalled();
		});

		test('handleDeleteFont() - An in-use font is not deleted', () => {
			const confirm = jest.fn(() => true);
			global.confirm = confirm;
			props.deleteFont.mockClear();
			const instance = shallow(<FontListItems {...props} />).instance();

			instance.handleDeleteFont(
				{ stopPropagation: jest.fn() },
				{ id: 'freeserif', type: 'core', can_delete: false }
			);

			expect(confirm).not.toHaveBeenCalled();
			expect(props.deleteFont).not.toHaveBeenCalled();
		});

		test('getDeleteConfirmation() - A core font says whether it comes back', () => {
			GFPDF.fontManagerDeleteFontConfirmation = 'Sure?';
			GFPDF.fontManagerCoreConfirm = 'Delete %s, it comes back';
			GFPDF.fontManagerCoreConfirmOffline = 'Delete %s, it stays gone';
			const instance = shallow(<FontListItems {...props} />).instance();
			const font = { font_name: 'Free Serif', type: 'core' };

			expect(instance.getDeleteConfirmation({ type: 'custom' })).toBe(
				'Sure?'
			);
			expect(
				instance.getDeleteConfirmation({ ...font, auto_install: true })
			).toBe('Delete Free Serif, it comes back');
			expect(
				instance.getDeleteConfirmation({ ...font, auto_install: false })
			).toBe('Delete Free Serif, it stays gone');
		});

		test('isDeleteLocked() - Off until usage loads, and while in use or not allowed', () => {
			const font = { id: 'garuda', type: 'core', can_delete: true };
			const locked = (fontUsage, row = font) =>
				shallow(<FontListItems {...props} fontUsage={fontUsage} />)
					.instance()
					.isDeleteLocked(row);

			expect(locked(null)).toBe(true);
			expect(locked({})).toBe(false);
			expect(locked({ garuda: ['Default font'] })).toBe(true);
			expect(locked({}, { ...font, can_delete: false })).toBe(true);
		});

		test('render() - Each type gets its tag, in-use reasons and delete state', () => {
			GFPDF.fontManagerTypeCore = 'Core';
			GFPDF.fontManagerTypeBundled = 'Bundled';
			GFPDF.fontManagerNetworkOnly = 'Network only';
			const base = {
				regular: 'a.ttf',
				italics: '',
				bold: '',
				bolditalics: '',
			};
			const wrapper = shallow(
				<FontListItems
					{...props}
					loading={false}
					fontUsage={{
						arimo: ['Default font'],
						freeserif: ['Used by 1 PDF'],
					}}
					fontList={[
						{
							...base,
							id: 'arimo',
							font_name: 'Arimo',
							type: 'bundled',
							can_delete: false,
						},
						{
							...base,
							id: 'freeserif',
							font_name: 'Free Serif',
							type: 'core',
							can_delete: true,
						},
						{
							...base,
							id: 'khmeros',
							font_name: 'Khmer OS',
							type: 'core',
							can_delete: true,
						},
					]}
				/>
			);
			const rows = wrapper.find('[data-test="font-list-item"]');
			const trash = (index) => rows.at(index).find('.dashicons-trash');

			expect(rows.at(0).find('.font-type').text()).toBe('Bundled');
			expect(trash(0).exists()).toBe(false);
			expect(rows.at(0).prop('tabIndex')).toBe(undefined);

			expect(rows.at(1).find('.font-type').text()).toBe('Core');
			expect(rows.at(1).find('.font-in-use').text()).toBe(
				'Used by 1 PDF'
			);
			expect(trash(1).hasClass('disabled')).toBe(true);
			expect(trash(1).prop('title')).toBe('Used by 1 PDF');

			expect(rows.at(2).find('.font-in-use').exists()).toBe(false);
			expect(trash(2).hasClass('disabled')).toBe(false);

			expect(
				wrapper.find('[data-test="font-list-network-note"]').exists()
			).toBe(false);
		});

		test('render() - Says why when this user cannot delete core fonts', () => {
			GFPDF.fontManagerNetworkOnly = 'Network only';
			const wrapper = shallow(
				<FontListItems
					{...props}
					fontList={[
						{
							...props.fontList[0],
							id: 'garuda',
							type: 'core',
							can_delete: false,
						},
					]}
				/>
			);

			expect(
				wrapper.find('[data-test="font-list-network-note"]').text()
			).toBe('Network only');
		});

		test("handleDeleteFontKeypress() - Listen to an 'enter' keyboard event for font deletion", () => {
			global.confirm = () => true;

			const wrapper = shallow(<FontListItems {...props} />);
			const instance = wrapper.instance();
			const handleDeleteFont = jest.spyOn(instance, 'handleDeleteFont');
			const e = {
				key: 'Enter',
				stopPropagation: jest.fn(),
			};

			instance.handleDeleteFontKeypress(e, {
				id: 'arial',
				type: 'custom',
				can_delete: true,
			});

			expect(handleDeleteFont).toHaveBeenCalledTimes(1);
		});

		test('handleSelectFont() - Handle the process of selecting and deselecting of font type/name (radio button)', () => {
			const wrapper = shallow(<FontListItems {...props} />);
			const instance = wrapper.instance();
			const e = { target: { value: 'arial' } };

			instance.handleSelectFont(e, 'click');

			expect(props.selectFont).toHaveBeenCalledTimes(1);
		});

		test("handleSelectFontKeypress() - Listen to an 'enter' or 'space' keyboard event for selecting a font type/name (radio button)", () => {
			const wrapper = shallow(<FontListItems {...props} />);
			const instance = wrapper.instance();
			const handleSelectFont = jest.spyOn(instance, 'handleSelectFont');
			const e = {
				key: 'Enter',
				preventDefault: jest.fn(),
				stopPropagation: jest.fn(),
				target: { value: 'arial' },
			};

			instance.handleSelectFontKeypress(e);

			expect(e.preventDefault).toHaveBeenCalledTimes(1);
			expect(e.stopPropagation).toHaveBeenCalledTimes(1);
			expect(handleSelectFont).toHaveBeenCalledTimes(1);
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

		test('render delete trash icon', () => {
			const wrapper = shallow(<FontListItems {...props} />);
			expect(wrapper.find('span.dashicons-trash').length).toBe(1);
		});

		test('render radio button for select font name', () => {
			const wrapper = shallow(<FontListItems {...props} />);
			const data = 'select-font-name-' + props.fontList[0].id;

			expect(wrapper.find(`input[name="${data}"]`).length).toBe(1);
		});

		test('render font name', () => {
			const wrapper = shallow(<FontListItems {...props} />);
			expect(wrapper.find('span.font-name').text()).toBe(
				'Fira Sans Light'
			);
		});

		test('render <FontListIcon /> component', () => {
			const wrapper = shallow(<FontListItems {...props} />);
			expect(wrapper.find('FontListIcon')).toHaveLength(4);
		});
	});
});
