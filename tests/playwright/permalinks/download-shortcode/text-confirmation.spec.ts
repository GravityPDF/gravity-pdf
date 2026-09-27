import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { withConfirmation } from '@self:playwright/utils/gravityforms';

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

		// setup form, PDF and default confirmation
		const form = await pdf.createForm('Text Confirmation');
		const pdfId = await pdf.addPdf(form.id, 'Text Confirmation Document');
		await pdf.updateForm(
			form.id,
			withConfirmation({ message: `[gravitypdf id="${pdfId}"]` })
		);

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
