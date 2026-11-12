import { expect } from '@wordpress/e2e-test-utils-playwright';
import { test } from '@self:playwright/fixtures/test';
import {
	SUBSITE,
	logInToSubsite as logIn,
} from '@self:playwright/utils/multisite';
import { failedFontsNotice as notice } from '@self:playwright/utils/notices';
import { snapshot } from '@self:playwright/utils/snapshot';
import { wpCli } from '@self:playwright/utils/wp-cli';

const MAIN_SITE = '/wp-admin/index.php';
const SUBSITE_ADMIN = `/${SUBSITE}/wp-admin/index.php`;
const NETWORK_ADMIN = '/wp-admin/network/index.php';

const gaveUp = { retry_at: 0, gave_up: 1, dismissed: false };

// The real record every site reads, as base64 because wpCli() runs its commands inside double quotes
const saveFailures = (failures: Record<string, object>) =>
	wpCli(
		`echo ${Buffer.from(JSON.stringify(failures)).toString('base64')} | base64 -d | wp site option update gfpdf_core_font_failures --format=json --quiet`
	);

test.describe('Failed core fonts notice on a Multisite', () => {
	// Every site shares the one record, and each test builds on the state the one before left it in
	test.describe.configure({ mode: 'serial' });

	test.beforeAll(() =>
		saveFailures({
			'FreeSerif.ttf': gaveUp,
			'Sun-ExtB.ttf': gaveUp,
			// Still waiting on its retry, so not listed
			'Garuda.ttf': {
				retry_at: 4102444800,
				gave_up: 0,
				dismissed: false,
			},
		})
	);

	test.afterAll(() =>
		wpCli('wp site option delete gfpdf_core_font_failures --quiet')
	);

	test('should list the fonts that gave up on every site and in the network admin', async ({
		page,
	}, testinfo) => {
		const failed = notice(page);

		for (const url of [MAIN_SITE, SUBSITE_ADMIN, NETWORK_ADMIN]) {
			await page.goto(url);
			await expect(failed.locator('li code')).toHaveText([
				'FreeSerif.ttf',
				'Sun-ExtB.ttf',
			]);
		}

		await expect(
			failed.getByRole('button', { name: 'Dismiss Notice' })
		).toBeVisible();

		await snapshot(page, testinfo, failed);
	});

	test('should open the system report from the notice in the network admin', async ({
		page,
	}) => {
		await page.goto(NETWORK_ADMIN);
		await notice(page)
			.getByRole('link', { name: 'view the system report' })
			.click();

		await expect(page).toHaveURL(/admin\.php\?page=gf_system_status/);
		await expect(
			page.getByRole('row', { name: /^Core Fonts installed/ })
		).toBeVisible();
	});

	test('should not show the notice to a subsite user who cannot edit the settings', async ({
		browser,
	}) => {
		const page = await logIn(browser, 'siteeditor');

		await expect(page.locator('#wpbody-content h1')).toBeVisible();
		await expect(notice(page)).toHaveCount(0);

		await page.context().close();
	});

	test('should hide the notice everywhere once a subsite administrator dismisses it', async ({
		browser,
		page,
	}) => {
		const siteAdmin = await logIn(browser, 'siteadmin');

		await notice(siteAdmin)
			.getByRole('button', { name: 'Dismiss Notice' })
			.click();
		await expect(notice(siteAdmin)).toHaveCount(0);

		await siteAdmin.context().close();

		for (const url of [MAIN_SITE, NETWORK_ADMIN]) {
			await page.goto(url);
			await expect(page.locator('#wpbody-content h1')).toBeVisible();
			await expect(notice(page)).toHaveCount(0);
		}
	});

	test('should raise the notice again for only the font that fails after a dismissal', async ({
		page,
	}) => {
		saveFailures({
			'FreeSerif.ttf': { ...gaveUp, dismissed: true },
			'Sun-ExtB.ttf': { ...gaveUp, dismissed: true },
			'Garuda.ttf': gaveUp,
		});

		for (const url of [MAIN_SITE, SUBSITE_ADMIN, NETWORK_ADMIN]) {
			await page.goto(url);
			await expect(notice(page).locator('li code')).toHaveText([
				'Garuda.ttf',
			]);
		}
	});
});
