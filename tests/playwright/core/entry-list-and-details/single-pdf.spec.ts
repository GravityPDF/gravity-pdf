import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { FIXED_ENTRY_DATE } from '@self:playwright/utils/gravityforms';
import { snapshot } from '@self:playwright/utils/snapshot';

test.describe('Single PDF', () => {
	let form = null;
	let pdf = null;
	let entry = null;

	test.beforeEach(
		async ({
			requestUtils,
			page,
			admin,
		}: {
			requestUtils: RequestUtils;
			page: Page;
			admin: Admin;
		}) => {
			// setup form
			pdf = new Pdf(requestUtils, admin, page);
			form = await pdf.createForm('Single PDF');

			// setup PDF
			await pdf.setGlobalPdfSetting('View', true);
			await pdf.createPdf(form.id, 'Single #1');

			// create entry
			entry = await pdf.createEntry({
				form_id: form.id,
				date_created: FIXED_ENTRY_DATE,
			});
		}
	);

	test('Entry List, View', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}, testinfo) => {
		await pdf.navigateToEntryList(form.id);
		const pdfLink = page.getByRole('link', { name: 'View PDF' });
		await page.locator('.has-row-actions').first().hover();

		await snapshot(page, testinfo);
		await pdf.downloadAndVerifyPdf(pdfLink, 'Single #1.pdf');
	});

	test('Entry Details', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.navigateToEntryDetail(entry.form_id, entry.id);

		const viewPdfLink = page.getByRole('link', {
			name: 'View',
			exact: true,
		});
		const downloadPdfLink = page.getByRole('link', {
			name: 'Download',
			exact: true,
		});

		await pdf.downloadAndVerifyPdf(viewPdfLink, 'Single #1.pdf');
		await pdf.downloadAndVerifyPdf(downloadPdfLink, 'Single #1.pdf');
	});
});
