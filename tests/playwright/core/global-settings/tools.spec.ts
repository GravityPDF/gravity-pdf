import type { Admin } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';

test.describe('Tools Tab', () => {
	test.describe('Install Core Fonts', () => {
		test('should no longer offer a core font installer', async ({
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

			// 7.0 bundles the fonts it needs, so there is nothing to download
			await expect(
				page.locator('#gfpdf-fieldset-install_core_fonts')
			).toHaveCount(0);

			await expect(
				page.locator('legend', { hasText: 'Install Core Fonts' })
			).toHaveCount(0);
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
