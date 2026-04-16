import { readFile } from 'fs/promises';
import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { expect } from '@playwright/test';

type Form = {
	id: number;
	title: string;
};

type Entry = {
	form_id: number;
	created_by?: number;
	ip?: string;
};

export default class GravityForms {
	protected requestUtils: RequestUtils;
	protected admin: Admin;
	protected page: Page;

	constructor(requestUtils: RequestUtils, admin: Admin, page: Page) {
		this.requestUtils = requestUtils;
		this.admin = admin;
		this.page = page;
	}

	/*
	 * Form management
	 */
	async createForm(title: string, type: string = 'standard.json') {
		const formJson: string = await readFile(
			__dirname + '/../data/forms/' + type,
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

	async createEntry(entry: Entry) {
		return await this.requestUtils.rest({
			method: 'POST',
			path: `/gf/v2/entries`,
			data: { ...entry },
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

	async saveFormEditor() {
		await this.page.getByRole('button', { name: 'Save Form' }).click();
		await this.page.waitForTimeout(3500);
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

	async navigateToEntryList(formId: number) {
		await this.admin.visitAdminPage(
			'admin.php',
			`page=gf_entries&id=${formId}`
		);
	}

	async navigateToEntryDetail(formId: number, entryId: number) {
		await this.admin.visitAdminPage(
			'admin.php',
			`page=gf_entries&view=entry&id=${formId}&lid=${entryId}`
		);
	}

	async navigateToFormPreview(formId: number) {
		await this.page.goto('/?gf_page=preview&id=' + formId);
	}

	/*
	 * Settings management
	 */
	async addNotification(formId: number, name: string) {
		await this.admin.visitAdminPage(
			'admin.php',
			`subview=notification&page=gf_edit_forms&view=settings&id=${formId}`
		);

		await this.page.getByRole('link', { name: 'Add New' }).click();

		await this.page
			.getByRole('textbox', { name: 'Name' })
			.first()
			.fill(name);
		await this.page
			.getByRole('textbox', { name: 'Send To Email' })
			.fill('hi@example.com');
		await this.page
			.getByRole('textbox', { name: 'Subject' })
			.fill('Subject');
		await this.setRichTextContent('#gform_setting_message', 'Message');

		await this.page
			.getByRole('button', { name: 'Update Notification' })
			.click();
	}

	async setRichTextContent(
		containerSelector: string,
		content: string,
		append = false
	) {
		const container = await this.page.locator(containerSelector);

		// swap to code view
		container
			.getByRole('button', { name: /^Code$/ })
			.first()
			.click();

		const textbox = await container.getByRole('textbox').last();

		if (append) {
			content = (await textbox.inputValue()) + content;
		}

		await textbox.fill(content);

		await this.page.waitForTimeout(500);

		expect(await textbox.inputValue()).toEqual(content);
	}

	async submitForm() {
		await this.page
			.getByRole('button', { name: /(save|submit)/i })
			.last()
			.click();
	}
}
