import { readFile } from "fs/promises";
import { Admin, RequestUtils } from "@wordpress/e2e-test-utils-playwright";
import type { Page } from "@playwright/test";

type Form = {
  id: number;
  title: string;
};

export default class GravityForms {
  private requestUtils: RequestUtils;
  private admin: Admin;
  private page: Page;

  constructor(requestUtils: RequestUtils, admin: Admin, page: Page) {
    this.requestUtils = requestUtils;
    this.admin = admin;
    this.page = page;
  }

  async createForm(title: string) {
    const formJson: string = await readFile(
      __dirname + "/../data/forms/standard.json",
      "utf8",
    );

    const form: Form = JSON.parse(formJson);

    form.title = title;

    return await this.requestUtils.rest({
      method: "POST",
      path: `/gf/v2/forms`,
      data: { ...form },
    });
  }

  async createPdf(formId: number, label: string) {
    await this.navigateToFormPdfList(formId);
    await this.page.getByRole("link", { name: "Add new PDF" }).click();
    await this.page.getByLabel("Label").fill(label)
    await this.page.getByLabel("Filename").fill(label)
    await this.page.getByRole("button", { name: "Add PDF" }).first().click();
  }

  async navigateToFormList() {
    await this.admin.visitAdminPage("admin.php", "page=gf_edit_forms");
  }

  async getFormIdByTitle(title: string): Promise<number> {
    await this.navigateToFormList();
    const search = this.page.getByRole("searchbox");
    await search.fill(title);
    await search.press("Enter");

    return Number.parseInt(
      await this.page.locator(".id > a").first().innerText(),
    );
  }

  async navigateToFormByTitle(title: string) {
    const id = await this.getFormIdByTitle(title);
    await this.navigateToFormById(id);
  }

  async navigateToFormById(id: number) {
    await this.admin.visitAdminPage("admin.php", "page=gf_edit_forms&id=" + id);
  }

  async navigateToFormSettingsById(id: number, subview = "") {
    await this.admin.visitAdminPage(
      "admin.php",
      `page=gf_edit_forms&view=settings&subview=settings&id=${id}&subview=${subview}`,
    );
  }

  async navigateToFormPdfList(id: number) {
    await this.navigateToFormSettingsById(id, 'PDF')
  }
}
