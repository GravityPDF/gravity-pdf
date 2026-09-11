import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Locator, Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

/**
 * The 7.0 Font Manager.
 *
 * Every `/fonts` route but `/fonts/settings` is served by the plugin now, so what the manager shows here is
 * what this site actually holds: one bundled family, no packs, no custom fonts and neither catalogue
 * downloaded. That is what the assertions below are written against — an empty group draws no heading, and a
 * source that has never synced offers its Refresh button instead of a grid.
 *
 * Still uncovered, because both need fixtures this project cannot leave behind for the specs it runs beside:
 * the add → edit → delete lifecycle of a custom font, and the install run of a catalogue entry.
 */
test.describe('Font Manager', () => {
	let pdf: Pdf;
	let form: any;

	const openManager = async (page: Page) => {
		await page.locator('.gfpdf-manage-fonts').getByRole('button').click();

		return page.getByRole('dialog', { name: 'Font manager' });
	};

	// The one font every site has. `.font-row` rather than the name, which the detail pane repeats once it opens
	const bundledRow = (manager: Locator) =>
		manager.locator('.font-row').filter({ hasText: 'Arimo' });

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
		).toHaveCount(1);

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

	test('should list the installed fonts by group, and narrow them by search', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await expect(
			manager.getByText('Bundled', { exact: true })
		).toBeVisible();
		await expect(bundledRow(manager)).toBeVisible();

		// A group with no rows draws no heading, and this site has neither a custom font nor a pack
		await expect(manager.getByText('Custom', { exact: true })).toHaveCount(
			0
		);
		await expect(manager.getByText('Language packs')).toHaveCount(0);

		await manager.getByLabel('Search fonts').fill('ari');

		await expect(bundledRow(manager)).toBeVisible();

		await manager.getByLabel('Search fonts').fill('nothing by this name');

		await expect(bundledRow(manager)).toBeHidden();
		await expect(manager.getByText('No results.')).toBeVisible();
	});

	test('should open a bundled font, and show the key a template writes', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await bundledRow(manager).click();

		await expect(
			manager.getByText('Bundled with Gravity PDF · gfpdf-arimo')
		).toBeVisible();
	});

	test('should offer to download a catalogue that has never synced', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await manager.locator('.components-dropdown-menu__toggle').click();
		await page
			.getByRole('menuitem', { name: 'Browse Google Fonts' })
			.click();

		await expect(
			manager.getByText(/The Google Fonts catalogue hasn.t been/)
		).toBeVisible();

		// The header carries a Refresh of its own, so this is the one the empty state offers
		await expect(
			manager
				.locator('.fm-empty-detail')
				.getByRole('button', { name: 'Refresh' })
		).toBeVisible();
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

		await bundledRow(manager).click();

		await expect(manager.locator('.fm-sidebar')).toBeHidden();
		await expect(manager.locator('.fm-detail')).toBeVisible();

		await manager.getByRole('button', { name: 'Fonts' }).click();

		await expect(manager.locator('.fm-sidebar')).toBeVisible();
	});
});
