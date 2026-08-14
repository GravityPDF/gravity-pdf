import type { Admin } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import { snapshot } from '@self:playwright/utils/snapshot';

test.describe('License Tab', () => {
	test('should display License field information', async ({
		page,
		admin,
	}: {
		page: Page;
		admin: Admin;
	}) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=gf_settings&subview=PDF&tab=license'
		);

		await expect(
			page.locator('legend', { hasText: 'Licensing' })
		).toBeVisible();
		await expect(page.locator('[name="submit"]')).toBeVisible();
	});

	test.describe('Sample Plugin License', () => {
		test('should display error message for invalid license key', async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=gf_settings&subview=PDF&tab=license'
			);

			const input = page
				.locator('#gfpdf-fieldset-license_gravity-pdf-example-plugin')
				.locator(
					'[id="gfpdf_settings[license_gravity-pdf-example-plugin]"]'
				);
			await input.clear();
			await input.fill('123456789');
			await page.locator('[name="submit"]').click();

			await expect(
				page.locator('.gforms_note_error', {
					hasText:
						'This license key is invalid. Please check your key has been entered correctly.',
				})
			).toBeVisible();
			await expect(
				page.getByRole('button', { name: 'Deactivate License' })
			).not.toBeVisible();
		});

		test('should display success message and deactivation option for active license key', async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=gf_settings&subview=PDF&tab=license'
			);

			const input = page
				.locator('#gfpdf-fieldset-license_gravity-pdf-example-plugin')
				.locator(
					'[id="gfpdf_settings[license_gravity-pdf-example-plugin]"]'
				);
			await input.clear();
			await input.fill('987654321');
			await page.locator('[name="submit"]').click();

			await expect(
				page.locator('.gforms_note_success', {
					hasText:
						'Your support license key has been activated for this domain.',
				})
			).toBeVisible();
			await expect(
				page.getByRole('button', { name: 'Deactivate License' })
			).toBeVisible();
		});

		test('should deactivate license and display deactivated message', async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}, testinfo) => {
			await admin.visitAdminPage(
				'admin.php',
				'page=gf_settings&subview=PDF&tab=license'
			);

			const input = page
				.locator('#gfpdf-fieldset-license_gravity-pdf-example-plugin')
				.locator(
					'[id="gfpdf_settings[license_gravity-pdf-example-plugin]"]'
				);
			await input.clear();
			await input.fill('987654321');
			await page.locator('[name="submit"]').click();

			await page.waitForTimeout(1000);
			await snapshot(page, testinfo);

			await page
				.getByRole('button', { name: 'Deactivate License' })
				.click();

			await expect(
				page.getByText('License key deactivated.')
			).toBeVisible({ timeout: 30000 });
		});
	});
});
