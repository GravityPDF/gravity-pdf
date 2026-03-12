import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
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

		await expect(page.getByLabel('Template')).toBeVisible();
		await page
			.locator('#gpdf-advance-template-selector')
			.getByRole('button', { name: 'Manage' })
			.click();
		await expect(
			page.getByRole('heading', { name: 'Installed PDFs' })
		).toBeVisible();
	});

	test('should display default core templates', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page.getByLabel('Template').click();

		await expect(page.getByRole('group', { name: 'Core' })).toBeVisible();
		await expect(
			page.getByRole('option', { name: 'Blank Slate' })
		).toBeVisible();
		await expect(
			page.getByRole('option', { name: 'Focus Gravity' })
		).toBeVisible();
	});

	test('should save selected template', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page.getByLabel('Template').selectOption('rubix');
		await pdf.addOrUpdatePdf();

		await expect(page.getByLabel('Template')).toHaveValue('rubix');
	});

	test('should successfully search, upload, show details, and delete template', async ({
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
			.locator('#gpdf-advance-template-selector')
			.getByRole('button', { name: 'Manage' })
			.click();

		// Search
		await page.locator('#wp-filter-search-input').fill('rubix');
		await expect(page.locator('.theme')).toHaveCount(1);
		await expect(page.locator('.theme-name')).toHaveText('Rubix');

		// Upload
		await page
			.locator('input[type="file"]')
			.setInputFiles(path.join(resourcesPath, 'test-template.zip'));
		await expect(
			page.getByText('Template successfully installed')
		).toBeVisible();

		// Template Details
		await page
			.locator('.theme[data-slug="test-template"]')
			.getByText('Template Details')
			.click();
		await expect(page.locator('.theme-name.current')).toHaveText(
			'Test Template'
		);
		await expect(page.locator('.theme-author')).toContainText('Custom');

		// Navigation in details
		await page.getByRole('button', { name: 'Show next template' }).click();
		await expect(page.locator('.theme-name.current')).not.toHaveText(
			'Test Template'
		);

		// Delete
		page.on('dialog', (dialog) => dialog.accept());
		await page
			.locator('.theme[data-slug="test-template"]')
			.getByText('Template Details')
			.click();
		await page.getByRole('button', { name: 'Delete' }).click();

		await expect(
			page.locator('.theme[data-slug="test-template"]')
		).not.toBeVisible();
	});

	test('should be able to close template manager popup', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.locator('#gpdf-advance-template-selector')
			.getByRole('button', { name: 'Manage' })
			.click();

		await page.getByRole('button', { name: 'Close dialog' }).click();
		await expect(page.locator('.container.theme-wrap')).not.toBeVisible();
	});
});
