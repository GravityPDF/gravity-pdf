import type { Admin } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import { snapshot } from '@self:playwright/utils/snapshot';

const openGeneralTab = (admin: Admin) =>
	admin.visitAdminPage(
		'admin.php',
		'page=gf_settings&subview=PDF&tab=general'
	);

const cacheSection = (page: Page) =>
	page.locator('#gfpdf-fieldset-gfpdf_settings_general_cache');

test.describe('Settings Tab', () => {
	test.describe('PDF Cache', () => {
		test('should show Clear Cache beside the toggle only while the cache is on', async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}) => {
			await openGeneralTab(admin);

			const url = page.url();
			const section = cacheSection(page);
			const toggle = section.locator('#gfpdf_settings\\[pdf_cache\\]');
			const button = section.getByRole('button', { name: 'Clear Cache' });

			await expect(section).not.toHaveClass(
				/gform-settings-panel--collapsed/
			);
			await expect(toggle).toBeChecked();
			await expect(button).toBeVisible();
			await expect(section.getByLabel('Cache Duration')).toBeVisible();

			const [switchBox, buttonBox, sectionBox, toggleRow, durationRow] =
				await Promise.all([
					section
						.locator('.gform-field__toggle-container')
						.boundingBox(),
					button.boundingBox(),
					section.boundingBox(),
					section
						.locator('#gfpdf-settings-field-wrapper-pdf_cache')
						.boundingBox(),
					section
						.locator('#gfpdf-settings-field-wrapper-cache_duration')
						.boundingBox(),
				]);

			// To the right of the switch, on the same line
			expect(buttonBox.x).toBeGreaterThan(switchBox.x + switchBox.width);
			expect(buttonBox.y).toBeLessThan(switchBox.y + switchBox.height);
			expect(buttonBox.y + buttonBox.height).toBeGreaterThan(switchBox.y);

			// Both settings span the section rather than sitting in half-width columns
			expect(toggleRow.width).toBeGreaterThan(sectionBox.width * 0.75);
			expect(durationRow.width).toBeGreaterThan(sectionBox.width * 0.75);

			await toggle.uncheck();
			await expect(button).toBeHidden();

			await toggle.check();
			await expect(button).toBeVisible();

			expect(page.url()).toBe(url);
		});

		// A real clear moves the site-wide cache generation under the other workers, so the request is answered here
		test('should clear the cache without saving the form', async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}, testinfo) => {
			await openGeneralTab(admin);

			let posts = 0;
			await page.route('**/wp-admin/admin-ajax.php', async (route) => {
				const postData = route.request().postData() ?? '';
				if (postData.includes('action=gfpdf_clear_pdf_cache')) {
					posts++;
					await route.fulfill({
						status: 200,
						contentType: 'application/json',
						body: JSON.stringify({ success: 'PDF cache cleared.' }),
					});
				} else {
					await route.continue();
				}
			});

			const url = page.url();
			const section = cacheSection(page);
			const button = section.getByRole('button', { name: 'Clear Cache' });

			await button.click();

			await expect(
				section.locator('.gfpdf-clear-pdf-cache-notice')
			).toHaveText('PDF cache cleared.');
			await expect(button).toBeEnabled();

			expect(posts).toBe(1);
			expect(page.url()).toBe(url);
			await expect(
				page.getByText('Settings updated.', { exact: true })
			).toHaveCount(0);

			await snapshot(page, testinfo, section);
		});
	});
});
