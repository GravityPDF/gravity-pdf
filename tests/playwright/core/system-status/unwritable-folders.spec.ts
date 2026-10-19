import type { Admin } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import { snapshot } from '@self:playwright/utils/snapshot';
import { wpCli } from '@self:playwright/utils/wp-cli';

// Matches the folder tools/mu-plugins makes read-only for requests sending the header below
const READ_ONLY_FOLDER = '/tmp/gfpdf-e2e-read-only/';

// Anchored, so "Temporary Folder permissions" can't match
const folderRow = (page: Page) =>
	page.getByRole('row', { name: /^Folder permissions/ });

const notice = (page: Page) =>
	page.locator('.notice.notice-error', {
		hasText: 'Gravity PDF cannot create or write to these directories.',
	});

test.describe('Unwritable Folders', () => {
	// Each test builds on the one before: the report stores the failure, the notice shows it, then it's dismissed
	test.describe.configure({ mode: 'serial' });

	// Clears the failures and dismissals stored by an earlier run (see tools/mu-plugins)
	test.beforeAll(() =>
		wpCli(
			'wp option delete gfpdf_e2e_unwritable_folders gfpdf_e2e_action_dismissal --quiet || true'
		)
	);

	// Keeps the read-only folder and its notice out of the specs running alongside (see tools/mu-plugins)
	test.beforeEach(async ({ page }: { page: Page }) => {
		await page.setExtraHTTPHeaders({
			'X-GPDF-E2E-Unwritable-Folders': 'yes',
		});
	});

	test('should list each unwritable folder in the system report', async ({
		page,
		admin,
	}: {
		page: Page;
		admin: Admin;
	}, testinfo) => {
		await admin.visitAdminPage('admin.php', 'page=gf_system_status');

		const row = folderRow(page);

		await expect(row).toContainText('Not writable');
		await expect(row.locator('code')).toHaveText([READ_ONLY_FOLDER]);

		await snapshot(page, testinfo, row);
	});

	test('should raise a dismissible notice listing each unwritable folder', async ({
		page,
		admin,
	}: {
		page: Page;
		admin: Admin;
	}, testinfo) => {
		await admin.visitAdminPage('index.php');

		const unwritable = notice(page);

		await expect(unwritable).toHaveCount(1);
		await expect(unwritable.locator('li code')).toHaveText([
			READ_ONLY_FOLDER,
		]);

		// Gravity Forms resets list bullets, so the notice sets them per item
		await expect(unwritable.locator('li').first()).toHaveCSS(
			'list-style-type',
			'disc'
		);

		await expect(
			unwritable.getByRole('button', { name: 'View the system report' })
		).toBeVisible();
		await expect(
			unwritable.getByRole('button', { name: 'Dismiss Notice' })
		).toBeVisible();

		await snapshot(page, testinfo, unwritable);
	});

	test('should stay dismissed while the folder keeps failing', async ({
		page,
		admin,
	}: {
		page: Page;
		admin: Admin;
	}) => {
		await admin.visitAdminPage('index.php');

		await notice(page)
			.getByRole('button', { name: 'Dismiss Notice' })
			.click();
		await expect(notice(page)).toHaveCount(0);

		// Building the report checks the folders again
		await admin.visitAdminPage('admin.php', 'page=gf_system_status');
		await expect(folderRow(page)).toContainText('Not writable');

		await admin.visitAdminPage('index.php');
		await expect(notice(page)).toHaveCount(0);
	});
});
