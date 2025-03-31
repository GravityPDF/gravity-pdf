import { test } from '@wordpress/e2e-test-utils-playwright';
import GravityForms from '../../utils/gravityforms';
import Assertions from '../../utils/assertions';

import type {
	Admin,
	Editor,
	RequestUtils,
} from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';

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
		const gf = new GravityForms(requestUtils, admin, page);

		// setup form and PDF
		const form = await gf.createForm('Page Confirmation');
		const pdfId = await gf.createPdf(form.id, 'Page Confirmation Document');

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
		await gf.navigateToFormConfirmation(form.id);
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
		await gf.saveForm();

		// preview and submit form
		await gf.navigateToFormPreview(form.id);
		await gf.saveForm();

		// verify the results
		const pdfLink = await page.getByRole('link', { name: 'Download PDF' });

		const assertions = new Assertions(page);
		await assertions.downloadAndVerifyPdf(
			pdfLink,
			'Page Confirmation Document.pdf'
		);
	});
});
