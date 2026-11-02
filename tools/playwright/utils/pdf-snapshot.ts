import type { TestInfo } from '@playwright/test';
import { expect } from '@playwright/test';
import * as fs from 'node:fs/promises';
import * as path from 'node:path';
import { shotName, visualEnabled, VISUAL_SHOTS_DIR } from './snapshot';

/**
 * Each page's size and text, one line per text row, so a changed word shows in the diff
 * @param pdf
 */
async function pdfText(pdf: Buffer) {
	// ESM-only, so import dynamically
	const { getDocument } = await import('pdfjs-dist/legacy/build/pdf.mjs');
	const doc = await getDocument({ data: new Uint8Array(pdf), verbosity: 0 })
		.promise;

	const pages: string[] = [];
	for (let i = 1; i <= doc.numPages; i++) {
		const page = await doc.getPage(i);
		const [, , width, height] = page.view.map(Math.round);
		const content = await page.getTextContent();

		const text = content.items
			.map((item) =>
				'str' in item ? item.str + (item.hasEOL ? '\n' : '') : ''
			)
			.join('')
			.split('\n')
			.map((line) => line.trim())
			.filter(Boolean)
			.join('\n');

		pages.push(
			`--- Page ${i} of ${doc.numPages} (${width}x${height}pt) ---\n${text}`
		);
	}

	await doc.destroy();

	return pages.join('\n\n') + '\n';
}

/**
 * Snapshot a PDF's text, and write each page as a PNG for Lost Pixel
 * @param testinfo
 * @param pdf
 */
export async function pdfSnapshot(testinfo: TestInfo, pdf: Buffer) {
	await testinfo.attach('pdf', { body: pdf, contentType: 'application/pdf' });

	expect(await pdfText(pdf)).toMatchSnapshot();

	if (!visualEnabled) {
		return;
	}

	const { pdfToPng } = await import('pdf-to-png-converter');
	const pages = await pdfToPng(new Uint8Array(pdf));

	await fs.mkdir(VISUAL_SHOTS_DIR, { recursive: true });
	await Promise.all(
		pages.map((page) =>
			fs.writeFile(
				path.join(
					VISUAL_SHOTS_DIR,
					shotName(testinfo, `page-${page.pageNumber}`)
				),
				page.content!
			)
		)
	);
}
