import * as crypto from 'node:crypto';
import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { withConfirmation } from '@self:playwright/utils/gravityforms';

test.describe('Signed PDF URLs', () => {
	let pdf: Pdf;
	let form: any;
	let pdfId: string;
	let entry: any;

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
			form = await pdf.createForm('Signed URL');
			pdfId = await pdf.addPdf(form.id, 'Signed URL');

			// from another IP, so an anonymous visitor can only reach its PDF with a signed URL
			entry = await pdf.createEntry({ form_id: form.id, ip: '10.0.0.1' });
		}
	);

	const shortcode = (entryId?: number) =>
		`[gravitypdf id="${pdfId}"${entryId ? ` entry="${entryId}"` : ''} signed="1" raw="1"]`;

	test('signs the link an administrator publishes', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const post = await requestUtils.createPage({
			title: 'Signed URL by an administrator',
			content: `<code id="pdf-url">${shortcode(entry.id)}</code>`,
			status: 'publish',
		});

		await admin.context.clearCookies();
		await page.goto(post.link);
		const url = await page.locator('#pdf-url').innerText();

		expect(url).toContain('signature=');
		await pdf.gotoPdfAndVerify(url, 'Signed URL.pdf');
	});

	test("does not sign the link an author who can't view the entry publishes", async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const username = crypto.randomBytes(10).toString('hex');
		const contributor = await requestUtils.createUser({
			username,
			email: `${username}@example.com`,
			password: crypto.randomBytes(10).toString('hex'),
			roles: ['contributor'],
		});

		const post: any = await requestUtils.rest({
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				title: 'Signed URL by a contributor',
				content: `<code id="pdf-url">${shortcode(entry.id)}</code>`,
				status: 'publish',
				author: contributor.id,
			},
		});

		// signing checks the post author, whoever views the post
		await page.goto(post.link);
		expect(await page.locator('#pdf-url').innerText()).not.toContain(
			'signature='
		);

		await admin.context.clearCookies();
		await page.goto(post.link);
		const url = await page.locator('#pdf-url').innerText();

		expect(url).toContain(pdfId);
		expect(url).not.toContain('signature=');

		await page.goto(url);
		await expect(
			page.getByRole('button', { name: 'Log In' })
		).toBeVisible();
	});

	test('signs the links in a form confirmation for a logged out submitter', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.updateForm(
			form.id,
			withConfirmation({
				type: 'message',
				message: `<code id="shortcode-url">${shortcode()}</code><code id="mergetag-url">{PDF:pdf:${pdfId}:signed}</code>`,
			})
		);

		const formPage = await requestUtils.createPage({
			title: 'Signed URL confirmation',
			content: `[gravityform id="${form.id}" ajax="false"]`,
			status: 'publish',
		});

		await admin.context.clearCookies();
		await page.goto(formPage.link);
		await pdf.submitForm();

		await expect(page.locator('#shortcode-url')).toContainText(
			'signature='
		);
		await expect(page.locator('#mergetag-url')).toContainText('signature=');
	});
});
