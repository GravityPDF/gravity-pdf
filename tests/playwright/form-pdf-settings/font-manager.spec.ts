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

		await expect(page.getByLabel('Font')).toBeVisible();
		await page
			.locator('#gfpdf-settings-field-wrapper-font-container')
			.getByRole('button', { name: 'Manage' })
			.click();
		await expect(
			page.getByRole('heading', { name: 'Font Manager' })
		).toBeVisible();
	});

	test('should display a dropdown of default fonts option', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page.getByLabel('Font').click();

		await expect(
			page.getByRole('group', { name: 'Unicode' })
		).toBeVisible();
		await expect(
			page.getByRole('option', { name: 'Dejavu Sans Condensed' })
		).toBeVisible();
		await expect(page.getByRole('group', { name: 'Indic' })).toBeVisible();
		await expect(
			page.getByRole('option', { name: 'Lohit Kannada' })
		).toBeVisible();
	});

	test('should save selected font', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page.getByLabel('Font').selectOption('mph2bdamase');
		await pdf.addOrUpdatePdf();

		await expect(page.getByLabel('Font')).toHaveValue('mph2bdamase');
	});

	test('should display font manager error validation', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.locator('#gfpdf-settings-field-wrapper-font-container')
			.getByRole('button', { name: 'Manage' })
			.click();

		await page.getByRole('button', { name: 'Add Font →' }).click();

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
		await page.locator('#gfpdf-add-font-name-input').fill('Roboto');
		await page
			.locator('input[aria-labelledby*="gfpdf-font-variant-regular"]')
			.setInputFiles(path.join(resourcesPath, 'Roboto-Regular.ttf'));
		await page.getByRole('button', { name: 'Add Font →' }).click();

		await expect(page.getByText('Your font has been saved.')).toBeVisible();
		const fontItems = page.locator('.font-list-item');
		await expect(fontItems).toHaveCount(1);

		// Search Font
		await page.locator('#font-manager-search-box').fill('Roboto');
		await expect(fontItems).toHaveCount(1);

		// Edit Font - check toggled state for disabled 'Update Font' button
		await fontItems.first().click();
		const updateButton = page.getByRole('button', {
			name: 'Update Font →',
		});
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
			.locator('input[aria-labelledby*="gfpdf-font-variant-italics"]')
			.setInputFiles(
				path.join(resourcesPath, 'Roboto-RegularItalic.ttf')
			);
		await page
			.locator('input[aria-labelledby*="gfpdf-font-variant-bold"]')
			.first()
			.setInputFiles(path.join(resourcesPath, 'Roboto-Bold.ttf'));
		await page
			.locator('input[aria-labelledby*="gfpdf-font-variant-bolditalics"]')
			.setInputFiles(path.join(resourcesPath, 'Roboto-BoldItalic.ttf'));

		await updateButton.click();
		await expect(page.getByText('Your font has been saved.')).toBeVisible();
		await expect(page.getByText('Roboto 2')).toBeVisible();

		// Delete Font
		await page.locator('.dashicons-trash').click();
		await expect(page.getByText('Font list empty.')).toBeVisible();
	});

	test('should be able to close font manager popup', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.locator('#gfpdf-settings-field-wrapper-font-container')
			.getByRole('button', { name: 'Manage' })
			.click();

		await page.getByRole('button', { name: 'Close dialog' }).click();
		await expect(
			page.locator('.container.theme-wrap.font-manager')
		).not.toBeVisible();
	});
});
