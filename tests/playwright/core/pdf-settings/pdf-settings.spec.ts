import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test, resourcesPath } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import * as path from 'node:path';
import { takeSnapshot } from '@chromatic-com/playwright';

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

	test.describe('General', () => {
		test('Label', async () => {
			await pdf.fillField('Label', 'Test Label');
			await pdf.addOrUpdatePdf();
			await expect(pdf.page.getByLabel('Label')).toHaveValue(
				'Test Label'
			);
		});

		test('Filename', async ({}, testinfo) => {
			await pdf.createPdf(form.id, 'Filename');

			await pdf.page.getByRole('textbox', { name: 'Filename' }).click();
			await pdf.page.getByTitle('Insert Merge Tags').nth(0).click();
			await pdf.page
				.getByRole('textbox', { name: 'Search Merge Tags' })
				.fill('date');
			await pdf.page
				.getByRole('button', { name: 'Date (dd/mm/yyyy)' })
				.click();
			await pdf.page.getByRole('textbox', { name: 'Filename' }).click();
			await pdf.page
				.getByRole('textbox', { name: 'Filename' })
				.pressSequentially('-{entry_id}');
			await pdf.addOrUpdatePdf();

			await takeSnapshot(pdf.page, testinfo);
		});

		test('Notifications', async () => {
			const elements = pdf.page
				.locator('#gfpdf-settings-field-wrapper-notification')
				.getByRole('checkbox');

			await expect(elements).toHaveCount(1);

			// Add another Notification
			await pdf.addNotification(form.id, 'User Notification');

			await pdf.createPdf(form.id, 'PDF Notification');
			await expect(elements).toHaveCount(2);

			await pdf.page
				.getByRole('checkbox', { name: 'User Notification' })
				.click();
			await pdf.addOrUpdatePdf();

			await expect(
				pdf.page.getByRole('checkbox', { name: 'Admin Notification' })
			).not.toBeChecked();
			await expect(
				pdf.page.getByRole('checkbox', { name: 'User Notification' })
			).toBeChecked();
		});

		test('Conditional Logic', async ({}, testinfo) => {
			await pdf.createPdf(form.id, 'Conditional Logic');

			await pdf.page
				.getByRole('checkbox', {
					name: 'Enable conditional logic',
					exact: true,
				})
				.check();

			await pdf.page.locator('#gfpdf_action_type').selectOption('hide');
			await pdf.page.locator('#gfpdf_logic_type').selectOption('any');
			await pdf.page
				.locator('#gfpdf_rule_value_0')
				.selectOption('Second Choice');

			await pdf.page.getByTitle('add another rule').first().click();
			await pdf.page
				.locator('#gfpdf_rule_field_1')
				.selectOption('status');

			await pdf.page.locator('#gfpdf_rule_value_1').selectOption('spam');
			await pdf.addOrUpdatePdf();

			await takeSnapshot(pdf.page, testinfo);

			// Entry 1: Radio = "Second Choice" → PDF hidden by conditional logic
			const entry1 = await pdf.createEntry({
				form_id: form.id,
				1: 'Second Choice',
			});

			await pdf.navigateToEntryList(form.id);
			await expect(
				pdf.page.getByRole('link', { name: 'View PDF' })
			).not.toBeAttached();

			await pdf.navigateToEntryDetail(entry1.form_id, entry1.id);
			await expect(
				pdf.page.getByRole('link', { name: 'View', exact: true })
			).not.toBeAttached();
			await expect(
				pdf.page.getByRole('link', { name: 'Download', exact: true })
			).not.toBeAttached();

			// Entry 2: Radio = "First Choice" → PDF passes conditional logic
			const entry2 = await pdf.createEntry({
				form_id: form.id,
				1: 'First Choice',
			});

			await pdf.navigateToEntryList(form.id);
			await expect(
				pdf.page.getByRole('link', { name: 'View PDF' })
			).toBeAttached();

			await pdf.navigateToEntryDetail(entry2.form_id, entry2.id);
			await expect(
				pdf.page.getByRole('link', { name: 'View', exact: true })
			).toBeAttached();
			await expect(
				pdf.page.getByRole('link', { name: 'Download', exact: true })
			).toBeAttached();
		});
	});

	test.describe('Appearance', () => {
		test('Paper', async ({}, testinfo) => {
			await pdf.createPdf(form.id, 'Paper');

			await pdf.selectField('Paper Size', 'CUSTOM');

			await pdf.page
				.locator('#gfpdf_settings\\[custom_pdf_size\\]_measurement')
				.selectOption('inches');

			await pdf.page
				.locator('#gfpdf_settings\\[custom_pdf_size\\]_width')
				.fill('5');

			await pdf.page
				.locator('#gfpdf_settings\\[custom_pdf_size\\]_height')
				.fill('7');
			await pdf.selectField('Paper Orientation', 'landscape');

			await pdf.addOrUpdatePdf();

			await pdf.page.waitForTimeout(500);

			await takeSnapshot(pdf.page, testinfo);
		});

		test('Color Picker', async ({}, testinfo) => {
			await pdf.createPdf(form.id, 'Color Picker');

			await pdf.page
				.getByRole('button', { name: 'Select Color' })
				.first()
				.click();

			await pdf.page
				.locator('.iris-square-inner.iris-square-vert')
				.first()
				.click({ position: { x: 100, y: 100 } });
			await pdf.page.getByRole('textbox', { name: 'Font Color' }).click();

			await expect(
				pdf.page.getByRole('textbox', { name: 'Font Color' })
			).toHaveValue('#898989');

			await takeSnapshot(pdf.page, testinfo);
		});

		test('Reverse Text RTL', async () => {
			await pdf.checkField('Reverse Text (RTL)', true);
			await pdf.addOrUpdatePdf();

			await expect(
				pdf.page.getByLabel('Reverse Text (RTL)')
			).toBeChecked();
		});

		test('Background Image / File Upload', async () => {
			await pdf.createPdf(form.id, 'File Upload');

			await pdf.page.getByRole('button', { name: 'Upload File' }).click();
			await pdf.page.getByRole('tab', { name: 'Upload files' }).click();
			await pdf.page
				.locator('input[type=file]')
				.setInputFiles(
					path.join(resourcesPath, 'images', 'thumbnail.jpg')
				);

			await pdf.page
				.getByRole('button', { name: 'Select Media' })
				.click();

			await expect(
				pdf.page.getByRole('textbox', { name: 'Background Image' })
			).toHaveValue(/^http:\/\/localhost.+/);
		});
	});

	test.describe('Template', () => {
		test('Rich Text Editor / Header / Footer', async ({}, testinfo) => {
			await pdf.createPdf(form.id, 'Rich Text Editor');

			await pdf.checkRichTextEditor(
				pdf.page.locator('#gfpdf-settings-field-wrapper-header')
			);

			await pdf.page
				.getByRole('checkbox', {
					name: 'Use different header on first',
				})
				.check();

			await pdf.checkRichTextEditor(
				pdf.page.locator('#gfpdf-settings-field-wrapper-first_header')
			);

			await takeSnapshot(pdf.page, testinfo);
		});

		test('Show Empty Fields', async () => {
			await pdf.checkField('Show Empty Fields', true);
			await pdf.addOrUpdatePdf();
			await expect(
				pdf.page.getByLabel('Show Empty Fields')
			).toBeChecked();
		});

		test('Show Form Title', async () => {
			await pdf.checkField('Show Form Title', true);
			await pdf.addOrUpdatePdf();
			await expect(pdf.page.getByLabel('Show Form Title')).toBeChecked();
		});

		test('Show Page Names', async () => {
			await pdf.checkField('Show Page Names', true);
			await pdf.addOrUpdatePdf();
			await expect(pdf.page.getByLabel('Show Page Names')).toBeChecked();
		});

		test('Show HTML Fields', async () => {
			await pdf.checkField('Show HTML Fields', true);
			await pdf.addOrUpdatePdf();
			await expect(pdf.page.getByLabel('Show HTML Fields')).toBeChecked();
		});

		test('Show Section Break Description', async () => {
			await pdf.checkField('Show Section Break Description', true);
			await pdf.addOrUpdatePdf();
			await expect(
				pdf.page.getByLabel('Show Section Break Description')
			).toBeChecked();
		});

		test('Background Color', async () => {
			// Set the color via JavaScript since the wp-color-picker input
			// is enhanced and not directly fillable
			await pdf.page.evaluate(() => {
				const input = document.querySelector(
					'#gfpdf_settings\\[background_color\\]'
				) as HTMLInputElement;
				if (input) {
					input.value = '#1e73be';
					input.dispatchEvent(new Event('change', { bubbles: true }));
				}
			});

			await pdf.addOrUpdatePdf();

			await expect(
				pdf.page.locator('#gfpdf_settings\\[background_color\\]')
			).toHaveValue('#1e73be');
		});
	});

	test.describe('Advanced', () => {
		test('PDF Security', async ({}, testinfo) => {
			await pdf.createPdf(form.id, 'PDF Security');

			await expect(
				pdf.page.getByRole('textbox', { name: 'Password' })
			).not.toBeVisible();

			await expect(
				pdf.page.getByRole('checkbox', {
					name: 'Print - High Resolution',
				})
			).not.toBeVisible();

			await pdf.page.getByText('Enable PDF Security').first().click();

			await expect(
				pdf.page.getByRole('textbox', { name: 'Password' })
			).toBeVisible();

			await expect(
				pdf.page.getByRole('checkbox', {
					name: 'Print - High Resolution',
				})
			).toBeVisible();

			await pdf.page.getByRole('radio', { name: 'PDF/A-1b' }).check();

			await expect(
				pdf.page.getByText('Enable PDF Security').first()
			).not.toBeVisible();

			await expect(
				pdf.page.getByRole('textbox', { name: 'Password' })
			).not.toBeVisible();

			await expect(
				pdf.page.getByRole('checkbox', {
					name: 'Print - High Resolution',
				})
			).not.toBeVisible();

			await pdf.page.getByRole('radio', { name: 'Standard' }).check();

			await expect(
				pdf.page.getByRole('textbox', { name: 'Password' })
			).toBeVisible();

			await expect(
				pdf.page.getByRole('checkbox', {
					name: 'Print - High Resolution',
				})
			).toBeVisible();

			await pdf.page
				.locator('#gfpdf-settings-field-wrapper-password')
				.getByTitle('Insert Merge Tags')
				.click();

			await pdf.page
				.getByRole('button', { name: 'Date (dd/mm/yyyy)' })
				.click();

			await takeSnapshot(pdf.page, testinfo);
		});

		test('Public Access', async () => {
			await pdf.checkField('Enable Public Access', true);
			await pdf.addOrUpdatePdf();
			await expect(
				pdf.page.getByLabel('Enable Public Access')
			).toBeChecked();
		});

		test('Format', async () => {
			await pdf.chooseField('Standard', 'Standard');
			await pdf.chooseField('PDF/A-1b', 'PDF/A-1b');

			await pdf.addOrUpdatePdf();

			await expect(
				pdf.page.locator('#gfpdf_settings\\[format\\]\\[PDFA1B\\]')
			).toBeChecked();
		});

		test('Image DPI', async () => {
			await pdf.fillField('Image DPI', '150');
			await pdf.addOrUpdatePdf();

			await expect(pdf.page.getByLabel('Image DPI')).toHaveValue('150');
		});

		test('Restrict Owner', async () => {
			await pdf.checkField('Restrict Owner', true);
			await pdf.addOrUpdatePdf();

			await expect(pdf.page.getByLabel('Restrict Owner')).toBeChecked();
		});
	});
});
