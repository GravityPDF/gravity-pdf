import { associatedFontManagerSelectBox } from '../../../../../src/assets/js/react/utilities/FontManager/associatedFontManagerSelectBox';

describe('Utilities/FontManager - associatedFontManagerSelectBox.js', () => {
	const fontList = [
		{
			bold: '',
			bolditalics: '',
			font_name: 'Gugi',
			type: 'custom',
			id: 'gugi',
			italics: '',
			regular: 'Gugi-Regular.ttf',
		},
		{
			bold: '',
			bolditalics: '',
			font_name: 'Gotham',
			type: 'custom',
			id: 'gotham',
			italics: '',
			regular: 'Gotham-Black-Regular.ttf',
		},
	];

	beforeEach(() => {
		// Mock font manager select box DOM
		document.body.innerHTML =
			'<div class="gfpdf-font-manager">' +
			' <select id="gfpdf_settings[default_font]" class="gfpdf_settings_default_font " name="gfpdf_settings[default_font]" data-placeholder="">' +
			'   <optgroup label="User-Defined Fonts">' +
			'     <option value="gugi">Gugi</option>' +
			'     <option value="gotham">Gotham</option>' +
			'   </optgroup>' +
			' </select>' +
			'</div>';
	});

	test('associatedFontManagerSelectBox() - Set current selected font value', () => {
		associatedFontManagerSelectBox(fontList, 'gugi');

		expect(document.querySelector('.gfpdf-font-manager select').value).toBe(
			'gugi'
		);
	});

	test('associatedFontManagerSelectBox() - Only custom fonts join the User-Defined group, and a core font can be selected', () => {
		document
			.querySelector('select')
			.insertAdjacentHTML(
				'beforeend',
				'<optgroup label="Unicode"><option value="freeserif">Free Serif</option></optgroup>'
			);
		GFPDF.fontUserDefinedGroup = 'User-Defined Fonts';

		associatedFontManagerSelectBox(
			[{ id: 'freeserif', font_name: 'Free Serif', type: 'core' }],
			'freeserif'
		);

		const select = document.querySelector('.gfpdf-font-manager select');
		expect(select.value).toBe('freeserif');
		expect(
			document.querySelector('optgroup[label="User-Defined Fonts"]')
		).toBe(null);
		expect(
			select.querySelectorAll('option[value="freeserif"]').length
		).toBe(1);
	});

	test('associatedFontManagerSelectBox() - Set empty value if selected item is deleted', () => {
		associatedFontManagerSelectBox(fontList, 'arial');
		expect(document.querySelector('.gfpdf-font-manager select').value).toBe(
			''
		);
	});
});
