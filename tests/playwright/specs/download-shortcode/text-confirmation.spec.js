const { test, expect } = require("@wordpress/e2e-test-utils-playwright");
import GravityForms from "../../utils/gravityforms";

test.describe("Text Confirmation", () => {
  test("[gravitypdf] shortcode renders PDF link", async ({
    requestUtils,
    page,
    admin,
    editor
  }) => {
    const gf = new GravityForms(requestUtils, admin, page);

    const form = await gf.createForm("Text Confirmation");
    await gf.createPdf(form.id, "Text Confirmation 1");
    await gf.navigateToFormPdfList(form.id);

    // copy shortcode to clipboard
    await page
      .getByRole("dialog", { name: "Copy the Text Confirmation 1" })
      .click();

    // paste shortcode to main confirmation message and save
    await gf.navigateToFormSettingsById(form.id, 'confirmation')
    await page.getByRole('link', { name: "Default Confirmation (Edit)" }).click();
    await page.getByRole('button', { name: 'Code'}).first().click()
    const textarea = await page.locator('#_gform_setting_message')
    await textarea.press('ControlOrMeta+a')
    await textarea.press('Backspace')
    await textarea.press('ControlOrMeta+v')
    await page.getByRole('button', { name: 'save'}).click()

    // embed form on page (abstract)
    await admin.createNewPost({
      postType: "page",
      title: "Gravity PDF Text Confirmation Form",
      content: `[gravityform id="${form.id}" ajax="true"]` // TODO <- add to shortcode block
    })

    const postId = await editor.publishPost();
    await page.goto(`/?page_id=${postId}`);

    // Submit the form
  });
});
