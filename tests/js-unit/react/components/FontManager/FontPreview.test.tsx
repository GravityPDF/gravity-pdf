import { renderWithStore } from '../../testUtilsRTL';
import FontPreview from '../../../../../src/assets/js/react/components/FontManager/FontPreview';
import type { FontVariantStyles } from '../../../../../src/assets/js/react/types';

const blankStyles: FontVariantStyles = {
	regular: '',
	italics: '',
	bold: '',
	bolditalics: '',
};

describe('FontManager - FontPreview', () => {
	beforeEach(() => {
		document
			.querySelectorAll('style[data-gfpdf-fontfaces]')
			.forEach((el) => el.remove());
	});

	test('renders nothing when no variants are uploaded', () => {
		const { container } = renderWithStore(
			<FontPreview familyId="roboto" fontStyles={blankStyles} />
		);
		expect(container.firstChild).toBeNull();
	});

	test('renders a preview block for each uploaded variant', () => {
		const { getAllByText } = renderWithStore(
			<FontPreview
				familyId="roboto"
				fontStyles={{
					regular: 'paths/Roboto-Regular.ttf',
					italics: 'paths/Roboto-Italic.ttf',
					bold: '',
					bolditalics: '',
				}}
			/>
		);
		expect(getAllByText(/Annual Report 2026/i)).toHaveLength(2);
	});

	test('mounts a <style data-gfpdf-fontfaces> element for the family', () => {
		const { unmount } = renderWithStore(
			<FontPreview
				familyId="roboto"
				fontStyles={{
					regular: 'paths/Roboto-Regular.ttf',
					italics: '',
					bold: '',
					bolditalics: '',
				}}
			/>
		);
		const styleEl = document.getElementById(
			'gfpdf-fm-fontfaces-roboto'
		) as HTMLStyleElement | null;
		expect(styleEl).not.toBeNull();
		expect(styleEl?.textContent).toContain('@font-face');
		expect(styleEl?.textContent).toContain('font-weight: 400');

		unmount();
		expect(document.getElementById('gfpdf-fm-fontfaces-roboto')).toBeNull();
	});

	test('builds @font-face URL from GFPDF.customFontUrlBase + basename for saved files', () => {
		(
			window.GFPDF as unknown as { customFontUrlBase: string }
		).customFontUrlBase = 'https://example.test/uploads/fonts/';
		renderWithStore(
			<FontPreview
				familyId="lato"
				fontStyles={{
					regular: '/var/www/.../uploads/fonts/Lato-Regular.ttf',
					italics: '',
					bold: '',
					bolditalics: '',
				}}
			/>
		);
		const styleEl = document.getElementById(
			'gfpdf-fm-fontfaces-lato'
		) as HTMLStyleElement | null;
		expect(styleEl?.textContent).toContain(
			"url('https://example.test/uploads/fonts/Lato-Regular.ttf')"
		);
	});

	test('uses URL.createObjectURL for unsaved File variants', () => {
		const objectUrlSpy = jest.spyOn(URL, 'createObjectURL');
		renderWithStore(
			<FontPreview
				familyId="draft-1"
				fontStyles={{
					regular: new File([new Uint8Array(0)], 'Draft-Regular.ttf'),
					italics: '',
					bold: '',
					bolditalics: '',
				}}
			/>
		);
		expect(objectUrlSpy).toHaveBeenCalled();
		objectUrlSpy.mockRestore();
	});
});
