import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Locator } from '@playwright/test';
import type { Readable } from 'stream';
import { URL } from 'node:url';
import GravityForms from '@self:playwright/utils/gravityforms';

export default class Pdf extends GravityForms {
	async navigateToGlobalPdfSettings() {
		await this.admin.visitAdminPage(
			'admin.php',
			'page=gf_settings&subview=PDF'
		);
	}

	async setGlobalPdfSetting(label: string, value: any) {
		await this.navigateToGlobalPdfSettings();

		const setting = this.page.getByLabel(label).first();

		switch (await setting.getAttribute('type')) {
			case 'checkbox':
				// eslint-disable-next-line no-unused-expressions
				value ? await setting.check() : await setting.uncheck();
				break;

			case 'radio':
				// eslint-disable-next-line no-unused-expressions
				value ? await setting.check() : await setting.uncheck();
		}

		await this.page.getByRole('button', { name: 'Save Settings' }).click();
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

	async createPdf(formId: number, label: string) {
		await this.navigateToNewFormPdf(formId);
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
		).toBeVisible();
	}

	async navigateToNewFormPdf(formId: number) {
		await this.navigateToFormPdfList(formId);
		await this.page.getByRole('link', { name: 'Add new PDF' }).click();
		await expect(
			this.page.getByRole('button', { name: 'Manage PDF Templates' })
		).toBeVisible();
	}

	async navigateToFormPdf(formId: number, pdfId: string) {
		await this.admin.visitAdminPage(
			'admin.php',
			`page=gf_edit_forms&view=settings&subview=PDF&id=${formId}&pid=${pdfId}`
		);
		await expect(
			this.page.getByRole('button', { name: 'Manage PDF Templates' })
		).toBeVisible();
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

		await expect(container).toHaveScreenshot();

		// Code View
		await container
			.getByRole('button', { name: /^Code$/ })
			.first()
			.click();

		await expect(container.getByRole('textbox').last()).toHaveValue(
			/^\{Radio:1}\{entry_id}<img .+/
		);

		await expect(container).toHaveScreenshot();
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
		} catch (e) {
			console.log(e);
		}

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
