import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('Advanced Template Checks', () => {
	let pdf: Pdf;
	let form: any;

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
			pdf = new Pdf(requestUtils, admin, page);
			form = await pdf.createForm('Advanced Template Checks');
		}
	);

	test('should successfully saved toggled switch value for active and inactive template', async ({
		page,
	}) => {
		await pdf.navigateToFormPdfList(form.id);
		const pdfId = await pdf.createPdf(form.id, 'Toggle Test');

		await pdf.navigateToFormPdfList(form.id);
		const toggle = page
			.locator(`#gfpdf-${pdfId}`)
			.locator('button.gform-status-indicator');

		// Toggle off
		await toggle.click();
		await expect(toggle).toHaveAttribute('data-status', 'inactive');

		// Toggle on
		await toggle.click();
		await expect(toggle).toHaveAttribute('data-status', 'active');
	});

	test('should check that "View PDF" link is hidden when template is inactive', async ({
		page,
	}) => {
		await pdf.navigateToFormPdfList(form.id);
		const pdfId = await pdf.createPdf(form.id, 'Inactive Test');

		await pdf.navigateToFormPdfList(form.id);
		const toggle = page
			.locator(`#gfpdf-${pdfId}`)
			.locator('button.gform-status-indicator');
		await toggle.click();

		const entry = await pdf.createEntry({ form_id: form.id });
		await pdf.navigateToEntryList(form.id);

		// Entry list selectors might vary, using a common one
		await page.locator('td.column-primary').first().hover();
		await expect(
			page.locator('.gravitypdf-download-link')
		).not.toBeVisible();
	});

	test('should check that "View PDF" link is hidden when conditional logic fails', async ({
		page,
	}) => {
		await pdf.navigateToFormPdfList(form.id);
		const pdfId = await pdf.createPdf(form.id, 'Conditional Test');

		await pdf.navigateToFormPdf(form.id, pdfId!);
		await page
			.getByRole('checkbox', {
				name: 'Enable conditional logic',
				exact: true,
			})
			.check();

		await page.locator('#gfpdf_rule_value_0').selectOption('Third Choice');
		await pdf.addOrUpdatePdf();

		await pdf.createEntry({ form_id: form.id });
		await pdf.navigateToEntryList(form.id);

		await page.locator('td.column-primary').first().hover();
		await expect(page.getByText('View PDF')).not.toBeVisible();
	});

	test('should successfully duplicate existing PDF', async ({ page }) => {
		await pdf.navigateToFormPdfList(form.id);
		await pdf.createPdf(form.id, 'Source PDF');

		await pdf.navigateToFormPdfList(form.id);
		await page.locator('.name').first().hover();
		await page.getByRole('link', { name: 'Duplicate' }).click();

		await expect(page.locator('tr[id^="gfpdf-"]')).toHaveCount(2);
	});
});
