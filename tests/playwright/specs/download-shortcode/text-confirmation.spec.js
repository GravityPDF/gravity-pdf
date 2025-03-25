import Assertions from '../../utils/assertions'

const { test } = require('@wordpress/e2e-test-utils-playwright');
import GravityForms from '../../utils/gravityforms';

test.describe('[gravitypdf] Shortcode', () => {
	test('Text confirmation', async ({ requestUtils, page, admin }) => {
		const gf = new GravityForms(requestUtils, admin, page);

		// setup form and PDF
		const form = await gf.createForm('Text Confirmation');
		await gf.navigateToFormPreview(form.id);
		await gf.saveForm();
		const pdfId = await gf.createPdf(form.id, 'Text Confirmation Document');

		// setup default confirmation
		await gf.navigateToFormConfirmation(form.id);
		await gf.setRichTextContent(
			'#gform_setting_message',
			`[gravitypdf id="${pdfId}"]`
		);
		await gf.saveForm();

		// preview and submit form
		await gf.navigateToFormPreview(form.id);
		await gf.saveForm();

		// verify the results
		const pdfLink = await page.getByRole('link', { name: 'Download PDF' });

		const assertions = new Assertions(page)
		await assertions.downloadAndVerifyPdf(
			pdfLink,
			'Text Confirmation Document.pdf'
		);
	});
});
