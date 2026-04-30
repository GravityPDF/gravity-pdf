import { templateSnippet } from '../../../../../src/assets/js/react/utilities/FontManager/templateSnippet';

describe('Utilities/FontManager - templateSnippet', () => {
	test('produces a deterministic class name for a given id', () => {
		const a = templateSnippet({ id: 'roboto', font_name: 'Roboto' });
		const b = templateSnippet({ id: 'roboto', font_name: 'Roboto' });
		expect(a.className).toEqual(b.className);
	});

	test('class is the slug + 4-digit hash with no hyphens between', () => {
		const { className } = templateSnippet({
			id: 'open-sans',
			font_name: 'Open Sans',
		});
		expect(className).toMatch(/^font-opensans\d{4}$/);
	});

	test('snippet contains the class and a font-family declaration', () => {
		const { className, snippet } = templateSnippet({
			id: 'roboto',
			font_name: 'Roboto',
		});
		expect(snippet).toContain(`<style>`);
		expect(snippet).toContain(`.${className}`);
		expect(snippet).toContain(`font-family: ${className}`);
		expect(snippet).toContain(`<div class="${className}">Text</div>`);
	});

	test('serif fonts get a serif fallback', () => {
		const { snippet } = templateSnippet({
			id: 'playfair-display',
			font_name: 'Playfair Display',
		});
		expect(snippet).toContain('serif');
	});

	test('sans fonts get a sans-serif fallback', () => {
		const { snippet } = templateSnippet({
			id: 'roboto',
			font_name: 'Roboto',
		});
		expect(snippet).toContain('sans-serif');
	});
});
