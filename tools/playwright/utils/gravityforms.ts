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
	date_created?: string;
};

/**
 * A `date_created` for entries whose creation date ends up in a Chromatic snapshot
 *
 * Gravity Forms stamps a new entry with the current UTC time and the entry list renders it to the minute, so a
 * snapshot of that screen differs on every run no matter how long the test waits. GFAPI takes this verbatim when
 * it is supplied. Pass it only where the date is on screen: an entry is also what Gravity PDF times anonymous
 * access from, so backdating one withdraws access a logged out visitor is supposed to still have.
 */
export const FIXED_ENTRY_DATE = '2020-01-01 00:00:00';

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
	/**
	 * Merge `patch` into a form over the REST API
	 *
	 * The endpoint replaces the whole form, so the current one is read back first. Used for settings the admin UI
	 * doesn't offer, and for those it does when the point of the test is elsewhere.
	 */
	async updateForm(formId: number, patch: object) {
		const form: object = await this.requestUtils.rest({
			path: `/gf/v2/forms/${formId}`,
		});

		return await this.requestUtils.rest({
			method: 'PUT',
			path: `/gf/v2/forms/${formId}`,
			data: { ...form, ...patch },
		});
	}

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

		// The caller may have only just clicked through to this screen. `switchToCodeEditor()` cannot stand in
		// for the wait: with nothing rendered yet it finds no editor to switch and returns at once, leaving the
		// fill below to time out on a textarea that had not arrived.
		await container.locator('.wp-editor-wrap').waitFor();

		await this.switchToCodeEditor();

		const textbox = container.getByRole('textbox').last();

		if (append) {
			content = (await textbox.inputValue()) + content;
		}

		await textbox.fill(content);

		await this.page.waitForTimeout(500);

		expect(await textbox.inputValue()).toEqual(content);
	}

	async switchToCodeEditor() {
		// The Code tab hands off to WordPress' `switchEditor()`, which calls `editor.hide()` and copies the
		// TinyMCE iframe's height onto the textarea it reveals. Both need an initialised editor: clicking early
		// throws out of `hide()` before the class swap below it, leaving the editor in visual mode with its
		// textarea still hidden, and the height it copies is whatever the iframe had reached by then — which is
		// what moves the box between snapshots. The images inside count too, since they are what the editor
		// sizes itself around.
		await this.page.waitForFunction(
			() => {
				const tinymce = (window as any).tinymce;

				return Array.from(
					document.querySelectorAll('.wp-editor-wrap.tmce-active')
				)
					.filter((wrap) => (wrap as HTMLElement).offsetParent)
					.every((wrap) => {
						const editor = tinymce?.get?.(
							wrap.id.replace(/^wp-/, '').replace(/-wrap$/, '')
						);

						if (!editor?.initialized) {
							return false;
						}

						const doc = editor.getDoc?.();

						return (
							!doc ||
							Array.from(doc.images).every(
								(image: any) => image.complete
							)
						);
					});
			},
			undefined,
			{ timeout: 15000 }
		);

		for (const button of await this.page
			.locator('.wp-editor-tabs')
			.getByRole('button', { name: /^Code$/ })
			.filter({ visible: true })
			.all()) {
			await button.click();
		}
	}

	async submitForm() {
		await this.page
			.getByRole('button', { name: /(save|submit)/i })
			.last()
			.click();
	}
}
