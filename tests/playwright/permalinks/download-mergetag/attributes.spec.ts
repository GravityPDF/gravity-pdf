import { expect } from '@wordpress/e2e-test-utils-playwright';
import { test } from '@self:playwright/fixtures/test';
import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { withConfirmation } from '@self:playwright/utils/gravityforms';

test.describe('Mergetag attributes', () => {
	let pdf = null;
	let form = null;
	let pdfId = null;

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

			// setup form, PDF and a default confirmation carrying its merge tag
			form = await pdf.createForm('Mergetag Attributes');
			pdfId = await pdf.addPdf(form.id, 'Mergetag');
			await pdf.updateForm(
				form.id,
				withConfirmation({
					message: `PDF URL: {Mergetag:pdf:${pdfId}}`,
				})
			);
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
