/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

interface TemplateSnippetInput {
	id: string;
	font_name: string;
}

interface TemplateSnippetOutput {
	className: string;
	snippet: string;
}

/**
 * Build a deterministic CSS class name and HTML/CSS snippet for a saved
 * custom font. The class shape is `font-{slugNoHyphens}{4-digit hash of id}`
 * — the hash makes it stable across re-mounts (matches prototype).
 *
 * @param input
 */
export function templateSnippet(
	input: TemplateSnippetInput
): TemplateSnippetOutput {
	const { id, font_name: fontName } = input;
	const slug = fontName
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.replace(/^-+|-+$/g, '');
	const slugNoHyphens = slug.replace(/-/g, '');

	let h = 0;
	for (let i = 0; i < id.length; i++) {
		/* Simple positive deterministic 32-bit hash (avoids unsigned-shift). */
		h = (h * 31 + id.charCodeAt(i)) % 0x100000000;
	}
	const hash = String(h % 10000).padStart(4, '0');

	const className = `font-${slugNoHyphens}${hash}`;
	const fallback = /serif|playfair|garamond|baskerville|caslon/i.test(
		fontName
	)
		? 'serif'
		: 'sans-serif';

	const snippet =
		`<style>\n` +
		`.${className} {\n` +
		`  font-family: ${className}, ${fallback};\n` +
		`}\n` +
		`</style>\n` +
		`\n` +
		`<div class="${className}">Text</div>`;

	return { className, snippet };
}
