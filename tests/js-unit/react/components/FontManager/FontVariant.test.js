import React from 'react';
import { shallow, mount } from 'enzyme';
import { findByTestAttr } from '../../testUtils';
import FontVariant from '../../../../../src/assets/js/react/components/FontManager/FontVariant';

describe('FontManager - FontVariant.js', () => {
	const props = {
		state: 'addFont',
		fontStyles: {
			regular:
				'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/FiraSans-Regular.ttf',
			italics:
				'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/FiraSans-Italic.ttf',
			bold: 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/FiraSans-SemiBold.ttf',
			bolditalics:
				'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts/FiraSans-SemiBoldItalic.ttf',
		},
		validateRegular: true,
		onHandleUpload: jest.fn(),
		onHandleDeleteFontStyle: jest.fn(),
		msg: {},
	};

	describe('RENDERS COMPONENT', () => {
		test('render <FontVariant /> component', () => {
			const wrapper = shallow(<FontVariant {...props} />);
			const component = findByTestAttr(wrapper, 'component-FontVariant');

			expect(component.length).toBe(1);
		});

		test('render <Dropzone /> component', () => {
			const wrapper = shallow(<FontVariant {...props} />);
			const component = findByTestAttr(wrapper, 'component-Dropzone');

			expect(component.length).toBe(4);
		});

		const row = (wrapper, key) => wrapper.find(`[data-variant="${key}"]`);

		test('A style without a file offers an upload', () => {
			const wrapper = mount(
				<FontVariant {...props} fontStyles={{ regular: '' }} />
			);
			const regular = row(wrapper, 'regular').hostNodes();

			expect(regular.hasClass('missing')).toBe(true);
			expect(regular.find('.gfpdf-font-filename').text()).toBe(
				'No .ttf file added'
			);
			expect(regular.find('.font-variant-actions button').text()).toBe(
				'+ Upload'
			);
			expect(
				findByTestAttr(wrapper, 'component-FontVariant-input').length
			).toBe(1);
		});

		test('A style with a file shows its name and can be replaced', () => {
			const wrapper = mount(<FontVariant {...props} />);
			const bold = row(wrapper, 'bold').hostNodes();

			expect(bold.find('.gfpdf-font-filename').text()).toBe(
				'FiraSans-SemiBold.ttf'
			);
			expect(bold.find('.font-variant-actions button').at(0).text()).toBe(
				'Replace'
			);
		});

		test('Only optional styles can be deleted', () => {
			const wrapper = mount(<FontVariant {...props} />);

			expect(
				findByTestAttr(wrapper, 'component-FontVariant-delete').length
			).toBe(3);
			expect(
				row(wrapper, 'regular').find('.font-variant-delete').exists()
			).toBe(false);

			props.onHandleDeleteFontStyle.mockClear();
			row(wrapper, 'bold')
				.find('button.font-variant-delete')
				.simulate('click');

			expect(props.onHandleDeleteFontStyle).toHaveBeenCalledWith(
				expect.anything(),
				'bold',
				'addFont'
			);
		});

		test('A missing required file says so, and a bad file gives its error', () => {
			let wrapper = mount(
				<FontVariant
					{...props}
					fontStyles={{ regular: '' }}
					validateRegular={false}
				/>
			);
			expect(
				row(wrapper, 'regular').hostNodes().hasClass('required')
			).toBe(true);

			wrapper = mount(
				<FontVariant
					{...props}
					msg={{ error: { addFont: { bold: 'Not a TTF' } } }}
				/>
			);
			const bold = row(wrapper, 'bold').hostNodes();
			expect(bold.hasClass('error')).toBe(true);
			expect(bold.find('.gfpdf-font-filename').text()).toBe('Not a TTF');
		});

		test('render <FontVariantLabel /> component', () => {
			const wrapper = mount(<FontVariant {...props} />);

			expect(wrapper.find('FontVariantLabel').length).toBe(4);
		});
	});
});
