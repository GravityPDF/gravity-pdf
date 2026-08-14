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
		await page
			.getByRole('button', { name: 'Manage PDF Templates' })
			.click();
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
		await page
			.getByRole('button', { name: 'Manage PDF Templates' })
			.click();

		const templateManager = page.locator('.container.theme-wrap');

		// Search
		await page.locator('#wp-filter-search-input').fill('rubix');
		await expect(templateManager.getByRole('option')).toHaveCount(1);
		await expect(
			templateManager.getByRole('option').locator('h2.theme-name')
		).toHaveText('Rubix');

		// Clear search before upload to ensure upload area is accessible
		await page.locator('#wp-filter-search-input').fill('');

		// Upload
		await page
			.locator('.gfpdf-template-dropzone input[type="file"]')
			.setInputFiles(
				path.join(resourcesPath, 'template', 'test-template.zip')
			);

		// Wait for test template to appear in the list (upload success indicator)
		await expect(
			page.locator('.theme[data-slug="test-template"]')
		).toBeVisible();

		// Template Details
		await templateManager
			.getByRole('option', { name: 'Custom Test Template Details' })
			.locator('.more-details')
			.click();

		await expect(
			page.locator('h2.theme-name', { hasText: 'Test Template' })
		).toBeAttached();

		await expect(
			page.locator('p.theme-author', { hasText: 'Custom' })
		).toBeAttached();

		// Delete
		page.on('dialog', (dialog) => dialog.accept());
		await page.getByRole('button', { name: 'Delete Template' }).click();

		await expect(
			page.locator('.theme[data-slug="test-template"]')
		).not.toBeVisible();
	});

	test('should install every zip in a multi-file selection', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.getByRole('button', { name: 'Manage PDF Templates' })
			.click();

		// Fixtures used by no other test — they all share one WordPress instance
		await page
			.locator('.gfpdf-template-dropzone input[type="file"]')
			.setInputFiles([
				path.join(resourcesPath, 'template', 'bulk-sample.zip'),
				path.join(resourcesPath, 'template', 'bulk-sample-two.zip'),
			]);

		await expect(
			page.locator('.theme[data-slug="bulk-sample"]')
		).toBeVisible();
		await expect(
			page.locator('.theme[data-slug="bulk-sample-two"]')
		).toBeVisible();
	});

	test('should install a template zip that was re-zipped from an extracted folder', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.getByRole('button', { name: 'Manage PDF Templates' })
			.click();

		// Safari auto-extracts downloads, so users re-zip the folder and the templates end up nested
		await page
			.locator('.gfpdf-template-dropzone input[type="file"]')
			.setInputFiles(
				path.join(resourcesPath, 'template', 'rezipped-sample.zip')
			);

		await expect(
			page.locator('.theme[data-slug="rezipped-sample"]')
		).toBeVisible();
	});

	test('should treat the whole Template Manager window as a drop target', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.getByRole('button', { name: 'Manage PDF Templates' })
			.click();

		const overlay = page.locator('.gfpdf-dropzone-overlay');
		await expect(overlay).toBeHidden();

		// Drag a zip over the backdrop, which sits well outside the old "Add New Template" tile
		await page.locator('.theme-backdrop').dispatchEvent('dragenter', {
			dataTransfer: await page.evaluateHandle(() => {
				const dataTransfer = new DataTransfer();
				dataTransfer.items.add(
					new File(['zip'], 'template.zip', {
						type: 'application/zip',
					})
				);
				return dataTransfer;
			}),
		});

		await expect(overlay).toBeVisible();
	});

	test('should be able to close template manager popup button', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.getByRole('button', { name: 'Manage PDF Templates' })
			.click();

		const popup = page.locator('.container.theme-wrap');
		await page.getByRole('button', { name: 'close', exact: true }).click();
		await expect(popup).not.toBeVisible();
	});

	test('should be able to close template manager popup escape', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.getByRole('button', { name: 'Manage PDF Templates' })
			.click();

		const popup = page.locator('.container.theme-wrap');
		await popup.focus();
		await page.keyboard.press('Escape');
		await expect(popup).not.toBeVisible();
	});
});
