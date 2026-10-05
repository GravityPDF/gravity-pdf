import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('PDF Content-Disposition', () => {
	test('should show a PDF inline, or download it, under its encoded filename', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const pdf = new Pdf(requestUtils, admin, page);
		const form = await pdf.createForm('Content Disposition');
		const pdfId = await pdf.addPdf(form.id, 'Content Disposition', {
			filename: 'Café Report',
		});
		const entry = await pdf.createEntry({ form_id: form.id });

		const url = `/?gpdf=1&pid=${pdfId}&lid=${entry.id}`;
		const filename = `filename="Caf%C3%A9%20Report.pdf"; filename*=utf-8''Caf%C3%A9%20Report.pdf`;

		const view = await page.request.get(url);
		expect(view.headers()['content-disposition']).toBe(
			`inline; ${filename}`
		);

		const download = await page.request.get(`${url}&action=download`);
		expect(download.headers()['content-disposition']).toBe(
			`attachment; ${filename}`
		);
	});
});
