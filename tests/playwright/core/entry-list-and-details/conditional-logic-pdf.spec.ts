import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test, expect } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import type { Entry, Form } from '@self:playwright/utils/gravityforms';

test.describe('Conditional PDF', () => {
	let form: Form;
	let pdf: Pdf;
	let entry1: Entry;
	let entry2: Entry;

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
			// setup form and inactive PDF
			pdf = new Pdf(requestUtils, admin, page);
			form = await pdf.createForm('Conditional PDF');

			// setup PDF with conditional logic
			const pdfId = await pdf.createPdf(form.id, 'Conditional PDF');
			await page
				.getByRole('checkbox', {
					name: 'Enable conditional logic',
					exact: true,
				})
				.check();
			await pdf.addOrUpdatePdf();

			// create entry
			entry1 = await pdf.createEntry({ form_id: form.id });
			entry2 = await pdf.createEntry({
				form_id: form.id,
				1: 'First Choice',
			} as any);
		}
	);

	test('Entry List', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.navigateToEntryList(form.id);
		await expect(page.getByRole('link', { name: 'View PDF' })).toHaveCount(
			1
		);
	});

	test('Entry Detail', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.navigateToEntryDetail(entry1.form_id, entry1.id!);
		await expect(
			page.getByRole('link', { name: 'View', exact: true })
		).not.toBeAttached();
		await expect(
			page.getByRole('link', { name: 'Download', exact: true })
		).not.toBeAttached();

		await pdf.navigateToEntryDetail(entry2.form_id, entry2.id!);
		await expect(
			page.getByRole('link', { name: 'View', exact: true })
		).toBeAttached();
		await expect(
			page.getByRole('link', { name: 'Download', exact: true })
		).toBeAttached();
	});
});
