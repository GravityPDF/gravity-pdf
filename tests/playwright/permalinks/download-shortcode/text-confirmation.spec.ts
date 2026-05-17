import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

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
		const pdf = new Pdf(requestUtils, admin, page);

		// setup form and PDF
		const form = await pdf.createForm('Text Confirmation');
		const pdfId = await pdf.createPdf(
			form.id,
			'Text Confirmation Document'
		);

		// setup default confirmation
		await pdf.navigateToFormConfirmation(form.id);
		await pdf.setRichTextContent(
			'#gform_setting_message',
			`[gravitypdf id="${pdfId}"]`
		);
		await pdf.submitForm();

		// preview and submit form
		await pdf.navigateToFormPreview(form.id);
		await pdf.submitForm();

		// verify the results
		const pdfLink = page.getByRole('link', { name: 'Download PDF' });

		await pdf.downloadAndVerifyPdf(
			pdfLink,
			'Text Confirmation Document.pdf'
		);
	});
});
