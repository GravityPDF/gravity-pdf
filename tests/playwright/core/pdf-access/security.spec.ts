import * as crypto from 'node:crypto';
import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('PDF Security and Access Policies', () => {
	let pdf: Pdf;
	let form: any;

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
			form = await pdf.createForm('Restriction Test');
		}
	);

	test('should download a PDF for an anonymous user with the same IP', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const pdfId = await pdf.createPdf(form.id, 'Accessible PDF');
		const entry = await pdf.createEntry({
			form_id: form.id,
		});

		// Visit as anonymous user
		await admin.context.clearCookies();
		await pdf.gotoPdfAndVerify(
			`/?gpdf=1&pid=${pdfId}&lid=${entry.id}`,
			'Accessible PDF.pdf'
		);
	});

	test('should show the login page when an anonymous user with a different IP tries to access a PDF', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const pdfId = await pdf.createPdf(form.id, 'Restricted PDF');
		const entry = await pdf.createEntry({
			form_id: form.id,
			ip: '10.0.0.1',
		});

		// Visit as anonymous user
		await admin.context.clearCookies();
		await page.goto(`/?gpdf=1&pid=${pdfId}&lid=${entry.id}`);

		// Should redirect to login
		await expect(
			page.getByRole('button', { name: 'Log In' })
		).toBeVisible();
	});

	test('should show error if "Restrict Owner" is enabled and visiting as that user', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const userId = crypto.randomBytes(20).toString('hex');
		const user = await requestUtils.createUser({
			username: userId,
			email: `${userId}@example.com`,
			password: '123456',
		});

		await pdf.navigateToNewFormPdf(form.id);
		const pdfId = await pdf.createPdf(form.id, 'Restrict Owner');

		await pdf.navigateToFormPdf(form.id, pdfId);
		await pdf.checkField('Restrict Owner', true);
		await pdf.addOrUpdatePdf();

		const entry = await pdf.createEntry({
			form_id: form.id,
			created_by: user.id,
		});

		// Log in as the entry creator user
		await admin.context.clearCookies();
		await page.goto('/wp-login.php');
		await page.getByLabel('Username or Email Address').fill(userId);
		await page.getByLabel('Password', { exact: true }).fill('123456');
		await page.getByRole('button', { name: 'Log In' }).click();

		await page.goto(`/?gpdf=1&pid=${pdfId}&lid=${entry.id}`);

		// Should show access denied error
		await expect(
			page.getByText('You do not have access to view this PDF.')
		).toBeVisible();
	});

	test('should download PDF when "Restrict Owner" is enabled and downloading as current user', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		const userPass = crypto.randomBytes(20).toString('hex');
		const user = await requestUtils.createUser({
			username: crypto.randomBytes(20).toString('hex'),
			email: crypto.randomBytes(20).toString('hex') + '@example.com',
			password: userPass,
		});

		await pdf.navigateToNewFormPdf(form.id);
		const pdfId = await pdf.createPdf(form.id, 'Restrict Owner');

		await pdf.navigateToFormPdf(form.id, pdfId);
		await pdf.checkField('Restrict Owner', true);
		await pdf.addOrUpdatePdf();

		const entry = await pdf.createEntry({
			form_id: form.id,
			created_by: user.id,
		});

		// Visit the PDF link as anon (clear cookies to simulate anonymous user)
		await page.context().clearCookies();
		await page.goto(`/?gpdf=1&pid=${pdfId}&lid=${entry.id}`);

		// Should redirect to login
		await expect(page.locator('#login form')).toBeVisible();
	});

	test('should allow access to a public PDF for anonymous users', async ({
		requestUtils,
		page,
		admin,
	}: {
		requestUtils: RequestUtils;
		page: Page;
		admin: Admin;
	}) => {
		await pdf.navigateToNewFormPdf(form.id);
		const pdfId = await pdf.createPdf(form.id, 'Public PDF');

		await pdf.navigateToFormPdf(form.id, pdfId);
		await pdf.checkField('Enable Public Access', true);
		await pdf.addOrUpdatePdf();

		const entry = await pdf.createEntry({ form_id: form.id });

		// Visit as anonymous user
		await page.context().clearCookies();
		await pdf.gotoPdfAndVerify(
			`/?gpdf=1&pid=${pdfId}&lid=${entry.id}`,
			'Public PDF.pdf'
		);
	});
});
