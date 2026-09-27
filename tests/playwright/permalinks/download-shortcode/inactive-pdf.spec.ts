import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { withConfirmation } from '@self:playwright/utils/gravityforms';

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
			// setup form, inactive PDF and default confirmation
			pdf = new Pdf(requestUtils, admin, page);

			form = await pdf.createForm('Inactive PDF on Text Confirmation');
			const pdfId = await pdf.addPdf(form.id, 'Inactive PDF Document', {
				active: false,
			});
			await pdf.updateForm(
				form.id,
				withConfirmation({ message: `[gravitypdf id="${pdfId}"]` })
			);
		}
	);

	test('Disabled PDF, Debug Mode', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		// Debug Mode is site-wide, so it's switched on for this page's requests alone (see tools/mu-plugins)
		await page.setExtraHTTPHeaders({ 'X-GPDF-E2E-Debug-Mode': 'yes' });

		// preview and submit form
		await pdf.navigateToFormPreview(form.id);
		await pdf.submitForm();

		// verify the results
		await expect(
			page.getByRole('link', { name: 'Download PDF' })
		).not.toBeAttached();
		await expect(page.getByText('PDF link not displayed')).toContainText(
			'Admin Only Message'
		);

		await page.setExtraHTTPHeaders({});

		// preview and submit form
		await pdf.navigateToFormPreview(form.id);
		await pdf.submitForm();

		// verify the results
		await expect(
			page.getByRole('link', { name: 'Download PDF' })
		).not.toBeAttached();

		await expect(page.locator('.gform_confirmation_message')).toBeEmpty();
	});
});
