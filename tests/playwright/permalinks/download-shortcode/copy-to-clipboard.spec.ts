import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { takeSnapshot } from '@chromatic-com/playwright';

test.describe('[gravitypdf] Shortcode', () => {
	test('Copy to Clipboard', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}, testinfo) => {
		const pdfLabel = 'PDF Clipboard';

		const pdf = new Pdf(requestUtils, admin, page);
		const form = await pdf.createForm('Copy to Clipboard');
		const pdfId = await pdf.createPdf(form.id, pdfLabel);
		await pdf.copyDownloadShortcodeToClipboard(form.id, pdfId!);

		await takeSnapshot(page, testinfo);

		// Add a new PDF and paste into the Label
		await pdf.navigateToNewFormPdf(form.id);

		const label = page.getByLabel('Label');
		await label.press('ControlOrMeta+v');
		await expect(label).toHaveValue(
			`[gravitypdf name="${pdfLabel}" id="${pdfId}" text="Download PDF"]`
		);
	});
});
