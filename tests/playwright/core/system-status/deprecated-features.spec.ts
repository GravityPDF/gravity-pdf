import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import {
	installDeprecatedUsage,
	maskFormIds,
	removeDeprecatedUsage,
	setLegacyDownloadUrl,
} from '@self:playwright/utils/deprecation';
import { snapshot } from '@self:playwright/utils/snapshot';

test.describe('Deprecated Features', () => {
	// The signals are site-wide, so the tests share one set-up and run in order
	test.describe.configure({ mode: 'serial' });

	// Only requests carrying this see the notices the signals raise, which keeps them out of the other specs running
	// alongside (see tools/mu-plugins)
	test.beforeEach(async ({ page }: { page: Page }) => {
		await page.setExtraHTTPHeaders({ 'X-GPDF-E2E-Deprecations': 'yes' });
	});

	let pdf: Pdf;
	let formId: number;

	test.beforeAll(async ({ requestUtils }: { requestUtils: RequestUtils }) => {
		// createForm only touches requestUtils, the one fixture a beforeAll hook can take
		pdf = new Pdf(requestUtils, undefined!, undefined!);

		const form: any = await pdf.createForm('Legacy Download URL');
		formId = form.id;

		await setLegacyDownloadUrl(pdf, formId);
		installDeprecatedUsage(formId);
	});

	test.afterAll(async () => {
		// The form carries two of the signals, and forms outlive the run
		await pdf.deleteForm(formId);

		removeDeprecatedUsage();
	});

	test('should list each detected feature in the system report', async ({
		page,
		admin,
	}: {
		page: Page;
		admin: Admin;
	}, testinfo) => {
		await admin.visitAdminPage('admin.php', 'page=gf_system_status');

		// The table title is its caption, matched whole so a stray "Checks" elsewhere can't join in
		const checks = page.locator('table.gform_system_report').filter({
			has: page.locator('caption', { hasText: /^\s*Checks\s*$/ }),
		});

		const feature = (label: string) =>
			checks.getByRole('row', { name: `${label} (Unsupported)` });

		const legacyTemplates = feature('Legacy Templates');

		// Everything the v3 provider detects is gone in 7.0, so there is nothing left to report as merely deprecated
		await expect(
			checks.getByRole('row', { name: /\(Deprecated\)/ })
		).toHaveCount(0);
		await expect(checks).toContainText('no deprecated features');

		// Templates are listed by file name alone, since the report states the working directory of its own, and
		// by the forms configured to render through them
		await expect(legacyTemplates).toContainText(
			`e2e-legacy.php (form ID ${formId})`
		);
		await expect(legacyTemplates).toContainText(
			'Removed in Gravity PDF 7.0, so it no longer runs.'
		);
		await expect(
			legacyTemplates.getByRole('link', { name: 'How to upgrade' })
		).toHaveAttribute(
			'href',
			'https://docs.gravitypdf.com/upgrade/legacy-templates/'
		);
		await expect(checks).not.toContainText('PDF_EXTENDED_TEMPLATES');

		// A template that drives the PDF engine is a Business Plus one, and reports under its own row
		await expect(feature('Business Plus / Tier 2 Templates')).toContainText(
			'e2e-business-plus.php (not configured on a form)'
		);

		// Each detected form links to its own PDF settings, which is where both the template and the URL are replaced
		await expect(feature('Legacy Download URLs')).toContainText(
			`Broken link stored on form ID ${formId}`
		);

		const formLinks = checks.getByRole('link', {
			name: `${formId}`,
			exact: true,
		});

		await expect(formLinks).toHaveCount(2);
		await expect(formLinks.first()).toHaveAttribute(
			'href',
			new RegExp(`subview=PDF&id=${formId}$`)
		);

		// Both hook shapes the map names are reported, one per line: a v3-shaped alias and a `gfpdf_legacy_` one
		const hooks = feature('Actions and Filters');

		await expect(hooks).toContainText('gfpdf_rtl has 1 listener');
		await expect(hooks).toContainText(
			'gfpdf_legacy_templates has 1 listener'
		);

		await maskFormIds(page);

		// Only the feature rows, as the checks below them depend on what the specs alongside leave behind
		await snapshot(page, testinfo, [
			legacyTemplates,
			feature('Business Plus / Tier 2 Templates'),
			feature('Legacy Download URLs'),
			hooks,
		]);
	});

	test('should report the detected features in Site Health', async ({
		page,
		admin,
	}: {
		page: Page;
		admin: Admin;
	}, testinfo) => {
		await admin.visitAdminPage('site-health.php');

		const heading = page.locator('.health-check-accordion-heading', {
			hasText:
				'Your site uses Gravity PDF functionality that has been removed',
		});

		await expect(heading).toBeVisible({ timeout: 30000 });
		await heading.click();

		// Removed functionality has a critical test of its own, apart from what is only scheduled for removal
		const panel = page.locator(
			'#health-check-accordion-block-gravity_pdf_removed_features'
		);

		// Each feature reads as it does in the system report, after the group's own intro
		await expect(panel).toContainText(
			'These features have been removed and any Gravity PDF document that relied on them will stop working.'
		);

		await expect(panel).toContainText(
			`e2e-legacy.php (form ID ${formId}). PDFs that use a legacy template can no longer be generated in Gravity PDF 7.0.`
		);
		await expect(panel).not.toContainText('PDF_EXTENDED_TEMPLATES');
		await expect(panel).toContainText(
			`Broken link stored on form ID ${formId}`
		);
		await expect(panel).toContainText('gfpdf_rtl has 1 listener');
		await expect(
			panel.getByRole('link', { name: 'Learn how to upgrade' }).first()
		).toHaveAttribute('href', /upgrade\/legacy-templates\//);
		await expect(
			panel.getByRole('link', {
				name: 'View the Gravity Forms system report',
			})
		).toBeVisible();

		await maskFormIds(page);

		await snapshot(page, testinfo, [heading, panel]);
	});

	test('should include the detected features in the Site Health Info tab', async ({
		page,
		admin,
	}: {
		page: Page;
		admin: Admin;
	}, testinfo) => {
		await admin.visitAdminPage('site-health.php', 'tab=debug');

		// One panel with the lines of the copied system report, so a support ticket reads the same from either place
		const heading = page.locator('#health-check-section-gravity-pdf');

		await expect(heading).toHaveText('Gravity PDF');
		await heading.click();

		const panel = page.locator('#health-check-accordion-block-gravity-pdf');

		// Each feature is a check line ending in its mark, as it copies from the system report
		await expect(panel).toContainText('Legacy Templates (Unsupported)');
		await expect(panel).toContainText(
			`e2e-legacy.php (form ID ${formId}) ✘`
		);
		await expect(panel).toContainText(
			`Broken link stored on form ID ${formId}`
		);
		await expect(panel).toContainText('gfpdf_rtl has 1 listener');

		// The settings follow the checks, as in the copied report
		await expect(panel).toContainText('Entry View');

		await expect(
			page.locator('#health-check-section-gravity-pdf-unsupported')
		).toHaveCount(0);

		await maskFormIds(page);

		const row = (label: string) =>
			panel.locator('tr', { hasText: `${label} (Unsupported)` });

		// Only the feature rows, as the checks and settings below them depend on what the specs alongside leave behind
		await snapshot(page, testinfo, [
			heading,
			row('Legacy Templates'),
			row('Business Plus / Tier 2 Templates'),
			row('Legacy Download URLs'),
			row('Actions and Filters'),
		]);
	});

	test('should raise one dismissible notice covering every detected feature', async ({
		page,
		admin,
	}: {
		page: Page;
		admin: Admin;
	}, testinfo) => {
		await admin.visitAdminPage('index.php');

		// One notice for the lot, rather than one per feature competing for the same screen
		const notice = page.locator('.notice', {
			hasText:
				'This site uses Gravity PDF functionality that has been removed',
		});

		await expect(notice).toHaveCount(1);
		await expect(notice).toBeVisible();

		// 7.0 removed features this site still uses, so the notice reads as an error rather than a warning
		await expect(notice).toHaveClass(/notice-error/);

		// Every detected feature is listed, each linking to the guide that covers it
		await expect(notice).toContainText(
			'PDFs that use a legacy template can no longer be generated in Gravity PDF 7.0.'
		);
		await expect(notice).toContainText(
			'PDFs built with Advanced Templating can no longer be generated in Gravity PDF 7.0.'
		);
		await expect(notice).toContainText(
			'Old PDF download links stopped working in Gravity PDF 7.0, so anyone who clicks one will not get their PDF. Replace those links with the [gravitypdf] shortcode or a PDF merge tag.'
		);

		// The hooks item says what is actually affected: out of its report row, "Actions and Filters" reads as the lot
		await expect(notice).toContainText(
			'Custom code on this site uses Gravity PDF hooks that were removed in version 7.0 and has stopped running. Update it to use the current hooks.'
		);

		await expect(
			notice.getByRole('button', { name: 'View the system report' })
		).toBeVisible();
		await expect(
			notice.getByRole('button', { name: 'Dismiss Notice' })
		).toBeVisible();

		await snapshot(page, testinfo, notice);
	});
});
