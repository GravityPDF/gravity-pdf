import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('Mergetag attributes', () => {
  let pdf = null;
  let form = null;
  let pdfId = null;

  test.beforeEach(async ({
                           requestUtils,
                           page,
                           admin,
                         }: {
    requestUtils: RequestUtils;
    page: Page;
    admin: Admin;
  }) => {
    pdf = new Pdf(requestUtils, admin, page);

    // setup form and PDF
    form = await pdf.createForm('Mergetag Attributes');
    await pdf.navigateToFormPreview(form.id);
    await pdf.saveForm();
    pdfId = await pdf.createPdf(form.id, 'Mergetag');

    // setup default confirmation
    await pdf.navigateToFormConfirmation(form.id);

    // Clear confirmation message and use the mergetag selector
    const content = `
        PDF URL: {Mergetag:pdf:${pdfId}}
        `;
    await pdf.setRichTextContent('#gform_setting_message', content);
    await pdf.saveForm();
  })

  test.afterEach(async({
                         requestUtils,
                         page,
                         admin,
                       }: {
    requestUtils: RequestUtils;
    page: Page;
    admin: Admin;
  }) => {
    await admin.visitAdminPage('options-permalink.php');
    await page.getByRole('radio', { name: 'Plain' }).click();
    await page.getByRole('button', { name: 'Save Changes' }).click();
  })

	test('Plain Permalinks', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		// preview and submit form
		await pdf.navigateToFormPreview(form.id);
		await pdf.saveForm();

		// verify the results
		const confirmation = await page.locator('#preview_form_container');
		await expect(confirmation).toContainText(
			new RegExp(
				`PDF URL: http:\/\/(.+?)\/\\?gpdf=1&pid=${pdfId}&lid=([0-9]+)`
			)
		);
	});

  test('Pretty Permalinks', async ({
                                    requestUtils,
                                    page,
                                    admin,
                                  }: {
    requestUtils: RequestUtils;
    page: Page;
    admin: Admin;
  }) => {
    await admin.visitAdminPage('options-permalink.php')
    await page.getByRole('radio', { name: 'Post name' }).click()
    await page.getByRole('button', { name: 'Save Changes' }).click()

    // preview and submit form
    await pdf.navigateToFormPreview(form.id);
    await pdf.saveForm();

    // verify the results
    const confirmation = await page.locator('#preview_form_container');
    await expect(confirmation).toContainText(
      new RegExp(
        `PDF URL: http:\/\/(.+?)\/pdf\/${pdfId}\/([0-9]+)\/`
      )
    );
  });
});
