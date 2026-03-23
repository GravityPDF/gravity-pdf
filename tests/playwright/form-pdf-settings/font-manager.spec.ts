import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import * as path from 'path';

test.describe('Font Manager', () => {
	let pdf: Pdf;
	let form: any;

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
			pdf = new Pdf(requestUtils, admin, page);
			form = await pdf.createForm('Font Manager');
		}
	);

	test('should display "Font" field and open Font Manager', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		await expect(page.getByLabel('Font', { exact: true })).toBeVisible();
		await page
			.locator('#gfpdf-settings-field-wrapper-font-container')
			.getByRole('button', { name: 'Manage' })
			.click();
		await expect(
			page.getByRole('heading', { name: 'Font Manager', exact: true })
		).toBeVisible();
	});

	test('should display a dropdown of default fonts option', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const select = await page.getByLabel('Font', { exact: true });

		await expect(
			select.locator('optgroup[label="Unicode"]')
		).toBeAttached();

		await expect(select.locator('optgroup[label="Indic"]')).toBeAttached();

		await select.selectOption('Dejavu Sans Condensed');
		await select.selectOption('Lohit Kannada');
	});

	test('should save selected font', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.getByLabel('Font', { exact: true })
			.selectOption('mph2bdamase');

		await pdf.addOrUpdatePdf();

		await expect(page.getByLabel('Font', { exact: true })).toHaveValue(
			'mph2bdamase'
		);
	});

	test('should display font manager error validation', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.locator('#gfpdf-settings-field-wrapper-font-container')
			.getByRole('button', { name: 'Manage' })
			.click();

		await page
			.getByRole('button', { name: 'Add font', visible: true })
			.click();

		await expect(
			page.locator('.input-label-validation-error')
		).toBeVisible();
		await expect(
			page.getByText(
				'Please choose a name contains letters and/or numbers (and a space if you want it).'
			)
		).toBeVisible();
		await expect(page.locator('.drop-zone.required')).toBeVisible();
	});

	test('should successfully add, search, edit, and delete new font', async ({
		page,
	}) => {
		const resourcesPath = path.join(
			__dirname,
			'..',
			'..',
			'e2e',
			'utilities',
			'resources'
		);

		await pdf.navigateToNewFormPdf(form.id);
		await page
			.locator('#gfpdf-settings-field-wrapper-font-container')
			.getByRole('button', { name: 'Manage' })
			.click();

		// Add Font
		await page
			.locator('[data-test="component-AddFont"]')
			.getByRole('textbox', { name: 'Font Name' })
			.fill('Roboto');

		await page
			.locator('#gfpdf-font-variant-regular-addFont')
			.setInputFiles(path.join(resourcesPath, 'Roboto-Regular.ttf'));

		await page
			.getByRole('button', { name: 'Add font', visible: true })
			.click();

		await expect(page.getByText('Your font has been saved.')).toBeVisible();
		const fontItems = page.locator('.font-list-item');
		await expect(fontItems).toHaveCount(1);

		// Search Font
		await page.locator('#font-manager-search-box').fill('Arial');
		await expect(fontItems).toHaveCount(0);
		await page.locator('#font-manager-search-box').fill('Roboto');
		await expect(fontItems).toHaveCount(1);

		const updateButton = page.getByRole('button', { name: 'Update Font' });
		await expect(updateButton).toBeDisabled();

		await page.locator('#gfpdf-update-font-name-input').fill('Roboto 2');
		await expect(updateButton).not.toBeDisabled();

		// Cancel button
		await page.getByRole('button', { name: 'Cancel' }).click();
		await expect(page.locator('.update-font.show')).not.toBeVisible();

		// Edit Font properly
		await fontItems.first().click();
		await page.locator('#gfpdf-update-font-name-input').fill('Roboto 2');
		await page
			.locator('#gfpdf-font-variant-italics-updateFont')
			.setInputFiles(
				path.join(resourcesPath, 'Roboto-RegularItalic.ttf')
			);
		await page
			.locator('#gfpdf-font-variant-bold-updateFont')
			.setInputFiles(path.join(resourcesPath, 'Roboto-Bold.ttf'));
		await page
			.locator('#gfpdf-font-variant-bolditalics-updateFont')
			.setInputFiles(path.join(resourcesPath, 'Roboto-BoldItalic.ttf'));

		await updateButton.click();
		await expect(page.getByText('Your font has been saved.')).toBeVisible();
		await expect(page.getByText('Roboto 2')).toBeVisible();

		// Delete Font
		page.on('dialog', (dialog) => dialog.accept());
		await fontItems.locator('.dashicons-trash').click();
		await expect(page.getByText('Font list empty.')).toBeVisible();
	});

	test('should be able to close font manager popup with button', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.locator('#gfpdf-settings-field-wrapper-font-container')
			.getByRole('button', { name: 'Manage' })
			.click();

		const popup = await page.locator('.container.theme-wrap.font-manager');

		await expect(popup).toBeVisible();
		await page.locator('[data-test="component-CloseDialog"]').click();
		await expect(popup).not.toBeVisible();
	});

	test('should be able to close font manager popup with esc key', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.locator('#gfpdf-settings-field-wrapper-font-container')
			.getByRole('button', { name: 'Manage' })
			.click();

		const popup = await page.locator('.container.theme-wrap.font-manager');

		await expect(popup).toBeVisible();
		await page.keyboard.press('Escape');
		await expect(popup).not.toBeVisible();
	});
});
