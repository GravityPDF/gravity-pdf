import type {
	Admin,
	Editor,
	RequestUtils,
} from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('[gravitypdf] Shortcode', () => {
	test('Page confirmation', async ({
		requestUtils,
		page,
		admin,
		editor,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
		editor: Editor;
	}) => {
		const pdf = new Pdf(requestUtils, admin, page);

		// setup form and PDF
		const form = await pdf.createForm('Page Confirmation');
		const pdfId = await pdf.createPdf(
			form.id,
			'Page Confirmation Document'
		);

		// Create Page with shortcode embedded
		await admin.createNewPost({
			postType: 'page',
			title: 'Gravity PDF Page Confirmation Form',
		});

		await editor.setContent(
			'<!-- wp:shortcode -->[gravitypdf id="' +
				pdfId +
				'"]<!-- /wp:shortcode -->'
		);
		await editor.publishPost();

		// setup default confirmation
		await pdf.navigateToFormConfirmation(form.id);
		await page.getByRole('radio', { name: 'Page' }).check();
		await page.getByRole('button', { name: 'Select a Page' }).click();
		await page
			.getByRole('textbox', { name: 'Search all' })
			.fill('Gravity PDF Page Confirmation Form');
		await page
			.getByRole('button', { name: 'Gravity PDF Page Confirmation Form' })
			.first()
			.click();
		await page
			.getByRole('textbox', { name: 'Data via Query' })
			.fill('entry={entry_id}');
		await pdf.saveForm();

		// preview and submit form
		await pdf.navigateToFormPreview(form.id);
		await pdf.saveForm();

		// verify the results
		const pdfLink = page.getByRole('link', { name: 'Download PDF' });

		await pdf.downloadAndVerifyPdf(
			pdfLink,
			'Page Confirmation Document.pdf'
		);
	});
});
