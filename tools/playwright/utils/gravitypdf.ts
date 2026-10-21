import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Locator } from '@playwright/test';
import type { Readable } from 'stream';
import { URL } from 'node:url';
import GravityForms from '@self:playwright/utils/gravityforms';

export type FontUpload = { name: string; mimeType: string; buffer: Buffer };

export default class Pdf extends GravityForms {
	/**
	 * Add a PDF to a form over the plugin's REST API, returning its ID
	 *
	 * The endpoint fills in every setting left out with its default, as the admin UI does. Seeding a PDF the test
	 * isn't about this way costs one request, where the UI costs a few page loads.
	 * @param formId
	 * @param name     also used as the filename
	 * @param settings any to change, e.g. `{ active: false }`
	 */
	async addPdf(formId: number, name: string, settings: object = {}) {
		const pdf: { id: string } = await this.requestUtils.rest({
			method: 'POST',
			path: `/gravity-pdf/v1/form/${formId}`,
			data: { name, filename: name, template: 'zadani', ...settings },
		});

		return pdf.id;
	}

	/**
	 * Add a custom font over the plugin's REST API
	 * @param label
	 * @param files each font style (regular, bold, italics, bolditalics) mapped to the file uploaded for it
	 */
	async addFont(label: string, files: Record<string, FontUpload>) {
		await this.requestUtils.rest({
			method: 'POST',
			path: '/gravity-pdf/v1/fonts',
			multipart: { label, ...files },
		});
	}

	/**
	 * Delete the custom fonts with these names over the plugin's REST API, along with their files
	 * @param names
	 */
	async deleteFonts(names: string[]) {
		if (!names.length) {
			return;
		}

		const fonts: { id: string; font_name: string }[] =
			await this.requestUtils.rest({ path: '/gravity-pdf/v1/fonts' });

		// The delete endpoint answers with an empty body, which rest() can't parse, so it's sent as a plain request
		const { rootURL, nonce } = this.requestUtils.storageState!;

		for (const font of fonts.filter((f) => names.includes(f.font_name))) {
			const response = await this.requestUtils.request.delete(
				`${rootURL}gravity-pdf/v1/fonts/${font.id}`,
				{ headers: { 'X-WP-Nonce': nonce } }
			);

			expect(response.ok()).toBe(true);
		}
	}

	/**
	 * A PDF's HTML (?html=1) or $form_data (?data=1), as an administrator with Debug Mode on for the request
	 * @param pdfId
	 * @param entryId
	 * @param helper
	 */
	async getPdfDebugOutput(
		pdfId: string,
		entryId: number,
		helper: 'html' | 'data' = 'html'
	) {
		const response = await this.requestUtils.request.get(
			`/?gpdf=1&pid=${pdfId}&lid=${entryId}&${helper}=1`,
			{ headers: { 'X-GPDF-E2E-Debug-Mode': 'yes' } }
		);

		return await response.text();
	}

	/**
	 * The $form_data the ?data=1 view shows, as text the browser decodes from its escaped <pre>
	 * @param pdfId
	 * @param entryId
	 */
	async getPdfFormData(pdfId: string, entryId: number) {
		await this.page.setContent(
			await this.getPdfDebugOutput(pdfId, entryId, 'data')
		);

		return await this.page.locator('pre').textContent();
	}

	async fillField(label: string, value: string) {
		await this.page.getByLabel(label).fill(value);
	}

	async selectField(label: string, value: string) {
		await this.page.getByLabel(label).selectOption(value);
	}

	async checkField(label: string, value: boolean) {
		const checkbox = this.page.getByLabel(label);
		// eslint-disable-next-line no-unused-expressions
		value ? await checkbox.check() : await checkbox.uncheck();
	}

	async chooseField(label: string, value: string) {
		await this.page.getByRole('radio', { name: value }).check();
	}

	async navigateToFormPdfList(formId: number) {
		await this.navigateToFormSettingsById(formId, 'PDF');
	}

	/**
	 * Flip a PDF's active state from the PDF list that is open, and wait for it to save
	 * @param pdfId
	 * @param expected the state the PDF should end up in
	 */
	async togglePdf(pdfId: string, expected: 'active' | 'inactive') {
		const toggle = this.page
			.locator(`#gfpdf-${pdfId}`)
			.locator('button.gform-status-indicator');

		await toggle.click();

		// The state saves over AJAX, and the status only flips once it has
		await expect(toggle).toHaveAttribute('data-status', expected);
	}

