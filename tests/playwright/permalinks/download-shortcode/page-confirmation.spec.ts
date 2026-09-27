import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { withConfirmation } from '@self:playwright/utils/gravityforms';

test.describe('[gravitypdf] Shortcode', () => {
	test('Page confirmation', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const pdf = new Pdf(requestUtils, admin, page);

		// setup PDF, the page with it embedded, and a default confirmation that sends the entry there
		const form = await pdf.createForm('Page Confirmation');
		const pdfId = await pdf.addPdf(form.id, 'Page Confirmation Document');
		const confirmationPage = await requestUtils.createPage({
			title: 'Gravity PDF Page Confirmation Form',
			content: `<!-- wp:shortcode -->[gravitypdf id="${pdfId}"]<!-- /wp:shortcode -->`,
			status: 'publish',
		});
		await pdf.updateForm(
			form.id,
			withConfirmation({
				type: 'page',
				pageId: confirmationPage.id,
				queryString: 'entry={entry_id}',
			})
		);

		// preview and submit form
		await pdf.navigateToFormPreview(form.id);
		await pdf.submitForm();

		// verify the results
		const pdfLink = page.getByRole('link', { name: 'Download PDF' });

		await pdf.downloadAndVerifyPdf(
			pdfLink,
			'Page Confirmation Document.pdf'
		);
	});
});
