import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test, expect, resourcesPath } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { Form } from '@self:playwright/utils/gravityforms';
import * as path from 'path';

test.describe('Font Manager', () => {
	/* Serialize: several tests add and delete a font with the same name; with
	   the 4-worker default config they race against the shared database and
	   collide on the unique-name constraint. */
	test.describe.configure({ mode: 'serial' });

	let pdf: Pdf;
	let form: Form;

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

	const openFontManager = async (page: Page) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.locator('#gfpdf-settings-field-wrapper-font-container')
			.getByRole('button', { name: 'Manage' })
			.click();
		await expect(
			page.locator('[data-test="component-FontManagerModal"]')
		).toBeVisible();
	};

	async function deleteAnyFonts(page: Page): Promise<void> {
		await openFontManager(page);

		/* Wait for the GET_CUSTOM_FONT_LIST round-trip to settle before
		   counting rows — opening the modal kicks off an async REST fetch
		   and racing against it makes rows.count() return 0 prematurely,
		   leaving any saved fonts un-cleaned. The list paints either a row
		   or the empty-state copy when loading completes. */
		await expect(
			page.locator(
				'[data-test="component-FontListItem"], .gfpdf-fm-list__empty'
			).first()
		).toBeVisible();

		const rows = page.locator('[data-test="component-FontListItem"]');

		/* Delete one row at a time, waiting for the row count to drop before
		   clicking the next one — the REST round-trip is async and racing
		   subsequent clicks against in-flight deletes leaves leftover rows. */
		// eslint-disable-next-line no-await-in-loop
		while ((await rows.count()) > 0) {
			const before = await rows.count();
			// eslint-disable-next-line no-await-in-loop
			await rows
				.first()
				.getByRole('button', { name: /delete /i })
				.click();
			// eslint-disable-next-line no-await-in-loop
			await page
				.getByRole('dialog')
				.filter({
					hasText: 'This font will be removed from the site',
				})
				.getByRole('button', { name: 'Delete font' })
				.click();
			// eslint-disable-next-line no-await-in-loop
			await expect(rows).toHaveCount(before - 1);
		}
	}

	test('should display "Font" field and open Font Manager', async ({
		page,
	}) => {
		await openFontManager(page);
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

		await deleteAnyFonts(page);
	});

	test('should display only .ttf rejection when uploading wrong file type', async ({
		page,
	}) => {
		await openFontManager(page);
		await page
			.locator('[data-test="component-FontSidebar"]')
			.getByRole('button', { name: /add new font/i })
			.click();

		/* Locate the hidden FormFileUpload input on the Regular row and try
		   to upload a non-.ttf — the front-end rejects this before it ever
		   reaches the backend. */
		const regularRow = page.locator(
			'[data-test="component-VariantRow"][data-variant-key="regular"]'
		);
		await regularRow
			.locator('input[type="file"]')
			.setInputFiles(path.join(resourcesPath, 'images', 'thumbnail.jpg'));

		await expect(
			page
				.locator('.components-snackbar')
				.getByText(/only .ttf files are supported/i)
		).toBeVisible();
	});

	test('should successfully add, search, edit, and delete new font', async ({
		page,
	}) => {
		/* Clear any fonts left behind by prior failed runs so the count
		   assertions below start from a known-empty list. */
		await deleteAnyFonts(page);

		await openFontManager(page);

		const sidebar = page.locator('[data-test="component-FontSidebar"]');
		const detail = page.locator('[data-test="component-FontDetail"]');
		const rows = page.locator('[data-test="component-FontListItem"]');

		// Add Font
		await sidebar.getByRole('button', { name: /add new font/i }).click();
		await detail.getByLabel('Font name').fill('Roboto');

		await page
			.locator(
				'[data-test="component-VariantRow"][data-variant-key="regular"] input[type="file"]'
			)
			.setInputFiles(
				path.join(resourcesPath, 'fonts', 'Roboto-Regular.ttf')
			);

		await detail.getByRole('button', { name: 'Add font' }).click();

		await expect(page.locator('.components-snackbar').filter({ hasText: 'Your font has been saved.' }).last()).toBeVisible();
		await expect(rows).toHaveCount(1);

		// Search Font
		await sidebar
			.getByRole('searchbox', { name: /search fonts/i })
			.fill('Arial');
		await expect(rows).toHaveCount(0);
		await sidebar
			.getByRole('searchbox', { name: /search fonts/i })
			.fill('Roboto');
		await expect(rows).toHaveCount(1);

		// Edit Font properly
		await rows.first().click();
		await detail.getByLabel('Font name').fill('Roboto 2');
		await page
			.locator(
				'[data-test="component-VariantRow"][data-variant-key="italics"] input[type="file"]'
			)
			.setInputFiles(
				path.join(resourcesPath, 'fonts', 'Roboto-RegularItalic.ttf')
			);
		await page
			.locator(
				'[data-test="component-VariantRow"][data-variant-key="bold"] input[type="file"]'
			)
			.setInputFiles(
				path.join(resourcesPath, 'fonts', 'Roboto-Bold.ttf')
			);
		await page
			.locator(
				'[data-test="component-VariantRow"][data-variant-key="bolditalics"] input[type="file"]'
			)
			.setInputFiles(
				path.join(resourcesPath, 'fonts', 'Roboto-BoldItalic.ttf')
			);

		/* Adding net-new variants to an existing font is non-destructive
		   (no SaveReplaceDialog), so the save proceeds straight through. */
		await detail.getByRole('button', { name: 'Save changes' }).click();
		await expect(page.locator('.components-snackbar').filter({ hasText: 'Your font has been saved.' }).last()).toBeVisible();
		await expect(page.getByText('Roboto 2')).toBeVisible();

		// Delete Font
		await detail.getByRole('button', { name: /delete font/i }).click();
		await page
			.getByRole('dialog')
			.filter({
				hasText: 'This font will be removed from the site',
			})
			.getByRole('button', { name: 'Delete font' })
			.click();

		await expect(
			page.getByText(/no custom fonts installed yet/i)
		).toBeVisible({
			timeout: 10000,
		});
	});

	test('should be able to close font manager popup with button', async ({
		page,
	}) => {
		await openFontManager(page);
		const popup = page.locator('.gfpdf-font-manager-modal');

		await expect(popup).toBeVisible();
		await popup.getByRole('button', { name: 'Close' }).click();
		await expect(popup).not.toBeVisible();
	});

	test('should be able to close font manager popup with esc key', async ({
		page,
	}) => {
		await openFontManager(page);
		const popup = page.locator('.gfpdf-font-manager-modal');

		await expect(popup).toBeVisible();
		await page.keyboard.press('Escape');
		await expect(popup).not.toBeVisible();
	});

	test('Set as active syncs the parent <select> after closing the modal', async ({
		page,
	}) => {
		/* Add a font, mark it active, close the modal, then verify the
		   parent <select> reflects the chosen font. */
		await deleteAnyFonts(page);
		await openFontManager(page);

		const sidebar = page.locator('[data-test="component-FontSidebar"]');
		const detail = page.locator('[data-test="component-FontDetail"]');

		await sidebar.getByRole('button', { name: /add new font/i }).click();
		await detail.getByLabel('Font name').fill('Roboto');
		await page
			.locator(
				'[data-test="component-VariantRow"][data-variant-key="regular"] input[type="file"]'
			)
			.setInputFiles(
				path.join(resourcesPath, 'fonts', 'Roboto-Regular.ttf')
			);
		await detail.getByRole('button', { name: 'Add font' }).click();
		await expect(page.locator('.components-snackbar').filter({ hasText: 'Your font has been saved.' }).last()).toBeVisible();

		/* The Set as active button only appears once the font is saved with
		   a Regular variant. */
		await detail.getByRole('button', { name: /set as active/i }).click();

		await page
			.locator('.gfpdf-font-manager-modal')
			.getByRole('button', { name: 'Close' })
			.click();

		await expect(page.getByLabel('Font', { exact: true })).toHaveValue(
			'roboto'
		);

		await deleteAnyFonts(page);
	});
});
