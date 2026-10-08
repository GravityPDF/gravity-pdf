import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('PDF shortcodes and merge tags in form fields', () => {
	let pdf: Pdf;
	let form: any;
	let pdfId: string;
	let formPage: any;

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
			form = await pdf.createForm('Form Field Tags');
			pdfId = await pdf.addPdf(form.id, 'Form Field Tags', {
				show_html: true,
			});

			await pdf.updateForm(form.id, (current: any) => ({
				fields: [
					...current.fields,
					{
						id: 50,
						formId: form.id,
						type: 'textarea',
						label: 'Administrative',
						visibility: 'administrative',
						defaultValue: `admin-tag={PDF:pdf:${pdfId}:signed}`,
					},
					{
						id: 51,
						formId: form.id,
						type: 'html',
						label: 'Block',
						content: `<p id="html-tag">{PDF:pdf:${pdfId}}</p>`,
					},
				],
			}));

			formPage = await requestUtils.createPage({
				title: `Form field tags ${form.id}`,
				content: `[gravityform id="${form.id}" ajax="false"]`,
				status: 'publish',
			});
		}
	);

	/**
	 * Submit the form as a logged out visitor, and return the entry and its PDF's HTML as an administrator sees it
	 * @param page
	 */
	async function submit(page: Page) {
		await page.context().clearCookies();
		await page.goto(formPage.link);
		await page.locator(`#input_${form.id}_5`).fill('Visitor');
		await pdf.submitForm();
		await expect(page.locator('.gform_confirmation_message')).toBeVisible();

		const entry = await pdf.getLatestEntry(form.id);

		return { entry, html: await pdf.getPdfDebugOutput(pdfId, entry.id) };
	}

	function url(html: string, name: string) {
		return (
			html
				.match(new RegExp(`${name}=([^\\s<]+)`))?.[1]
				?.replaceAll('&amp;', '&') ?? ''
		);
	}

	test('keeps a PDF merge tag in an administrative default, and signs it in the PDF', async ({
		page,
	}: {
		page: Page;
	}) => {
		const { entry, html } = await submit(page);

		expect(entry['50']).toContain(`admin-tag={PDF:pdf:${pdfId}:signed}`);
		const link = url(html, 'admin-tag');
		expect(link).toContain(`/${entry.id}/`);
		expect(link).toContain('signature=');
	});

	test('does not show a PDF merge tag on the form, where there is no entry', async ({
		page,
	}: {
		page: Page;
	}) => {
		await page.context().clearCookies();
		await page.goto(formPage.link);

		await expect(page.locator('#html-tag')).toBeAttached();
		await expect(page.locator('#html-tag')).toHaveText('');
	});
});
