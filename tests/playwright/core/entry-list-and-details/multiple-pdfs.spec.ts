import { takeSnapshot } from '@chromatic-com/playwright';
import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test, expect } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import type { Entry, Form } from '@self:playwright/utils/gravityforms';

test.describe('Multiple PDF', () => {
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
			form = await pdf.createForm('Multiple PDF');

			// setup PDF
			for (let i = 1; i <= 2; i++) {
				await pdf.createPdf(form.id, `Multiple #${i}`);
			}

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
		const pdfLink = page.getByRole('link', { name: 'View PDFs' });
		await page.locator('.has-row-actions').first().hover();
		await pdfLink.hover();

		await takeSnapshot(page, testinfo);

		await pdf.downloadAndVerifyPdf(
			page.getByRole('link', { name: 'Multiple #2' }),
			'Multiple #2.pdf'
		);
	});

	test('Entry Details', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}, testinfo) => {
		await pdf.navigateToEntryDetail(entry.form_id, entry.id!);
		await expect(
			page.getByLabel('View or download Multiple #2.pdf')
		).toBeAttached();

		await takeSnapshot(page, testinfo);
	});
});
