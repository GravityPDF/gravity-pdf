import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('PDF Cache Headers', () => {
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
		const headers = {
			'X-GPDF-E2E-Debug-Mode': 'yes',
			'X-GPDF-E2E-Cache-Generation': '1',
		};

		const miss = await page.request.get(url, { headers });
		expect(miss.headers()['content-type']).toBe('application/pdf');
		expect(miss.headers()['x-gpdf-cache']).toBe('miss');

		const hit = await page.request.get(url, { headers });
		expect(hit.headers()['x-gpdf-cache']).toBe('hit');
		expect(await hit.body()).toEqual(await miss.body());
	});

	test('should send a 304 when the browser has the current PDF', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const pdf = new Pdf(requestUtils, admin, page);
		const form = await pdf.createForm('Conditional Requests');
		const pdfId = await pdf.addPdf(form.id, 'Conditional Requests');
		const entry = await pdf.createEntry({ form_id: form.id });

		const url = `/?gpdf=1&pid=${pdfId}&lid=${entry.id}`;
		const headers = {
			'X-GPDF-E2E-Cache-Generation': '1',
			// Content-Length used to be dropped for any Accept-Encoding
			'Accept-Encoding': 'gzip, deflate',
		};

		const first = await page.request.get(url, { headers });
		expect(first.status()).toBe(200);

		const etag = first.headers().etag;
		const lastModified = first.headers()['last-modified'];
		expect(etag).toMatch(/^"[^"]+"$/);
		expect(lastModified).toBeTruthy();
		expect(Number(first.headers()['content-length'])).toBe(
			(await first.body()).length
		);

		const conditional = (conditions: Record<string, string>) =>
			page.request.get(url, { headers: { ...headers, ...conditions } });

		for (const conditions of [
			{ 'If-None-Match': etag },
			{ 'If-None-Match': `"stale", W/${etag}` },
			{ 'If-Modified-Since': lastModified },
		]) {
			expect((await conditional(conditions)).status()).toBe(304);
		}

		const stale = await conditional({
			'If-None-Match': '"stale"',
			'If-Modified-Since': lastModified,
		});
		expect(stale.status()).toBe(200);
		expect(stale.headers()['content-type']).toBe('application/pdf');
		expect(await stale.body()).toEqual(await first.body());
	});
});
