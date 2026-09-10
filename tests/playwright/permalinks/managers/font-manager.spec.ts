import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

/**
 * The 7.0 Font Manager.
 *
 * The `/fonts` REST routes are not built yet, so this build serves them from fixtures in the browser and
 * nothing an admin does here reaches the database. What is covered is everything that does not need the server
 * to remember: the button, the shell, the three groups, search, the source browser, the settings panel and the
 * two ways out. The add → search → edit → delete lifecycle, the install-progress run and the pack install come
 * back with the REST controllers, which is the only thing that can make them assert anything true.
 */
test.describe('Font Manager', () => {
	let pdf: Pdf;
	let form: any;

	const openManager = async (page: Page) => {
		await page.locator('.gfpdf-manage-fonts').getByRole('button').click();

		return page.getByRole('dialog', { name: 'Font manager' });
	};

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

		await expect(await openManager(page)).toBeVisible();
	});

	test('should display a dropdown of default fonts option', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const select = await page.getByLabel('Font', { exact: true });

		// 7.0 ships none of the 6.x core fonts, so the dropdown lists what the font registry actually has
		await expect(
			select.locator('optgroup[label="Bundled Fonts"]')
		).toBeAttached();

		await expect(select.locator('optgroup[label="Unicode"]')).toHaveCount(
			0
		);

		await select.selectOption('Arimo');
	});

	test('should save selected font', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.getByLabel('Font', { exact: true })
			.selectOption('gfpdf-arimo');

		await pdf.addOrUpdatePdf();

		await expect(page.getByLabel('Font', { exact: true })).toHaveValue(
			'gfpdf-arimo'
		);
	});

	test('should group the installed fonts, and narrow them by search', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await expect(
			manager.getByText('Bundled', { exact: true })
		).toBeVisible();
		await expect(
			manager.getByText('Custom', { exact: true })
		).toBeVisible();
		await expect(manager.getByText('Language packs')).toBeVisible();

		await manager.getByLabel('Search fonts').fill('brand');

		await expect(manager.getByText('Brand Sans')).toBeVisible();
		await expect(manager.getByText('Emoji')).toBeHidden();
	});

	test('should open a font for editing, and show the key a template writes', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await manager.getByText('Brand Sans').click();

		await expect(manager.getByText('Edit font')).toBeVisible();
		await expect(manager.getByText('font-family: brandsans')).toBeVisible();
	});

	test('should browse a source, filter it, and open an entry', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await manager.locator('.components-dropdown-menu__toggle').click();
		await page
			.getByRole('menuitem', { name: 'Browse Google Fonts' })
			.click();

		await expect(manager.locator('.gfpdf-fm-cards')).toBeVisible();

		await manager.getByLabel('Category').selectOption('monospace');

		await expect(
			manager.getByText(/of 4 families · Monospace/)
		).toBeVisible();

		await manager.getByText('Roboto Mono').click();

		await expect(manager.getByLabel('Bold Italic')).toBeVisible();
	});

	test('should open the language settings from the header', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await manager
			.getByRole('button', { name: 'Language settings' })
			.click();

		await expect(
			manager.getByLabel('Default document language')
		).toBeVisible();
		await expect(manager.getByText('Bundled · Arimo')).toBeVisible();
	});

	test('should be able to close font manager popup with button', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await expect(manager).toBeVisible();
		await manager.getByRole('button', { name: 'Close dialog' }).click();
		await expect(manager).toBeHidden();
	});

	test('should be able to close font manager popup with esc key', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await expect(manager).toBeVisible();
		await page.keyboard.press('Escape');
		await expect(manager).toBeHidden();
	});

	test('should show one pane at a time on a phone', async ({ page }) => {
		await page.setViewportSize({ width: 390, height: 780 });
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await expect(manager.locator('.fm-sidebar')).toBeVisible();
		await expect(manager.locator('.fm-detail')).toBeHidden();

		await manager.getByText('Brand Sans').click();

		await expect(manager.locator('.fm-sidebar')).toBeHidden();
		await expect(manager.locator('.fm-detail')).toBeVisible();

		await manager.getByRole('button', { name: 'Fonts' }).click();

		await expect(manager.locator('.fm-sidebar')).toBeVisible();
	});
});
