import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import {test} from '@self:playwright/fixtures/test';
import GravityForms from '@self:playwright/utils/gravityforms';
import Assertions from '@self:playwright/utils/assertions';

test.describe('[gravitypdf] Shortcode', () => {
	test('Text confirmation', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const gf = new GravityForms(requestUtils, admin, page);

		// setup form and PDF
		const form = await gf.createForm('Text Confirmation');
		const pdfId = await gf.createPdf(form.id, 'Text Confirmation Document');

		// setup default confirmation
		await gf.navigateToFormConfirmation(form.id);
		await gf.setRichTextContent(
			'#gform_setting_message',
			`[gravitypdf id="${pdfId}"]`
		);
		await gf.saveForm();

		// preview and submit form
		await gf.navigateToFormPreview(form.id);
		await gf.saveForm();

		// verify the results
		const pdfLink = page.getByRole('link', { name: 'Download PDF' });

		const assertions = new Assertions(page);
		await assertions.downloadAndVerifyPdf(
			pdfLink,
			'Text Confirmation Document.pdf'
		);
	});
});
