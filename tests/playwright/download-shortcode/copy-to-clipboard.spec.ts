import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import {expect} from '@wordpress/e2e-test-utils-playwright';
import {test} from '@self:playwright/fixtures/test';
import GravityForms from '@self:playwright/utils/gravityforms';

test.describe('[gravitypdf] Shortcode', () => {
	test('Copy to Clipboard', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const pdfLabel = 'PDF Clipboard';

		const gf = new GravityForms(requestUtils, admin, page);
		const form = await gf.createForm('Copy to Clipboard');
		const pdfId = await gf.createPdf(form.id, pdfLabel);
		await gf.copyDownloadShortcodeToClipboard(form.id, pdfId);

		// Add a new PDF and paste into the Label
		await gf.navigateToNewFormPdf(form.id);

		const label = page.getByLabel('Label');
		await label.press('ControlOrMeta+v');
		await expect(label).toHaveValue(
			`[gravitypdf name="${pdfLabel}" id="${pdfId}" text="Download PDF"]`
		);
	});
});
