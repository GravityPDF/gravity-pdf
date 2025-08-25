import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import {test} from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('{Label:pdf:[id]} Merge Tag', () => {
	test('Text Confirmation Mergetag Selector', async ({
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
		const form = await pdf.createForm('Text Confirmation Mergetag');
		await pdf.navigateToFormPreview(form.id);
		await pdf.saveForm();
		await pdf.createPdf(
			form.id,
			'Text Confirmation Mergetag Document'
		);

		// setup default confirmation
		await pdf.navigateToFormConfirmation(form.id);

		// Clear confirmation message and use the mergetag selector
		await pdf.setRichTextContent('#gform_setting_message', '<a href="');
		await page.getByRole('button', { name: '' }).click(); // mergetag selector icon
		await page.getByRole('button', { name: 'Mergetag Document' }).click();
		await pdf.setRichTextContent(
			'#gform_setting_message',
			'">View PDF</a>',
			true
		);
		await pdf.saveForm();

		// preview and submit form
		await pdf.navigateToFormPreview(form.id);
		await pdf.saveForm();

		// verify the results
		const pdfLink = await page.getByRole('link', { name: 'View PDF' });
    
		await pdf.downloadAndVerifyPdf(
			pdfLink,
			'Text Confirmation Mergetag Document.pdf'
		);
	});
});
