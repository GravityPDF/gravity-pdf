import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import GravityForms from '../../utils/gravityforms';

test.describe('Mergetag attributes', () => {
	test('Plain Permalinks', async ({
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
		const form = await gf.createForm('Mergetag Attributes');
		await gf.navigateToFormPreview(form.id);
		await gf.saveForm();
		const pdfId = await gf.createPdf(form.id, 'Mergetag');

		// setup default confirmation
		await gf.navigateToFormConfirmation(form.id);

		// Clear confirmation message and use the mergetag selector
		const content = `
        Download: {Mergetag:pdf:${pdfId}:download}
        Print: {Mergetag:pdf:${pdfId}:print}
        Signed: {Mergetag:pdf:${pdfId}:signed}
        Signed 5: {Mergetag:pdf:${pdfId}:signed, 5 minutes}
        Print Download: {Mergetag:pdf:${pdfId}:download:print}
        Print Signed: {Mergetag:pdf:${pdfId}:signed:print}
        Download Signed: {Mergetag:pdf:${pdfId}:download:signed}
        `;
		await gf.setRichTextContent('#gform_setting_message', content);
		await gf.saveForm();

		// preview and submit form
		await gf.navigateToFormPreview(form.id);
		await gf.saveForm();

		// verify the results
		const confirmation = await page.locator('#preview_form_container');
		await expect(confirmation).toContainText(
			new RegExp(
				`Download: http:\/\/(.+?)\/\\?gpdf=1&pid=${pdfId}&lid=([0-9]+)&action=download`
			)
		);
		await expect(confirmation).toContainText(
			new RegExp(
				`Print: http:\/\/(.+?)\/\\?gpdf=1&pid=${pdfId}&lid=([0-9]+)&print=1`
			)
		);
		await expect(confirmation).toContainText(
			new RegExp(
				`Signed: http:\/\/(.+?)\/\\?gpdf=1&pid=${pdfId}&lid=([0-9]+)&expires=([0-9]+)&signature=(.+?)`
			)
		);
		await expect(confirmation).toContainText(
			new RegExp(
				`Signed 5: http:\/\/(.+?)\/\\?gpdf=1&pid=${pdfId}&lid=([0-9]+)&expires=([0-9]+)&signature=(.+?)`
			)
		);
		await expect(confirmation).toContainText(
			new RegExp(
				`Print Download: http:\/\/(.+?)\/\\?gpdf=1&pid=${pdfId}&lid=([0-9]+)&action=download&print=1`
			)
		);
		await expect(confirmation).toContainText(
			new RegExp(
				`Print Signed: http:\/\/(.+?)\/\\?gpdf=1&pid=${pdfId}&lid=([0-9]+)&print=1&expires=([0-9]+)&signature=(.+?)`
			)
		);
		await expect(confirmation).toContainText(
			new RegExp(
				`Download Signed: http:\/\/(.+?)\/\\?gpdf=1&pid=${pdfId}&lid=([0-9]+)&action=download&expires=([0-9]+)&signature=(.+?)`
			)
		);
	});
});
