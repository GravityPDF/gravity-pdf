import type { Admin } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import { snapshot } from '@self:playwright/utils/snapshot';

test.describe('Settings Tab', () => {
	test.describe('PDF Cache', () => {
		test('should render the section open', async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=gf_settings&subview=PDF&tab=general'
			);

			const section = page.locator(
				'#gfpdf-fieldset-gfpdf_settings_general_cache'
			);

			await expect(section).not.toHaveClass(
				/gform-settings-panel--collapsed/
			);
			await expect(section.getByLabel('Cache PDFs')).toBeVisible();
			await expect(
				section.getByRole('button', { name: 'Clear PDF Cache' })
			).toBeVisible();
			await expect(section.getByLabel('Cache Duration')).toBeVisible();
		});

		// A real clear moves the site-wide cache generation under the other workers, so the request is answered here
		test('should clear the cache without saving the form', async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}, testinfo) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=gf_settings&subview=PDF&tab=general'
			);

			let posts = 0;
			await page.route('**/wp-admin/admin-ajax.php', async (route) => {
				const postData = route.request().postData() ?? '';
				if (postData.includes('action=gfpdf_clear_pdf_cache')) {
					posts++;
					await route.fulfill({
						status: 200,
						contentType: 'application/json',
						body: JSON.stringify({
							success:
								'Cached PDFs cleared. Old files are being removed in the background.',
						}),
					});
				} else {
					await route.continue();
				}
			});

			const url = page.url();
			const section = page.locator(
				'#gfpdf-fieldset-gfpdf_settings_general_cache'
			);

			await section
				.getByRole('button', { name: 'Clear PDF Cache' })
				.click();

			const notice = section.locator('.gfpdf-clear-pdf-cache-notice');
			await expect(notice).toHaveText(
				'Cached PDFs cleared. Old files are being removed in the background.'
			);
			await expect(
				section.getByRole('button', { name: 'Clear PDF Cache' })
			).toBeEnabled();

			expect(posts).toBe(1);
			expect(page.url()).toBe(url);
			await expect(
				page.getByText('Settings updated.', { exact: true })
			).toHaveCount(0);

			await snapshot(page, testinfo, section);
		});
	});
});
