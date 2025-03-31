import {expect, test} from '@wordpress/e2e-test-utils-playwright';
import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import GravityForms from '../../utils/gravityforms';

test.describe('[gravitypdf] Shortcode', () => {
    let gf = null;
    let form = null;
    test.beforeEach(
      async ({
        requestUtils,
        page,
        admin,
      }: {
        requestUtils: RequestUtils;
        page: Page;
        admin: Admin;
      }) => {
          // setup form and inactive PDF
        gf = new GravityForms(requestUtils, admin, page);
        form = await gf.createForm("Inactive PDF on Text Confirmation");

        const pdfId = await gf.createPdf(form.id, "Inactive PDF Document");
        await gf.navigateToFormPdfList(form.id);
        await page.getByRole("button", { name: "Active", exact: true }).click();

        // setup default confirmation
        await gf.navigateToFormConfirmation(form.id);
        await gf.setRichTextContent(
          "#gform_setting_message",
          `[gravitypdf id="${pdfId}"]`,
        );
        await gf.saveForm();
      },
    );

    test.beforeEach(
        async ({
                   requestUtils,
                   page,
                   admin,
               }: {
            requestUtils: RequestUtils;
            page: Page;
            admin: Admin;
        }) => {
            // Disable Debug Mode
            await admin.visitAdminPage('admin.php', 'page=gf_settings&subview=PDF');
            await page.getByRole('checkbox', { name: 'Debug Mode' }).uncheck();
            await page.getByRole('button', { name: 'Save Settings' }).click()
        }
    );

    test('Disabled PDF, Debug Mode Off', async ({
                                         requestUtils,
                                         page,
                                         admin,
                                     }: {
        requestUtils: RequestUtils;
        page: Page;
        admin: Admin;
    }) => {
        // preview and submit form
        await gf.navigateToFormPreview(form.id);
        await gf.saveForm();

        // verify the results
        await expect(page.getByRole('link', { name: 'Download PDF' })).not.toBeAttached()
    });

    test('Disabled PDF, Debug Mode On', async ({
                                                    requestUtils,
                                                    page,
                                                    admin,
                                                }: {
        requestUtils: RequestUtils;
        page: Page;
        admin: Admin;
    }) => {
        await page.getByRole('checkbox', { name: 'Debug Mode' }).check();
        await page.getByRole('button', { name: 'Save Settings' }).click()

        // preview and submit form
        await gf.navigateToFormPreview(form.id);
        await gf.saveForm();

        // verify the results
        await expect(page.getByRole('link', { name: 'Download PDF' })).not.toBeAttached()
        await expect(page.getByText('PDF link not displayed')).toContainText('Admin Only Message')
    });
});
