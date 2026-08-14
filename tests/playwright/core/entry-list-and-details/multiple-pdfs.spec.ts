import { expect } from '@wordpress/e2e-test-utils-playwright';
import { snapshot } from '@self:playwright/utils/snapshot';
import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { FIXED_ENTRY_DATE } from '@self:playwright/utils/gravityforms';

test.describe('Multiple PDF', () => {
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
			form = await pdf.createForm('Multiple PDF');

			// setup PDF
			for (let i = 1; i <= 2; i++) {
				await pdf.createPdf(form.id, `Multiple #${i}`);
			}

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
		const pdfLink = page.getByRole('link', { name: 'View PDFs' });
		await page.locator('.has-row-actions').first().hover();
		await pdfLink.hover();

		await snapshot(page, testinfo);

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
		await pdf.navigateToEntryDetail(entry.form_id, entry.id);
		await expect(
			page.getByLabel('View or download Multiple #2.pdf')
		).toBeAttached();

		await snapshot(page, testinfo);
	});
});
