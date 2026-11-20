import type { Admin } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import { failedFontsNotice as notice } from '@self:playwright/utils/notices';
import { snapshot } from '@self:playwright/utils/snapshot';
import { wpCli } from '@self:playwright/utils/wp-cli';

// Matches the failures tools/mu-plugins reports for requests sending the header below
const FAILED_FONTS = ['FreeSerif.ttf', 'Sun-ExtB.ttf'];

// The fonts in FAILED_FONTS fail for the requests sending this, having given up at `timestamp` (see tools/mu-plugins)
const giveUpAt = (page: Page, timestamp: number) =>
	page.setExtraHTTPHeaders({ 'X-GPDF-E2E-Failed-Fonts': String(timestamp) });

test.describe('Core Fonts', () => {
	test('should report the installed core fonts and substitution fonts in the system report', async ({
		page,
		admin,
	}: {
		page: Page;
		admin: Admin;
	}, testinfo) => {
		await admin.visitAdminPage('admin.php', 'page=gf_system_status');

		const installed = page.getByRole('row', {
			name: /^Core Fonts installed/,
		});
		const substitution = page.getByRole('row', {
			name: /^Substitution Fonts/,
		});

		// Core font downloads are off on this site (see tools/mu-plugins), and the forms the other specs create decide how many are required
		await expect(installed).toContainText(
			/core: 0 of \d+ required files installed/
		);

		// The bundled fonts and the core ones mPDF loads only when they have the characters
		await expect(substitution).toContainText(
			'arimo, gfpdf-dejavu-symbols, dejavusanscondensed, freesans, garuda, khmeros, padaukbook, kaputaunicode, abyssinicasil, sun-exta'
		);

		await expect(
			page.getByRole('row', { name: /^Core Font downloads/ })
		).toContainText('Off (gfpdf_auto_install_fonts filter)');

		await snapshot(page, testinfo, [installed, substitution], {
			mask: [installed.locator('td').last()],
		});
	});

	test.describe('Failed downloads', () => {
		// Each test builds on the one before: the notice shows, is dismissed, then a later failure raises it again
		test.describe.configure({ mode: 'serial' });

		// Clears the failures an earlier run saved (see tools/mu-plugins)
		test.beforeAll(() =>
			wpCli('wp option delete gfpdf_e2e_failed_fonts --quiet || true')
		);

		// Keeps the notice out of the specs running alongside (see tools/mu-plugins)
		test.beforeEach(async ({ page }: { page: Page }) => {
			await giveUpAt(page, 1);
		});

		test('should raise a dismissible notice listing each font that failed to download', async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}, testinfo) => {
			await admin.visitAdminPage('index.php');

			const failed = notice(page);

			await expect(failed).toHaveCount(1);
			await expect(failed.locator('li code')).toHaveText(FAILED_FONTS);
			await expect(
				failed.getByRole('link', { name: 'install the fonts by hand' })
			).toHaveAttribute(
				'href',
				'https://docs.gravitypdf.com/users/core-pdf-fonts'
			);
			await expect(
				failed.getByRole('link', { name: 'view the system report' })
			).toHaveAttribute(
				'href',
				/admin\.php\?page=gf_system_status#gfpdf-font-download-failed$/
			);
			await expect(
				failed.getByRole('button', { name: 'Retry the downloads' })
			).toBeVisible();
			await expect(
				failed.getByRole('button', { name: 'Dismiss Notice' })
			).toBeVisible();

			await snapshot(page, testinfo, failed);
		});

		test('should stay dismissed until a font fails again', async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}) => {
			const failed = notice(page);

			await admin.visitAdminPage('index.php');

			await failed
				.getByRole('button', { name: 'Dismiss Notice' })
				.click();
			await expect(failed).toHaveCount(0);

			await admin.visitAdminPage('index.php');
			await expect(failed).toHaveCount(0);

			// A failure after the dismissal, which only covers the failures it followed
			await giveUpAt(page, 4102444800);

			await admin.visitAdminPage('index.php');
			await expect(failed.locator('li code')).toHaveText(FAILED_FONTS);
		});

		test('should clear the failures when the downloads are retried', async ({
			page,
			admin,
		}: {
			page: Page;
			admin: Admin;
		}) => {
			const failed = notice(page);

			await giveUpAt(page, 4102444800);
			await admin.visitAdminPage('index.php');

			await failed
				.getByRole('button', { name: 'Retry the downloads' })
				.click();
			await expect(
				page.getByText(
					'Gravity PDF will try downloading the fonts again in the background.'
				)
			).toBeVisible();
			await expect(failed).toHaveCount(0);

			await admin.visitAdminPage('index.php');
			await expect(failed).toHaveCount(0);
		});
	});
});
