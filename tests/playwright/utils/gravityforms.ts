import { readFile } from 'fs/promises';
import { URL } from 'node:url';
import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';

type Form = {
	id: number;
	title: string;
};

export default class GravityForms {
	private requestUtils: RequestUtils;
	private admin: Admin;
	private page: Page;

	constructor(requestUtils: RequestUtils, admin: Admin, page: Page) {
		this.requestUtils = requestUtils;
		this.admin = admin;
		this.page = page;
	}

	/*
	 * Form management
	 */
	async createForm(title: string) {
		const formJson: string = await readFile(
			__dirname + '/../data/forms/standard.json',
			'utf8'
		);

		const form: Form = JSON.parse(formJson);

		form.title = title;

		return await this.requestUtils.rest({
			method: 'POST',
			path: `/gf/v2/forms`,
			data: { ...form },
		});
	}

	async getFormIdByName(name: string): Promise<number> {
		await this.navigateToFormList();
		const search = this.page.getByRole('searchbox');
		await search.fill(name);
		await search.press('Enter');

		return Number.parseInt(
			await this.page.locator('.id > a').first().innerText()
		);
	}

	async navigateToFormList() {
		await this.admin.visitAdminPage('admin.php', 'page=gf_edit_forms');
	}

	async navigateToFormByName(name: string) {
		const id = await this.getFormIdByName(name);
		await this.navigateToFormById(id);
	}

	async navigateToFormById(formId: number) {
		await this.admin.visitAdminPage(
			'admin.php',
			'page=gf_edit_forms&id=' + formId
		);
	}

	async navigateToFormSettingsById(formId: number, subview = '') {
		await this.admin.visitAdminPage(
			'admin.php',
			`page=gf_edit_forms&view=settings&subview=settings&id=${formId}&subview=${subview}`
		);
	}

	async navigateToFormConfirmation(
		formId: number,
		label = 'Default Confirmation'
	) {
		await this.navigateToFormSettingsById(formId, 'confirmation');

		await this.page
			.locator('#the-list')
			.getByRole('link', { name: label + '  (Edit)', exact: true })
			.click();
	}

	async navigateToFormPdfList(formId: number) {
		await this.navigateToFormSettingsById(formId, 'PDF');
	}

	async navigateToFormPreview(formId: number) {
		await this.page.goto('/?gf_page=preview&id=' + formId);
	}

	/*
	 * PDF Management
	 */
	async createPdf(formId: number, label: string) {
		await this.navigateToNewFormPdf(formId);
		await this.page.getByLabel('Label').fill(label);
		await this.page.getByLabel('Filename').fill(label);
		await this.page
			.getByRole('button', { name: 'Add PDF' })
			.first()
			.click();

		// return PDF ID (@TODO update with the REST API settings once implemented)
		const pdfUrl = new URL(this.page.url());

		return pdfUrl.searchParams.get('pid');
	}

	async navigateToNewFormPdf(formId: number) {
		await this.navigateToFormPdfList(formId);
		await this.page.getByRole('link', { name: 'Add new PDF' }).click();
	}

	async copyDownloadShortcodeToClipboard(formId: number, pdfId: string) {
		await this.navigateToFormPdfList(formId);
		await this.page.locator(`#gfpdf-${pdfId}`).getByRole('dialog').click();
	}

	/*
	 * Settings management
	 */
	async setRichTextContent(containerSelector: string, content: string) {
		const container = await this.page.locator(containerSelector);

		// swap to code view
		container.getByRole('button', { name: 'Code' }).first().click();

		await container.getByRole('textbox').fill(content);
	}

	async saveForm() {
		await this.page.getByRole('button', { name: /(save|submit)/i }).click();
	}
}