	/**
	 * Save the new-PDF form that is already open, returning the ID of the PDF it creates
	 * @param label
	 */
	async saveNewPdf(label: string) {
		await this.page.getByLabel('Label').fill(label);
		await this.page.getByLabel('Filename').fill(label);
		await this.addOrUpdatePdf();

		// return PDF ID (@TODO update with the REST API settings once implemented)
		const pdfUrl = new URL(this.page.url());

		return pdfUrl.searchParams.get('pid');
	}

	async addOrUpdatePdf() {
		const addButton = this.page.getByRole('button', { name: 'Add PDF' });

		if ((await addButton.count()) > 0) {
			await addButton.first().click();
		} else {
			await this.page
				.getByRole('button', { name: 'Update PDF' })
				.first()
				.click();
		}

		await expect(
			this.page.getByRole('button', { name: 'Manage PDF Templates' })
		).toBeVisible({ timeout: 30000 });
	}

	async navigateToNewFormPdf(formId: number) {
		// What the list's "Add new PDF" link points at, without loading the list first
		await this.navigateToFormPdf(formId, '0');
	}

	async navigateToFormPdf(formId: number, pdfId: string) {
		await this.admin.visitAdminPage(
			'admin.php',
			`page=gf_edit_forms&view=settings&subview=PDF&id=${formId}&pid=${pdfId}`
		);
		await expect(
			this.page.getByRole('button', { name: 'Manage PDF Templates' })
		).toBeVisible({ timeout: 30000 });
	}

	async copyDownloadShortcodeToClipboard(formId: number, pdfId: string) {
		await this.navigateToFormPdfList(formId);
		await this.page.locator(`#gfpdf-${pdfId}`).getByRole('dialog').click();
	}

	async checkRichTextEditor(container: Locator) {
		// Merge Tag Selector
		await container.getByTitle('Insert Merge Tags').click();
		await container.getByRole('button', { name: 'Radio' }).click();
		await container.getByTitle('Insert Merge Tags').click();
		await container.getByRole('button', { name: 'Entry Id' }).click();

		// Add Media
		await container.getByRole('button', { name: 'Add Media' }).click();
		await this.page.getByRole('tab', { name: 'Upload files' }).click();
		await this.page
			.locator('.media-modal-content:visible')
			.locator('input[type=file]')
			.setInputFiles(__dirname + '/../data/images/thumbnail.jpg');
		await this.page
			.getByRole('button', { name: 'Insert into Post' })
			.click();

		// Code View
		await container
			.getByRole('button', { name: /^Code$/ })
			.first()
			.click();

		await expect(container.getByRole('textbox').last()).toHaveValue(
			/^\{Radio:1}\{entry_id}<img .+/
		);
	}

	async downloadAndVerifyPdf(pdfLink: Locator, expectedFilename: string) {
		await expect(pdfLink).toBeAttached();

		const downloadPromise = this.page.waitForEvent('download');
		await pdfLink.click();
		const download = await downloadPromise;

		// download the PDF and verify it's valid
		expect(download.suggestedFilename()).toBe(expectedFilename);
		const pdfStream = await download.createReadStream();

		async function readPdf(readable: Readable) {
			let data = '';
			for await (const chunk of readable) {
				data += chunk;
			}

			return data;
		}

		const pdfContent = await readPdf(pdfStream);
		expect(pdfContent.substring(0, 7)).toEqual(
			expect.stringContaining('%PDF-1.')
		);
		expect(pdfContent).toEqual(expect.stringContaining('startxref'));
		expect(pdfContent).toEqual(expect.stringContaining('%%EOF'));
	}

	async gotoPdfAndVerify(url: string, expectedFilename: string) {
		const downloadPromise = this.page.waitForEvent('download');

		try {
			await this.page.goto(url, { waitUntil: 'networkidle' });
		} catch (e) {}

		const download = await downloadPromise;

		// download the PDF and verify it's valid
		expect(download.suggestedFilename()).toBe(expectedFilename);
		const pdfStream = await download.createReadStream();

		async function readPdf(readable: Readable) {
			let data = '';
			for await (const chunk of readable) {
				data += chunk;
			}

			return data;
		}

		const pdfContent = await readPdf(pdfStream);
		expect(pdfContent.substring(0, 7)).toEqual(
			expect.stringContaining('%PDF-1.')
		);
		expect(pdfContent).toEqual(expect.stringContaining('startxref'));
		expect(pdfContent).toEqual(expect.stringContaining('%%EOF'));
	}
}
