import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test, resourcesPath } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { snapshot } from '@self:playwright/utils/snapshot';
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

	// Filtered to one core template, so what's on screen doesn't depend on templates other tests have installed
	const openTemplateManagerShowingRubix = async (page: Page) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.getByRole('button', { name: 'Manage PDF Templates' })
			.click();

		const templateManager = page.locator('.container.theme-wrap');
		await page.locator('#wp-filter-search-input').fill('rubix');
		await expect(templateManager.getByRole('option')).toHaveCount(1);

		return templateManager;
	};

	const dragZipOverBackdrop = async (page: Page) => {
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
	};

	test('should display "Template" field and open Template Manager', async ({
		page,
	}, testinfo) => {
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

		// Just the tile: the rest of the list depends on what other tests have installed
		await snapshot(page, testinfo, page.locator('.theme.add-new-theme'), {
			fixed: true,
		});
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

	test('should explain why templates cannot be uploaded or deleted when file changes are disabled', async ({
		page,
	}, testinfo) => {
		await page.setExtraHTTPHeaders({
			'X-GPDF-E2E-File-Mods': 'disallowed',
		});
		const templateManager = await openTemplateManagerShowingRubix(page);

		await expect(
			templateManager.getByText(
				'Uploading and deleting templates has been disabled on this server.'
			)
		).toBeVisible();
		await expect(
			templateManager.locator('.add-new-theme a')
		).toHaveAttribute('aria-disabled', 'true');

		await snapshot(page, testinfo, templateManager);

		await dragZipOverBackdrop(page);
		await expect(page.locator('.gfpdf-template-dropzone')).toHaveCount(0);
		await expect(page.locator('.gfpdf-dropzone-overlay')).toBeHidden();
	});

	test('should treat the whole Template Manager window as a drop target', async ({
		page,
	}, testinfo) => {
		await openTemplateManagerShowingRubix(page);

		const overlay = page.locator('.gfpdf-dropzone-overlay');
		await expect(overlay).toBeHidden();

		// The backdrop sits well outside the old "Add New Template" tile
		await dragZipOverBackdrop(page);

		await expect(overlay).toBeVisible();

		await snapshot(page, testinfo, overlay);
	});

	test('should report each rejected file in the upload status', async ({
		page,
	}, testinfo) => {
		await openTemplateManagerShowingRubix(page);

		// Refused by the browser for its extension, and by the server for not being a zip
		await page
			.locator('.gfpdf-template-dropzone input[type="file"]')
			.setInputFiles([
				{
					name: 'not-a-template.txt',
					mimeType: 'text/plain',
					buffer: Buffer.from('not a template'),
				},
				{
					name: 'not-a-zip.zip',
					mimeType: 'application/zip',
					buffer: Buffer.from('not a zip'),
				},
			]);

		const status = page.locator('.gfpdf-dropzone-status');

		await expect(status.locator('.notice.error')).toHaveCount(2);
		await expect(status).toContainText('not-a-template.txt');
		await expect(status).toContainText('not-a-zip.zip');

		await snapshot(page, testinfo, status);
	});

	test('should install a template zip over the media upload limit', async ({
		page,
	}) => {
		// Set before the page loads: the browser checks the size against the limit the page is given
		await page.setExtraHTTPHeaders({
			'X-GPDF-E2E-Media-Upload-Limit': 'tiny',
		});
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.getByRole('button', { name: 'Manage PDF Templates' })
			.click();

		// About 6 KB, over the 1 KB limit
		await page
			.locator('.gfpdf-template-dropzone input[type="file"]')
			.setInputFiles(
				path.join(resourcesPath, 'template', 'media-limit-sample.zip')
			);

		await expect(
			page.locator('.theme[data-slug="media-limit-sample"]')
		).toBeVisible();
	});

	test('should only install a Developer Toolkit template when the Toolkit is active', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.getByRole('button', { name: 'Manage PDF Templates' })
			.click();

		const upload = page.locator(
			'.gfpdf-template-dropzone input[type="file"]'
		);
		const toolkitZip = path.join(
			resourcesPath,
			'template',
			'toolkit-sample.zip'
		);
		const template = page.locator('.theme[data-slug="toolkit-sample"]');

		await upload.setInputFiles(toolkitZip);

		await expect(
			page.locator('.gfpdf-dropzone-status .notice.error')
		).toContainText('requires the Gravity PDF Developer Toolkit plugin');
		await expect(template).toHaveCount(0);

		// Picking the same file again fires no change event until the input is cleared
		await page.setExtraHTTPHeaders({ 'X-GPDF-E2E-Toolkit': 'active' });
		await upload.setInputFiles([]);
		await upload.setInputFiles(toolkitZip);

		await expect(template).toBeVisible();

		// Leave the shared site without it
		await template.locator('.more-details').click();
		page.on('dialog', (dialog) => dialog.accept());
		await page.getByRole('button', { name: 'Delete Template' }).click();
		await expect(template).toHaveCount(0);
	});

	test('should clear the upload status when the Template Manager is reopened', async ({
		page,
	}) => {
		await openTemplateManagerShowingRubix(page);

		await page
			.locator('.gfpdf-template-dropzone input[type="file"]')
			.setInputFiles({
				name: 'not-a-template.txt',
				mimeType: 'text/plain',
				buffer: Buffer.from('not a template'),
			});

		const status = page.locator('.gfpdf-dropzone-status');
		await expect(status.locator('.notice.error')).toHaveCount(1);

		await page.getByRole('button', { name: 'close', exact: true }).click();
		await page
			.getByRole('button', { name: 'Manage PDF Templates' })
			.click();

		await expect(
			page.getByRole('heading', { name: 'Installed PDFs' })
		).toBeVisible();
		await expect(status).toBeHidden();
	});

	test('should show the details of a template', async ({
		page,
	}, testinfo) => {
		const templateManager = await openTemplateManagerShowingRubix(page);

		await templateManager
			.getByRole('option')
			.locator('.more-details')
			.click();

		await expect(
			templateManager.locator('h2.theme-name', { hasText: 'Rubix' })
		).toBeVisible();

		await snapshot(page, testinfo, templateManager);
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
