import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import type { Entry, Form } from '@self:playwright/utils/gravityforms';

test.describe('Inactive PDF', () => {
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
			// setup form and inactive PDF
			pdf = new Pdf(requestUtils, admin, page);
			form = await pdf.createForm('Inactive PDF');

			const pdfId = await pdf.createPdf(form.id, 'Inactive PDF');
			await pdf.navigateToFormPdfList(form.id);
			await page
				.getByRole('button', { name: 'Active', exact: true })
				.click();

			// create entry
			entry = await pdf.createEntry({ form_id: form.id });
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
		await expect(
			page.getByRole('link', { name: 'View PDF' })
		).not.toBeAttached();

		await pdf.createPdf(form.id, 'Active PDF');

		await pdf.navigateToEntryList(form.id);
		await expect(
			page.getByRole('link', { name: 'View PDF' })
		).toBeAttached();
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
		await pdf.navigateToEntryDetail(entry.form_id, entry.id!);
		await expect(
			page.getByRole('link', { name: 'View', exact: true })
		).not.toBeAttached();
		await expect(
			page.getByRole('link', { name: 'Download', exact: true })
		).not.toBeAttached();

		await pdf.createPdf(form.id, 'Active PDF');

		await pdf.navigateToEntryDetail(entry.form_id, entry.id!);
		await expect(
			page.getByRole('link', { name: 'View', exact: true })
		).toBeAttached();
		await expect(
			page.getByRole('link', { name: 'Download', exact: true })
		).toBeAttached();
	});
});
