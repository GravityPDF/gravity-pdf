const { test, expect } = require('@wordpress/e2e-test-utils-playwright');
import GravityForms from '../../utils/gravityforms';

test.describe('[gravitypdf] Shortcode', () => {
	test('Copy to Clipboard', async ({ requestUtils, page, admin }) => {
		const pdfLabel = 'PDF Clipboard';

		const gf = new GravityForms(requestUtils, admin, page);
		const form = await gf.createForm('Copy to Clipboard');
		const pdfId = await gf.createPdf(form.id, pdfLabel);
		await gf.copyDownloadShortcodeToClipboard(form.id, pdfId);

		// Add a new PDF and paste into the Label
		await gf.navigateToNewFormPdf(form.id);

		const label = page.getByLabel('Label');
		await label.press('ControlOrMeta+v');
		expect(label).toHaveValue(
			`[gravitypdf name="${pdfLabel}" id="${pdfId}" text="Download PDF"]`
		);
	});
});
