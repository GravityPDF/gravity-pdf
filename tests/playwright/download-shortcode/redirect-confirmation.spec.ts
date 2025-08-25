import type { Admin, Editor, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import {test} from '@self:playwright/fixtures/test';
import GravityForms from '@self:playwright/utils/gravityforms';
import Assertions from '@self:playwright/utils/assertions';

test.describe('[gravitypdf] Shortcode', () => {
	test('Redirect confirmation', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
		editor: Editor;
	}) => {
		const gf = new GravityForms(requestUtils, admin, page);

		// setup form and PDF
		const form = await gf.createForm('Redirect Confirmation');
		const pdfId = await gf.createPdf(
			form.id,
			'Redirect Confirmation Document'
		);

		// setup default confirmation
		await gf.navigateToFormConfirmation(form.id);
		await page.getByRole('radio', { name: 'Redirect' }).check();
		await page
			.getByRole('textbox', { name: 'Redirect URL' })
			.fill('[gravitypdf id="' + pdfId + '"]');
		await gf.saveForm();

		// preview and submit form
		await gf.navigateToFormPreview(form.id);
		const assertions = new Assertions(page);
		await assertions.downloadAndVerifyPdf(
			await page.getByRole('button', { name: /(save|submit)/i }),
			'Redirect Confirmation Document.pdf'
		);
	});
});
