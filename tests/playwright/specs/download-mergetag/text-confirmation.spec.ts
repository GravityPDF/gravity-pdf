import { test } from '@wordpress/e2e-test-utils-playwright';
import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import GravityForms from '../../utils/gravityforms';
import Assertions from '../../utils/assertions';

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
		const gf = new GravityForms(requestUtils, admin, page);

		// setup form and PDF
		const form = await gf.createForm('Text Confirmation Mergetag');
		await gf.navigateToFormPreview(form.id);
		await gf.saveForm();
		await gf.createPdf(
			form.id,
			'Text Confirmation Mergetag Document'
		);

		// setup default confirmation
		await gf.navigateToFormConfirmation(form.id);

		// Clear confirmation message and use the mergetag selector
		await gf.setRichTextContent('#gform_setting_message', '<a href="');
		await page.getByRole('button', { name: '' }).click(); // mergetag selector icon
		await page.getByRole('button', { name: 'Mergetag Document' }).click();
		await gf.setRichTextContent(
			'#gform_setting_message',
			'">View PDF</a>',
			true
		);
		await gf.saveForm();

		// preview and submit form
		await gf.navigateToFormPreview(form.id);
		await gf.saveForm();

		// verify the results
		const pdfLink = await page.getByRole('link', { name: 'View PDF' });

		const assertions = new Assertions(page);
		await assertions.downloadAndVerifyPdf(
			pdfLink,
			'Text Confirmation Mergetag Document.pdf'
		);
	});
});
