import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('[gravitypdf] Shortcode', () => {
	test('Redirect confirmation', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const pdf = new Pdf(requestUtils, admin, page);

		// setup form and PDF
		const form = await pdf.createForm('Redirect Confirmation');
		const pdfId = await pdf.addPdf(
			form.id,
			'Redirect Confirmation Document'
		);

		// setup default confirmation through the UI: Gravity PDF adds the entry to the shortcode as it saves
		await pdf.navigateToFormConfirmation(form.id);
		await page.getByRole('radio', { name: 'Redirect' }).check();
		await page
			.getByRole('textbox', { name: 'Redirect URL' })
			.fill(`[gravitypdf id="${pdfId}"]`);
		await pdf.submitForm();

		// preview and submit form
		await pdf.navigateToFormPreview(form.id);
		await pdf.downloadAndVerifyPdf(
			page.getByRole('button', { name: /(save|submit)/i }),
			'Redirect Confirmation Document.pdf'
		);
	});
});
