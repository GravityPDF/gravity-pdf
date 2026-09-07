import type { Admin } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';

test.describe('Tools Tab', () => {
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
