import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('Form PDF Settings', () => {
	let pdf = null;
	let form = null;

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
			form = await pdf.createForm('Form PDF Settings');

			// Reset TinyMCE to default to the Visual tab
			await pdf.navigateToNewFormPdf(form.id);
			for (const button of await page
				.getByRole('button', { name: /^Visual$/ })
				.all()) {
				await button.click();
			}
		}
	);

	test('Add New PDF', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await expect(page.locator('#tab_PDF')).toHaveScreenshot({});
	});

	test('Update Existing PDF', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const pdfId = await pdf.createPdf(form.id, 'Existing PDF');
		await pdf.navigateToFormPdf(form.id, pdfId);

		await expect(page.locator('#tab_PDF')).toHaveScreenshot();
	});

	test('Notifications', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

    const elements = page.locator('#gfpdf-settings-field-wrapper-notification').getByRole('checkbox')
    await expect(elements).toHaveCount(1)

    // Add another Notification
    await pdf.addNotification(form.id, 'User Notification');

    await pdf.navigateToNewFormPdf(form.id);
    await expect(elements).toHaveCount(2)
	});
});
