import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('Tmp cleanup', () => {
	test('keeps the mPDF cache in a filtered folder inside tmp/', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const pdf = new Pdf(requestUtils, admin, page);
		const folder = `e2e-mpdf-${Date.now()}`;
		const form = await pdf.createForm('Tmp Cleanup');
		const [pdfId, entry] = await Promise.all([
			pdf.addPdf(form.id, 'Tmp Cleanup'),
			pdf.createEntry({ form_id: form.id }),
		]);

		await page.setExtraHTTPHeaders({
			'X-GPDF-E2E-Mpdf-Tmp-Location': folder,
		});
		await pdf.gotoPdfAndVerify(
			`/?gpdf=1&pid=${pdfId}&lid=${entry.id}`,
			'Tmp Cleanup.pdf'
		);

		expect(pdf.cleanupKeepsMpdfCache(folder)).toBe(true);
	});
});
