import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('PDF Cache Header', () => {
	test('should report a cache miss, then a hit, in debug mode', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const pdf = new Pdf(requestUtils, admin, page);
		const form = await pdf.createForm('Cache Header');
		const pdfId = await pdf.addPdf(form.id, 'Cache Header');
		const entry = await pdf.createEntry({ form_id: form.id });

		const url = `/?gpdf=1&pid=${pdfId}&lid=${entry.id}`;
		const headers = { 'X-GPDF-E2E-Debug-Mode': 'yes' };

		const miss = await page.request.get(url, { headers });
		expect(miss.headers()['content-type']).toBe('application/pdf');
		expect(miss.headers()['x-gpdf-cache']).toBe('miss');

		const hit = await page.request.get(url, { headers });
		expect(hit.headers()['x-gpdf-cache']).toBe('hit');
		expect(await hit.body()).toEqual(await miss.body());
	});
});
