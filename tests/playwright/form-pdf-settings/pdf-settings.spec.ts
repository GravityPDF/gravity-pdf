import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('Form PDF Settings', () => {
	let pdf = null;
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
			pdf = new Pdf(requestUtils, admin, page);
			form = await pdf.createForm('Form PDF Settings');

			// Reset TinyMCE to default to the Visual tab
			await pdf.navigateToNewFormPdf(form.id);
			for (const button of await page
				.getByRole('button', { name: /^Visual$/ })
				.all()) {
				await button.click();
			}
		}
	);

	test('Add New PDF', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await expect(page.locator('#tab_PDF')).toHaveScreenshot({});
	});

	test('Update Existing PDF', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const pdfId = await pdf.createPdf(form.id, 'Existing PDF');
		await pdf.navigateToFormPdf(form.id, pdfId);

		await expect(page.locator('#tab_PDF')).toHaveScreenshot();
	});

	test('Notifications', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const elements = page
			.locator('#gfpdf-settings-field-wrapper-notification')
			.getByRole('checkbox');
		await expect(elements).toHaveCount(1);

		// Add another Notification
		await pdf.addNotification(form.id, 'User Notification');

		await pdf.createPdf(form.id, 'PDF Notification');
		await expect(elements).toHaveCount(2);

		await page.getByRole('checkbox', { name: 'User Notification' }).click();
		await pdf.addOrUpdatePdf();

		await expect(
			page.getByRole('checkbox', { name: 'Admin Notification' })
		).not.toBeChecked();
		await expect(
			page.getByRole('checkbox', { name: 'User Notification' })
		).toBeChecked();
	});

	test('Filename', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.createPdf(form.id, 'Filename');

		await page.getByRole('textbox', { name: 'Filename' }).click();
		await page.getByTitle('Insert Merge Tags').nth(0).click();
		await page
			.getByRole('textbox', { name: 'Search Merge Tags' })
			.fill('date');
		await page.getByRole('button', { name: 'Date (dd/mm/yyyy)' }).click();
		await page.getByRole('textbox', { name: 'Filename' }).click();
		await page
			.getByRole('textbox', { name: 'Filename' })
			.pressSequentially('-{entry_id}');
		await pdf.addOrUpdatePdf();

		await expect(
			page.locator('#gfpdf-settings-field-wrapper-filename')
		).toHaveScreenshot();
	});

	test('Conditional Logic', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.createPdf(form.id, 'Conditional Logic');

		await page
			.getByRole('checkbox', {
				name: 'Enable conditional logic',
				exact: true,
			})
			.check();
		await page.locator('#gfpdf_action_type').selectOption('hide');
		await page.locator('#gfpdf_logic_type').selectOption('any');
		await page.locator('#gfpdf_rule_value_0').selectOption('Second Choice');
		await page.getByTitle('add another rule').first().click();
		await page.locator('#gfpdf_rule_field_1').selectOption('status');
		await page.locator('#gfpdf_rule_value_1').selectOption('spam');
		await pdf.addOrUpdatePdf();

		await expect(
			page.locator('#gfpdf-settings-field-wrapper-conditional')
		).toHaveScreenshot();
	});

	test('Paper', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.createPdf(form.id, 'Paper');

		await page.getByLabel('Paper Size').selectOption('LETTER');
		await page.getByLabel('Paper Size').selectOption('A4');
		await page.getByLabel('Paper Size').selectOption('B3');
		await page.getByLabel('Paper Size').selectOption('RA1');
		await page.getByLabel('Paper Size').selectOption('CUSTOM');
		await page
			.locator('#gfpdf_settings\\[custom_pdf_size\\]_measurement')
			.selectOption('inches');
		await page
			.locator('#gfpdf_settings\\[custom_pdf_size\\]_width')
			.fill('5');
		await page
			.locator('#gfpdf_settings\\[custom_pdf_size\\]_height')
			.fill('7');
		await page.getByLabel('Paper Orientation').selectOption('landscape');

		await pdf.addOrUpdatePdf();

		await expect(
			page.locator('#gfpdf-settings-field-wrapper-pdf_size')
		).toHaveScreenshot();
		await expect(
			page.locator('#gfpdf-settings-field-wrapper-custom_pdf_size')
		).toHaveScreenshot();
		await expect(
			page.locator('#gfpdf-settings-field-wrapper-orientation')
		).toHaveScreenshot();
	});

	test('Color Picker', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.createPdf(form.id, 'Color Picker');

		await page
			.getByRole('button', { name: 'Select Color' })
			.first()
			.click();
		await page
			.locator('.iris-square-inner.iris-square-vert')
			.first()
			.click({ position: { x: 100, y: 100 } });
		await page.getByRole('textbox', { name: 'Font Color' }).click();

		await expect(
			page.getByRole('textbox', { name: 'Font Color' })
		).toHaveValue('#898989');
		await expect(
			page.locator('#gfpdf-settings-field-wrapper-font_colour')
		).toHaveScreenshot();
	});

	test('File Upload', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.createPdf(form.id, 'File Upload');

		await page.getByRole('button', { name: 'Upload File' }).click();
		await page.getByRole('tab', { name: 'Upload files' }).click();
		await page
			.locator('input[type=file]')
			.setInputFiles(
				__dirname + '/../../../tools/playwright/data/images/thumbnail.jpg'
			);
		await page.getByRole('button', { name: 'Select Media' }).click();

		await expect(
			page.getByRole('textbox', { name: 'Background Image' })
		).toHaveValue(/^http:\/\/localhost.+/);
	});

	test('Rich Text Editor', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.createPdf(form.id, 'Rich Text Editor');

		await pdf.checkRichTextEditor(
			page.locator('#gfpdf-settings-field-wrapper-header')
		);

		await page
			.getByRole('checkbox', { name: 'Use different header on first' })
			.check();
		await pdf.checkRichTextEditor(
			page.locator('#gfpdf-settings-field-wrapper-first_header')
		);
	});

	test('PDF Security', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.createPdf(form.id, 'PDF Security');

		await expect(
			page.getByRole('textbox', { name: 'Password' })
		).not.toBeVisible();
		await expect(
			page.getByRole('checkbox', { name: 'Print - High Resolution' })
		).not.toBeVisible();

		await page.getByText('Enable PDF Security').first().click();

		await expect(
			page.getByRole('textbox', { name: 'Password' })
		).toBeVisible();
		await expect(
			page.getByRole('checkbox', { name: 'Print - High Resolution' })
		).toBeVisible();

		await page.getByRole('radio', { name: 'PDF/A-1b' }).check();

		await expect(
			page.getByText('Enable PDF Security').first()
		).not.toBeVisible();
		await expect(
			page.getByRole('textbox', { name: 'Password' })
		).not.toBeVisible();
		await expect(
			page.getByRole('checkbox', { name: 'Print - High Resolution' })
		).not.toBeVisible();

		await page.getByRole('radio', { name: 'Standard' }).check();

		await expect(
			page.getByRole('textbox', { name: 'Password' })
		).toBeVisible();
		await expect(
			page.getByRole('checkbox', { name: 'Print - High Resolution' })
		).toBeVisible();

		await page
			.locator('#gfpdf-settings-field-wrapper-password')
			.getByTitle('Insert Merge Tags')
			.click();
		await page.getByRole('button', { name: 'Date (dd/mm/yyyy)' }).click();

		await expect(
			page.locator('#gfpdf-settings-field-wrapper-password')
		).toHaveScreenshot();
	});
});
