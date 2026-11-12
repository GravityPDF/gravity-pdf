import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test, resourcesPath } from '@self:playwright/fixtures/test';
import Pdf, { type FontUpload } from '@self:playwright/utils/gravitypdf';
import { wpCli } from '@self:playwright/utils/wp-cli';
import { coreFontStandIn, hashedName } from '@self:playwright/utils/core-fonts';
import * as fs from 'fs';
import * as path from 'path';

/**
 * An upload of one of the test fonts, optionally under a filename of the test's choosing
 * @param font
 * @param name
 */
const fontFile = (font: string, name = font): FontUpload => ({
	name,
	mimeType: 'font/ttf',
	buffer: fs.readFileSync(path.join(resourcesPath, 'fonts', font)),
});

const notAFont = (name: string): FontUpload => ({
	name,
	mimeType: 'font/ttf',
	buffer: Buffer.from('not a font'),
});

const FONTS_DIR = 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts';

// What the font folder holds, to check a failed save left nothing behind and took nothing away
const savedFontFiles = () => wpCli(`ls ${FONTS_DIR}`);

const openFontManager = async (page: Page) =>
	page
		.locator('#gfpdf-settings-field-wrapper-font-container')
		.getByRole('button', { name: 'Manage' })
		.click();

const fontItem = (page: Page, name: string) =>
	page.locator('.font-list-item').filter({ hasText: name });

/**
 * Choose a file for each font style in the Add Font or Update Font form
 * @param page
 * @param form
 * @param files each font style mapped to the file uploaded for it
 */
const setFontFiles = async (
	page: Page,
	form: 'addFont' | 'updateFont',
	files: Record<string, FontUpload>
) => {
	for (const [style, file] of Object.entries(files)) {
		await page
			.locator(`#gfpdf-font-variant-${style}-${form}`)
			.setInputFiles(file);
	}
};

/**
 * Fill in the Add Font form and submit it
 * @param page
 * @param name
 * @param files each font style mapped to the file uploaded for it
 */
const addFontInUi = async (
	page: Page,
	name: string,
	files: Record<string, FontUpload>
) => {
	await page
		.locator('.add-font')
		.getByRole('textbox', { name: 'Font Name' })
		.fill(name);

	await setFontFiles(page, 'addFont', files);

	await page
		.getByRole('button', { name: 'Add font' })
		.filter({ visible: true })
		.click();
};

