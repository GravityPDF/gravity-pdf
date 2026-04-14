import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('[gravitypdf] Shortcode', () => {
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
			form = await pdf.createForm('Inactive PDF on Text Confirmation');

			await pdf.setGlobalPdfSetting('Debug Mode', false);
			const pdfId = await pdf.createPdf(form.id, 'Inactive PDF Document');
			await pdf.navigateToFormPdfList(form.id);
			await page
				.getByRole('button', { name: 'Active', exact: true })
				.click();

			// setup default confirmation
			await pdf.navigateToFormConfirmation(form.id);
			await pdf.setRichTextContent(
				'#gform_setting_message',
				`[gravitypdf id="${pdfId}"]`
			);
			await pdf.saveForm();
		}
	);

	test.only('Disabled PDF, Debug Mode Off', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		// preview and submit form
		await pdf.navigateToFormPreview(form.id);
		await pdf.saveForm();

		// verify the results
		await expect(
			page.getByRole('link', { name: 'Download PDF' })
		).not.toBeAttached();

		await expect(page.locator('.gform_confirmation_message')).toBeEmpty();
	});

	test('Disabled PDF, Debug Mode On', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.setGlobalPdfSetting('Debug Mode', true);

		// preview and submit form
		await pdf.navigateToFormPreview(form.id);
		await pdf.saveForm();

		// verify the results
		await expect(
			page.getByRole('link', { name: 'Download PDF' })
		).not.toBeAttached();
		await expect(page.getByText('PDF link not displayed')).toContainText(
			'Admin Only Message'
		);
	});
});
