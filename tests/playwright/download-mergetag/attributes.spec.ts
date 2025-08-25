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
        Download: {Mergetag:pdf:${pdfId}:download}
        Print: {Mergetag:pdf:${pdfId}:print}
        Signed: {Mergetag:pdf:${pdfId}:signed}
        Signed 5: {Mergetag:pdf:${pdfId}:signed, 5 minutes}
        Print Download: {Mergetag:pdf:${pdfId}:download:print}
        Print Signed: {Mergetag:pdf:${pdfId}:signed:print}
        Download Signed: {Mergetag:pdf:${pdfId}:download:signed}
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
        `Download: http:\/\/(.+?)\/pdf\/${pdfId}\/([0-9]+)\/download\/`
      )
    );
    await expect(confirmation).toContainText(
      new RegExp(
        `Print: http:\/\/(.+?)\/pdf\/${pdfId}\/([0-9]+)\/\\?print=1`
      )
    );
    await expect(confirmation).toContainText(
      new RegExp(
        `Signed: http:\/\/(.+?)\/pdf\/${pdfId}\/([0-9]+)\/\\?expires=([0-9]+)&signature=(.+?)`
      )
    );
    await expect(confirmation).toContainText(
      new RegExp(
        `Signed 5: http:\/\/(.+?)\/pdf\/${pdfId}\/([0-9]+)\/\\?expires=([0-9]+)&signature=(.+?)`
      )
    );
    await expect(confirmation).toContainText(
      new RegExp(
        `Print Download: http:\/\/(.+?)\/pdf\/${pdfId}\/([0-9]+)\/download\/\\?print=1`
      )
    );
    await expect(confirmation).toContainText(
      new RegExp(
        `Print Signed: http:\/\/(.+?)\/pdf\/${pdfId}\/([0-9]+)\/\\?print=1&expires=([0-9]+)&signature=(.+?)`
      )
    );
    await expect(confirmation).toContainText(
      new RegExp(
        `Download Signed: http:\/\/(.+?)\/pdf\/${pdfId}\/([0-9]+)\/download\/\\?expires=([0-9]+)&signature=(.+?)`
      )
    );
  });
});
