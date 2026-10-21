import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test, resourcesPath } from '@self:playwright/fixtures/test';
import Pdf, { type FontUpload } from '@self:playwright/utils/gravitypdf';
import { wpCli } from '@self:playwright/utils/wp-cli';
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

// What the custom font folder holds, to check a failed save left nothing behind and took nothing away
const savedFontFiles = () =>
	wpCli('ls wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts');

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
		await page.getByRole('button', { name: 'Cancel' }).click();
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
		page.on('dialog', (dialog) => dialog.accept());
		await font.locator('.dashicons-trash').click();
		await expect(font).toHaveCount(0);
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
			.locator('#gfpdf-font-variant-italics-updateFont')
			.dispatchEvent('click');
		await setFontFiles(page, 'updateFont', {
			bold: notAFont('Broken.ttf'),
		});
		await page.getByRole('button', { name: 'Update Font' }).click();

		await expect(
			page.locator('.update-font').getByText(/are malformed/)
		).toBeVisible();
		expect(savedFontFiles()).toContain(italics);
	});
});
