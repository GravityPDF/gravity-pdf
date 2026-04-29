import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import type { Form } from '@self:playwright/utils/gravityforms';

test.describe('Mergetag attributes', () => {
	let pdf: Pdf;
	let form: Form;
	let pdfId: string;

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

			// setup form and PDF
			form = await pdf.createForm('Mergetag Attributes');
			await pdf.navigateToFormPreview(form.id);
			await pdf.submitForm();
			pdfId = (await pdf.createPdf(form.id, 'Mergetag'))!;

			// setup default confirmation
			await pdf.navigateToFormConfirmation(form.id);

			// Clear confirmation message and use the mergetag selector
			const content = `
        PDF URL: {Mergetag:pdf:${pdfId}}
        `;
			await pdf.setRichTextContent('#gform_setting_message', content);
			await pdf.submitForm();
		}
	);

	test('Check merge tag generates a URL', async ({
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
		await pdf.submitForm();

		// verify the results
		const confirmation = await page.locator('#preview_form_container');

		await expect(confirmation).toContainText(
			new RegExp(
				`PDF URL: http:\/\/(.+?)\/(\\?gpdf=1&pid=${pdfId}&lid=([0-9]+)|pdf\/${pdfId}\/([0-9]+)\/)`
			)
		);
	});
});
