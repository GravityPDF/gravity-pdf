import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test, resourcesPath } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import * as path from 'path';

test.describe('Template Manager', () => {
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
			form = await pdf.createForm('Template Manager');
		}
	);

	test('should display "Template" field and open Template Manager', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const select = page
			.getByRole('group', { name: 'General' })
			.getByLabel('Template', { exact: true });

		await expect(select).toBeVisible();
		await page.locator('[data-test="component-templateButton"]').click();
		await expect(
			page.getByRole('heading', { name: 'Installed PDFs' })
		).toBeVisible();
	});

	test('should display default core templates', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);

		const select = page
			.getByRole('group', { name: 'General' })
			.getByLabel('Template', { exact: true });

		await expect(select.locator('optgroup[label="Core"]')).toBeAttached();
		await select.selectOption('Blank Slate');
		await select.selectOption('Focus Gravity');
	});

	test('should save selected template', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);

		const select = page
			.getByRole('group', { name: 'General' })
			.getByLabel('Template', { exact: true });

		await select.selectOption('rubix');
		await pdf.addOrUpdatePdf();

		await expect(select).toHaveValue('rubix');
	});

	test('should successfully search, upload, show details, and delete template', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page.locator('[data-test="component-templateButton"]').click();

		// Search
		await page.locator('#wp-filter-search-input').fill('rubix');
		await expect(
			page.locator('[data-test=component-templateListItem]')
		).toHaveCount(1);
		await expect(page.locator('[data-test=component-name]')).toHaveText(
			'Rubix'
		);

		// Clear search before upload to ensure upload area is accessible
		await page.locator('#wp-filter-search-input').fill('');

		// Upload
		await page
			.locator(
				'[data-test=component-templateUploader] input[type="file"]'
			)
			.setInputFiles(
				path.join(resourcesPath, 'template', 'test-template.zip')
			);

		// Wait for test template to appear in the list (upload success indicator)
		await expect(
			page.locator('.theme[data-slug="test-template"]')
		).toBeVisible();

		// Template Details
		await page
			.getByRole('option', { name: 'Custom Test Template Details' })
			.locator('[data-test="component-templateDetails"]')
			.click();

		await expect(
			page
				.locator('[data-test="component-name"]')
				.getByText('Test Template')
		).toBeAttached();

		await expect(
			page.locator('[data-test="component-group"]').getByText('Custom')
		).toBeAttached();

		// Delete
		page.on('dialog', (dialog) => dialog.accept());
		await page
			.locator('[data-test="component-templateDeleteButton"]')
			.click();

		await expect(
			page.locator('.theme[data-slug="test-template"]')
		).not.toBeVisible();
	});

	test('should be able to close template manager popup button', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page.locator('[data-test="component-templateButton"]').click();

		const popup = page.locator('.container.theme-wrap');
		await page.locator('[data-test="component-CloseDialog"]').click();
		await expect(popup).not.toBeVisible();
	});

	test('should be able to close template manager popup escape', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page.locator('[data-test="component-templateButton"]').click();

		const popup = page.locator('.container.theme-wrap');
		await popup.focus();
		await page.keyboard.press('Escape');
		await expect(popup).not.toBeVisible();
	});
});
