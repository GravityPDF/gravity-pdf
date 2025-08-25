import { expect } from "@wordpress/e2e-test-utils-playwright";
import type { Locator } from "@playwright/test";
import type { Readable } from "stream";
import { URL } from "node:url";
import GravityForms from "@self:playwright/utils/gravityforms";

export default class Pdf extends GravityForms{
  async navigateToPdfSettings() {
    await this.admin.visitAdminPage('admin.php', 'page=gf_settings&subview=PDF');
  }

  async setPdfSetting(label:string, value: any) {
    await this.navigateToPdfSettings();

    const setting = this.page.getByLabel(label).first()
    console.log(setting.getAttribute('type'))

    switch(await setting.getAttribute('type')) {
      case 'checkbox':
          value ? await setting.check() : await setting.uncheck();
          break;

      case 'radio':
        value ? await setting.check() : await setting.uncheck();
    }

    await this.page.getByRole('button', { name: 'Save Settings' }).click()
  }

  async navigateToFormPdfList(formId: number) {
    await this.navigateToFormSettingsById(formId, 'PDF');
  }

  async createPdf(formId: number, label: string) {
    await this.navigateToNewFormPdf(formId);
    await this.page.getByLabel('Label').fill(label);
    await this.page.getByLabel('Filename').fill(label);
    await this.page
      .getByRole('button', { name: 'Add PDF' })
      .first()
      .click();

    // return PDF ID (@TODO update with the REST API settings once implemented)
    const pdfUrl = new URL(this.page.url());

    return pdfUrl.searchParams.get('pid');
  }

  async navigateToNewFormPdf(formId: number) {
    await this.navigateToFormPdfList(formId);
    await this.page.getByRole('link', { name: 'Add new PDF' }).click();
  }

  async copyDownloadShortcodeToClipboard(formId: number, pdfId: string) {
    await this.navigateToFormPdfList(formId);
    await this.page.locator(`#gfpdf-${pdfId}`).getByRole('dialog').click();
  }

  async downloadAndVerifyPdf(pdfLink: Locator, expectedFilename: string) {
    await expect(pdfLink).toBeAttached();

    const downloadPromise = this.page.waitForEvent('download');
    await pdfLink.click();
    const download = await downloadPromise;

    // download the PDF and verify it's valid
    expect(download.suggestedFilename()).toBe(expectedFilename);
    const pdfStream = await download.createReadStream();

    async function readPdf(readable: Readable) {
      let data = '';
      for await (const chunk of readable) {
        data += chunk;
      }

      return data;
    }

    const pdfContent = await readPdf(pdfStream);
    expect(pdfContent.substring(0, 7)).toEqual(
      expect.stringContaining('%PDF-1.')
    );
    expect(pdfContent).toEqual(expect.stringContaining('startxref'));
    expect(pdfContent).toEqual(expect.stringContaining('%%EOF'));
  }
}