test.describe('Font Manager', () => {
	// Every font save rewrites the whole settings option, so two at once can drop each other's font
	test.describe.configure({ mode: 'default' });

	let pdf: Pdf;
	let form: any;

	// Fonts a test saves, deleted after it so they don't pile up in the shared settings
	const savedFonts: string[] = [];

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

	test.afterEach(() => pdf.deleteFonts(savedFonts.splice(0)));

	test('should display "Font" field and open Font Manager', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		await expect(page.getByLabel('Font', { exact: true })).toBeVisible();
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
	});

	test('should display font manager error validation', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);
		await openFontManager(page);

		await page
			.getByRole('button', { name: 'Add font' })
			.filter({ visible: true })
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

	test('should be able to close font manager popup with button', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await openFontManager(page);

		const popup = await page.locator('.container.theme-wrap.font-manager');

		await expect(popup).toBeVisible();
		await page.getByRole('button', { name: 'close', exact: true }).click();
		await expect(popup).not.toBeVisible();
	});

	test('should be able to close font manager popup with esc key', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await openFontManager(page);

		const popup = await page.locator('.container.theme-wrap.font-manager');

		await expect(popup).toBeVisible();
		await page.keyboard.press('Escape');
		await expect(popup).not.toBeVisible();
	});

	test('should successfully add, search, edit, and delete new font', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await openFontManager(page);

		const fontName = `Roboto ${Date.now()}`;

		// Add Font
		await addFontInUi(page, fontName, {
			regular: fontFile('Roboto-Regular.ttf'),
		});

		await expect(page.getByText('Your font has been saved.')).toBeVisible();
		const fontItems = page.locator('.font-list-item');
		const font = fontItem(page, fontName);
		await expect(font).toHaveCount(1);
		await expect(font.locator('.font-sub')).toHaveText('1 style');

		// Search Font
		await page.locator('#font-manager-search-box').fill('Arial');
		await expect(fontItems).toHaveCount(0);
		await page.locator('#font-manager-search-box').fill(fontName);
		await expect(fontItems).toHaveCount(1);

		const updateButton = page.getByRole('button', {
			name: 'Update Font',
		});
		await expect(updateButton).toBeDisabled();

		await page
			.locator('#gfpdf-update-font-name-input')
			.fill(`${fontName} Edited`);
		await expect(updateButton).not.toBeDisabled();

		// Cancel button
		await page
			.locator('.update-font')
			.getByRole('button', { name: 'Cancel' })
			.click();
		await expect(page.locator('.update-font.show')).not.toBeVisible();

		// Edit Font properly
		await font.click();
		await page
			.locator('#gfpdf-update-font-name-input')
			.fill(`${fontName} Edited`);
		await setFontFiles(page, 'updateFont', {
			italics: fontFile('Roboto-RegularItalic.ttf'),
			bold: fontFile('Roboto-Bold.ttf'),
			bolditalics: fontFile('Roboto-BoldItalic.ttf'),
		});

		await updateButton.click();
		await expect(page.getByText('Your font has been saved.')).toBeVisible();
		await expect(page.getByText(`${fontName} Edited`)).toBeVisible();

		// Delete Font
		await expect(font).toHaveClass(/active/);
		const deleteButton = page.locator('.update-font .font-delete');
		await expect(deleteButton).toHaveAttribute('aria-disabled', 'false');
		await deleteButton.click();
		await page.locator('.font-delete-confirm-delete').click();
		await expect(font).toHaveCount(0);
		await expect(
			page.locator('[data-test="font-action-notice"]')
		).toContainText(`${fontName} Edited deleted.`);
	});

	test('should add a font whose filenames have dots and brackets', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await openFontManager(page);

		// Unique filenames, so the upload isn't renamed to a free one
		const id = Date.now();
		const fontName = `Dotted ${id}`;
		savedFonts.push(fontName);

		await addFontInUi(page, fontName, {
			regular: fontFile(
				'Roboto-Regular.ttf',
				`Roboto.Regular (${id}).ttf`
			),
			bold: fontFile('Roboto-Bold.ttf', `Roboto.Bold (${id}).ttf`),
		});

		await expect(page.getByText('Your font has been saved.')).toBeVisible();
		await expect(fontItem(page, fontName)).toHaveCount(1);
	});

	test('should not add a font when one of its files is not a valid TTF', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		await openFontManager(page);

		const id = Date.now();
		const fontName = `Malformed ${id}`;
		savedFonts.push(fontName);
		const regular = `Malformed-${id}.ttf`;

		await addFontInUi(page, fontName, {
			regular: fontFile('Roboto-Regular.ttf', regular),
			bold: notAFont('Broken.ttf'),
		});

		await expect(
			page.locator('.add-font').getByText(/are malformed/)
		).toBeVisible();
		await expect(page.locator('.add-font .drop-zone.error')).toHaveCount(1);
		await expect(fontItem(page, fontName)).toHaveCount(0);
		expect(savedFontFiles()).not.toContain(regular);
	});

	test('should delete the file a font no longer uses once it is updated', async ({
		page,
	}) => {
		const id = Date.now();
		const fontName = `Replaced ${id}`;
		savedFonts.push(fontName);
		const original = `Replaced-${id}.ttf`;
		const replacement = `Replacement-${id}.ttf`;

		await pdf.addFont(fontName, {
			regular: fontFile('Roboto-Regular.ttf', original),
		});

		await pdf.navigateToNewFormPdf(form.id);
		await openFontManager(page);
		await fontItem(page, fontName).click();
		await setFontFiles(page, 'updateFont', {
			regular: fontFile('Roboto-Bold.ttf', replacement),
		});
		await page.getByRole('button', { name: 'Update Font' }).click();

		await expect(page.getByText('Your font has been saved.')).toBeVisible();

		const files = savedFontFiles();
		expect(files).toContain(replacement);
		expect(files).not.toContain(original);
	});

	test('should keep a font as it was when an update has a file that is not a valid TTF', async ({
		page,
	}) => {
		const id = Date.now();
		const fontName = `Unchanged ${id}`;
		savedFonts.push(fontName);
		const italics = `Unchanged-Italic-${id}.ttf`;

		await pdf.addFont(fontName, {
			regular: fontFile('Roboto-Regular.ttf', `Unchanged-${id}.ttf`),
			italics: fontFile('Roboto-RegularItalic.ttf', italics),
		});

		await pdf.navigateToNewFormPdf(form.id);
		await openFontManager(page);
		await fontItem(page, fontName).click();

		// Remove the italics and add a broken bold in the same save
		await page
			.locator(
				'.update-font [data-variant="italics"] .font-variant-delete'
			)
			.click();
		await setFontFiles(page, 'updateFont', {
			bold: notAFont('Broken.ttf'),
		});
		await page.getByRole('button', { name: 'Update Font' }).click();

		await expect(
			page.locator('.update-font').getByText(/are malformed/)
		).toBeVisible();
		expect(savedFontFiles()).toContain(italics);
	});

	test.describe('Core and custom fonts in one list', () => {
		const seededFiles: string[] = [];

		// Seeds a core font no other spec uses
		const seedCoreFont = (...files: string[]) => {
			seededFiles.push(...files.map(hashedName));
			wpCli(
				...files.map((file) =>
					coreFontStandIn(
						'wp-content/plugins/gravity-pdf/fonts/gfpdf-Arimo-Regular.ttf',
						`${FONTS_DIR}/${file}`
					)
				)
			);
		};

		const fontRow = (page: Page, label: string) =>
			page
				.locator('[data-test="font-list-item"]')
				.filter({ hasText: label });

		const fontGroup = (page: Page, type: string) =>
			page.locator(`[data-test="font-list-group-${type}"]`);

		test.afterEach(() =>
			wpCli(`cd ${FONTS_DIR} && rm -f ${seededFiles.splice(0).join(' ')}`)
		);

		test('should open a core font read-only, make it active and delete it', async ({
			page,
		}) => {
			const label = 'Dhyana (Lao)';
			seedCoreFont('Dhyana-Regular.ttf', 'Dhyana-Bold.ttf');

			await pdf.navigateToNewFormPdf(form.id);
			await openFontManager(page);

			const font = fontRow(page, label);
			await expect(fontGroup(page, 'core')).toContainText(label);
			await expect(font.locator('.font-sub')).toHaveText('2 styles');

			await font.click();
			await expect(page.locator('.update-font.show')).toHaveCount(0);
			await expect(page.locator('.add-font')).toBeHidden();

			const detail = page.locator('.font-detail');
			await expect(detail.locator('h2')).toHaveText(label);
			await expect(detail).toContainText('font-family: dhyana');
			await expect(detail).toContainText('Not in use');
			const samples = detail.locator('[data-test="font-preview-sample"]');
			await expect(samples).toHaveCount(1);
			await detail
				.locator('[data-test="font-preview-style"]')
				.selectOption('all');
			await expect(samples).toHaveCount(2);

			await detail.locator('[data-test="font-set-active"]').click();
			await expect(
				detail.locator('[data-test="font-active"]')
			).toBeVisible();
			await expect(font.locator('.font-active-tag')).toBeVisible();

			// The selected font moves out of its group to the top, and the groups keep their order
			await expect(
				page.locator(
					'[data-test="font-list-selected"] [data-font="dhyana"]'
				)
			).toBeVisible();
			await expect(fontGroup(page, 'core').getByText(label)).toHaveCount(
				0
			);

			await detail.locator('.font-delete').click();
			const confirm = page.locator('.font-delete-confirm');
			await expect(confirm).toContainText(
				'Font downloads are turned off'
			);

			// Cancel keeps it
			await confirm.getByRole('button', { name: 'Cancel' }).click();
			await expect(confirm).toHaveCount(0);

			await detail.locator('.font-delete').click();
			await page.locator('.font-delete-confirm-delete').click();

			await expect(font).toHaveCount(0);
			await expect(page.locator('.add-font')).toBeVisible();
			expect(savedFontFiles()).not.toContain('Dhyana');
		});

		test('should not delete a core font in use', async ({ page }) => {
			// The default font for this test's requests only (see tools/mu-plugins)
			await page.setExtraHTTPHeaders({
				'X-GPDF-E2E-Default-Font': 'jomolhari',
			});
			const label = 'Jomolhari (Tibetan)';
			seedCoreFont('Jomolhari.ttf');

			await pdf.navigateToNewFormPdf(form.id);
			await openFontManager(page);

			const font = fontRow(page, label);
			await expect(font.locator('.font-sub')).toContainText('In use');

			await font.click();
			const detail = page.locator('.font-detail');
			await expect(detail).toContainText('Default font');
			await expect(detail.locator('.font-delete')).toHaveAttribute(
				'aria-disabled',
				'true'
			);
		});

		test('should link to the settings of the PDFs using a font', async ({
			page,
		}) => {
			const label = 'Jomolhari (Tibetan)';
			seedCoreFont('Jomolhari.ttf');
			const name = `Tibetan ${Date.now()}`;
			const pid = await pdf.addPdf(form.id, name, { font: 'jomolhari' });

			await pdf.navigateToNewFormPdf(form.id);
			await openFontManager(page);
			await fontRow(page, label).click();

			const link = page
				.locator('[data-test="font-list-pdfs"]')
				.getByRole('link', { name });
			await expect(link).toHaveAttribute('target', '_blank');
			await expect(link).toHaveAttribute(
				'href',
				new RegExp(`id=${form.id}&pid=${pid}$`)
			);
		});

		test('should keep the PDF count when the list of PDFs fails to load', async ({
			page,
		}) => {
			// A font of its own, as the other tests here delete the ones they seed
			const label = 'Abyssinica SIL';
			seedCoreFont('Abyssinica_SIL.ttf');
			await pdf.addPdf(form.id, `Ethiopic ${Date.now()}`, {
				font: 'abyssinicasil',
			});
			await page.route(/abyssinicasil.*scope=usage/, (route) =>
				route.fulfill({ status: 500, body: '{}' })
			);

			await pdf.navigateToNewFormPdf(form.id);
			await openFontManager(page);
			await fontRow(page, label).click();

			// Other specs share the site, so its PDFs may use the font too
			await expect(page.locator('.font-detail')).toContainText(
				/Used by \d+ PDFs?/
			);
			await expect(
				page.locator('[data-test="font-list-pdfs"] a')
			).toHaveCount(0);
		});

		test('should reject a request for font usage with the wrong scope', async ({
			page,
		}) => {
			await pdf.navigateToNewFormPdf(form.id);
			const status = await page.evaluate(async () => {
				// @ts-expect-error the plugin's localized settings
				const url = new URL(`${GFPDF.restUrl}fonts/arimo`);
				url.searchParams.set('scope', 'nonsense');
				const response = await fetch(url.toString(), {
					// @ts-expect-error the plugin's localized settings
					headers: { 'X-WP-Nonce': GFPDF.restNonce },
				});
				return response.status;
			});

			expect(status).toBe(400);
		});

		test('should show a bundled font with no delete', async ({ page }) => {
			await pdf.navigateToNewFormPdf(form.id);
			await openFontManager(page);

			const font = page.locator('[data-font="arimo"]');
			await expect(font.locator('.font-lock')).toBeVisible();

			await font.click();
			const detail = page.locator('.font-detail');
			await expect(detail).toContainText('Bundled with Gravity PDF');
			await expect(detail.locator('.font-delete')).toHaveCount(0);
		});

		test('should let the route decide which panel shows', async ({
			page,
		}) => {
			const id = Date.now();
			const fontName = `Routed ${id}`;
			savedFonts.push(fontName);
			await pdf.addFont(fontName, {
				regular: fontFile('Roboto-Regular.ttf', `Routed-${id}.ttf`),
			});

			await pdf.navigateToNewFormPdf(form.id);
			await openFontManager(page);

			await fontRow(page, fontName).click();
			await expect(page.locator('.update-font.show')).toBeVisible();
			await expect(
				page.locator('#gfpdf-update-font-name-input')
			).toBeFocused();

			await page.goBack();
			await expect(page.locator('.update-font.show')).toHaveCount(0);
			await expect(page.locator('.add-font')).toBeVisible();
		});

		test('should go back to the add form with the add button', async ({
			page,
		}) => {
			await pdf.navigateToNewFormPdf(form.id);
			await openFontManager(page);

			await page.locator('[data-font="arimo"]').click();
			await expect(page.locator('.font-detail')).toBeVisible();

			await page.locator('[data-test="font-add-new"]').click();
			await expect(page.locator('.font-detail')).toHaveCount(0);
			await expect(
				page.locator('#gfpdf-add-font-name-input')
			).toBeFocused();

			// Cancel empties the form
			const name = page.locator('#gfpdf-add-font-name-input');
			await name.fill('Draft');
			await page
				.locator('.add-font')
				.getByRole('button', { name: 'Cancel' })
				.click();
			await expect(name).toHaveValue('');
		});
	});
});
