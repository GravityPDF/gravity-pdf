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
	test('should report the missing core fonts and the Font Installer in the system report', async ({
		page,
		admin,
	}: {
		page: Page;
		admin: Admin;
	}, testinfo) => {
		// A default font no spec puts on disk, for this test's requests only (see tools/mu-plugins)
		await page.setExtraHTTPHeaders({
			'X-GPDF-E2E-Default-Font': 'pothana2000',
		});

		await admin.visitAdminPage('admin.php', 'page=gf_system_status');

		const coreFonts = page.getByRole('row', { name: /^Core Fonts/ });
		const installer = page.getByRole('row', { name: /^Font Installer/ });

		// Core font downloads are off on this site (see tools/mu-plugins), so the default font's file stays missing
		await expect(coreFonts).toContainText(/\d+ missing:/);
		await expect(coreFonts.locator('code')).toContainText([
			'Pothana2000.ttf',
		]);
		await expect(coreFonts).toContainText(
			'The Font Installer is turned off by the gfpdf_auto_install_fonts filter.'
		);
		await expect(
			coreFonts.getByRole('link', { name: 'Install the fonts by hand' })
		).toHaveAttribute(
			'href',
			'https://docs.gravitypdf.com/users/core-pdf-fonts'
		);
		await expect(installer).toContainText(
			'Off (gfpdf_auto_install_fonts filter)'
		);

		// The bundled fonts and the core ones mPDF loads only when they have the characters, listed in the copied report only
		await expect(page.locator('#gform-system-report-text')).toContainText(
			'Substitution Fonts: arimo, gfpdf-dejavu-symbols, dejavusanscondensed, freesans, garuda, khmeros, padaukbook, kaputaunicode, abyssinicasil, sun-exta'
		);

		// The rows between the two depend on what the specs alongside leave behind
		await snapshot(page, testinfo, installer);
	});

	test.describe('Failed downloads', () => {
		// Each test builds on the one before: the notice shows, is dismissed, then a later failure raises it again
		test.describe.configure({ mode: 'serial' });

		// Clears the dismissals stored by an earlier run (see tools/mu-plugins)
		test.beforeAll(() =>
			wpCli(
				'wp option delete gfpdf_e2e_failed_fonts_dismissed --quiet || true'
			)
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
				failed.getByRole('button', { name: 'View the system report' })
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
	});
});
