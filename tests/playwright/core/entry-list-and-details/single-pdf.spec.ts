import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { takeSnapshot } from '@chromatic-com/playwright';
import type { Entry, Form } from '@self:playwright/utils/gravityforms';

test.describe('Single PDF', () => {
	let form: Form;
	let pdf: Pdf;
	let entry: Entry;

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
			entry = await pdf.createEntry({ form_id: form.id });
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

		await takeSnapshot(page, testinfo);
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
		await pdf.navigateToEntryDetail(entry.form_id, entry.id || 0);

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
