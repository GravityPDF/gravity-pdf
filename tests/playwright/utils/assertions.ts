import {
	expect,
} from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';

export default class Assertions {
	private page: Page;

	constructor(page: Page) {
		this.page = page;
	}

	async downloadAndVerifyPdf(pdfLink, expectedFilename: string) {
		await expect(pdfLink).toBeAttached();

		const downloadPromise = this.page.waitForEvent('download');
		await pdfLink.click();
		const download = await downloadPromise;

		// download the PDF and verify it's valid
		expect(download.suggestedFilename()).toBe(expectedFilename);
		const pdfStream = await download.createReadStream();

		async function readPdf(readable) {
			readable.setEncoding('utf8');
			let data = '';
			for await (const chunk of readable) {
				data += chunk;
			}

			return data;
		}

		const pdfContent = await readPdf(pdfStream);
		expect(pdfContent.substring(0, 7)).toEqual(
			expect.stringContaining('%PDF-1.')
		);
		expect(pdfContent).toEqual(expect.stringContaining('startxref'));
		expect(pdfContent).toEqual(expect.stringContaining('%%EOF'));
	}
}
