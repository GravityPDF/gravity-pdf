import type { Admin } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test, expect } from '@self:playwright/fixtures/test';
import { takeSnapshot } from '@chromatic-com/playwright';

test.describe('Tools Tab', () => {
	test.describe('Install Core Fonts', () => {
		test("should display 'Install Core Fonts' field", async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=gf_settings&subview=PDF&tab=tools'
			);

			await expect(
				page.locator('legend', { hasText: 'Install Core Fonts' })
			).toBeVisible();

			await expect(
				page
					.locator('#gfpdf-fieldset-install_core_fonts')
					.getByRole('button', { name: 'Download Core Fonts' })
			).toBeVisible();
		});

		test('should return download core fonts successful response', async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}, testinfo) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=gf_settings&subview=PDF&tab=tools'
			);

			await page.route('**/wp-admin/admin-ajax.php*', async (route) => {
				const postData = route.request().postData() ?? '';
				if (postData.includes('gfpdf_save_core_font')) {
					await route.fulfill({
						status: 200,
						contentType: 'application/json',
						body: 'true',
					});
				} else {
					await route.continue();
				}
			});

			await page
				.locator('#gfpdf-fieldset-install_core_fonts')
				.getByRole('button', { name: 'Download Core Fonts' })
				.click();

			await expect(
				page.locator('.gfpdf-core-font-status-success', {
					hasText: 'ALL CORE FONTS SUCCESSFULLY INSTALLED',
				})
			).toBeVisible({ timeout: 60000 });

			await takeSnapshot(page, testinfo);
		});

		test('should return download core fonts error/failed response', async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=gf_settings&subview=PDF&tab=tools'
			);

			await page
				.locator('#gfpdf-fieldset-install_core_fonts')
				.getByRole('button', { name: 'Download Core Fonts' })
				.click();

			await expect(
				page.locator('.gfpdf-core-font-status-error').first()
			).toBeVisible({ timeout: 15000 });
			await expect(
				page.locator('.gfpdf-core-font-retry-link')
			).toBeVisible();
		});
	});

	test.describe('Uninstall Gravity PDF', () => {
		test("should display 'Uninstall Gravity PDF' field", async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=gf_settings&subview=uninstall'
			);

			await expect(
				page.locator('.addon-uninstall-text h4', {
					hasText: 'Gravity PDF',
				})
			).toBeVisible();
			await expect(page.locator('.addon-uninstall-text')).toContainText(
				'This operation deletes ALL Gravity PDF settings.'
			);
			await expect(
				page
					.locator('.addon-uninstall-button')
					.getByRole('button', { name: 'Uninstall' })
			).toBeVisible();
		});
	});
});
