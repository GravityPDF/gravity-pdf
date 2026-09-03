import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import {
	clearDeprecatedDetection,
	clearDeprecatedUsage,
	installLegacyTemplates,
	maskFormIds,
	recordDeprecatedUsage,
	refreshDeprecatedDetection,
	removeLegacyTemplates,
	removeLegacyTemplateFromForm,
	setLegacyDownloadUrl,
	useLegacyTemplateOnForm,
} from '@self:playwright/utils/deprecation';
import { isolateForSnapshot, snapshot } from '@self:playwright/utils/snapshot';

test.describe('Deprecated Features', () => {
	// The signals are site-wide, so the tests share one set-up and run in order. Keeping the rest of the suite out
	// while they are installed is the `core-isolated` project's job, since serial mode only orders this file.
	test.describe.configure({ mode: 'serial' });

	let pdf: Pdf;
	let formId: number;

	test.beforeAll(async ({ requestUtils }: { requestUtils: RequestUtils }) => {
		recordDeprecatedUsage();
		installLegacyTemplates();

		// createForm only touches requestUtils, the one fixture a beforeAll hook can take
		pdf = new Pdf(requestUtils, undefined!, undefined!);

		const form: any = await pdf.createForm('Legacy Download URL');
		formId = form.id;

		await setLegacyDownloadUrl(pdf, formId, true);
		useLegacyTemplateOnForm(formId);

		refreshDeprecatedDetection();
	});

	test.afterAll(async () => {
		clearDeprecatedUsage();
		removeLegacyTemplates();
		removeLegacyTemplateFromForm(formId);

		// Leave the site as we found it: the form carries one of the signals, and forms outlive the run
		await setLegacyDownloadUrl(pdf, formId, false);

		// Leave the notices nothing to report, so the permalink pass runs against a site without them
		clearDeprecatedDetection();
	});

	test('should list each detected feature in the system report', async ({
		page,
		admin,
	}: {
		page: Page;
		admin: Admin;
	}, testinfo) => {
		await admin.visitAdminPage('admin.php', 'page=gf_system_status');

		// The section title is the table's caption, matched whole so a stray "Deprecated" elsewhere can't join in
		const sectionByTitle = (title: string) =>
			page.locator('table.gform_system_report').filter({
				has: page.locator('caption', {
					hasText: new RegExp(`^\\s*${title}\\s*$`),
				}),
			});

		const deprecated = sectionByTitle('Deprecated');

		await expect(deprecated).toBeVisible();

		// No registered feature has already been removed, so that section isn't carried around empty
		await expect(sectionByTitle('Unsupported')).toHaveCount(0);

		// Templates are listed by file name alone, since the report states the working directory of its own, and
		// by the forms configured to render through them
		await expect(deprecated).toContainText(
			'Support for Legacy Templates will be removed in Gravity PDF 7.0.'
		);
		await expect(deprecated).toContainText(
			`e2e-legacy.php (form ID ${formId})`
		);
		await expect(deprecated).not.toContainText('PDF_EXTENDED_TEMPLATES');

		// A template that drives the PDF engine is a Business Plus one, and reports under its own heading
		await expect(deprecated).toContainText(
			'Support for Business Plus / Tier 2 Templates will be removed in Gravity PDF 7.0.'
		);
		await expect(deprecated).toContainText(
			'e2e-business-plus.php (not configured on a form)'
		);

		// Each detected form links to its own PDF settings, which is where both the template and the URL are replaced
		await expect(deprecated).toContainText(`In use on form ID ${formId}`);

		const formLinks = deprecated.getByRole('link', {
			name: `${formId}`,
			exact: true,
		});

		await expect(formLinks).toHaveCount(2);
		await expect(formLinks.first()).toHaveAttribute(
			'href',
			new RegExp(`subview=PDF&id=${formId}$`)
		);

		// Both hook shapes are reported: the v3 `gfpdfe_` prefix, and the ones only the map can name
		await expect(deprecated).toContainText('gfpdf_rtl has 1 listener');
		await expect(deprecated).toContainText(
			'gfpdf_legacy_templates has 1 listener'
		);

		await isolateForSnapshot(page, [deprecated]);
		await maskFormIds(page);

		await snapshot(page, testinfo);
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
				'Your site uses Gravity PDF functionality that is scheduled for removal',
		});

		await expect(heading).toBeVisible({ timeout: 30000 });
		await heading.click();

		const panel = page.locator(
			'#health-check-accordion-block-gravity_pdf_deprecated_features'
		);

		// Each feature reads as it does in the system report, under the group heading it belongs to
		await expect(
			panel.getByRole('heading', { name: 'Deprecated', exact: true })
		).toBeVisible();

		await expect(panel).toContainText(
			`e2e-legacy.php (form ID ${formId}). Support for Legacy Templates will be removed in Gravity PDF 7.0.`
		);
		await expect(panel).not.toContainText('PDF_EXTENDED_TEMPLATES');
		await expect(panel).toContainText(`In use on form ID ${formId}`);
		await expect(panel).toContainText('gfpdf_rtl has 1 listener');
		await expect(
			panel.getByRole('link', { name: 'Learn how to upgrade' }).first()
		).toHaveAttribute('href', /upgrade\/legacy-templates\//);
		await expect(
			panel.getByRole('link', {
				name: 'View the Gravity Forms system report',
			})
		).toBeVisible();

		await isolateForSnapshot(page, [heading, panel]);
		await maskFormIds(page);

		await snapshot(page, testinfo);
	});

	test('should include the detected features in the Site Health Info tab', async ({
		page,
		admin,
	}: {
		page: Page;
		admin: Admin;
	}, testinfo) => {
		await admin.visitAdminPage('site-health.php', 'tab=debug');

		// The group gets its own section, so a support ticket carries the detections with it
		const deprecatedHeading = page.locator(
			'#health-check-section-gravity-pdf-deprecated'
		);

		// The title no longer names the group, which the panel now does for itself
		await expect(deprecatedHeading).toHaveText('Gravity PDF');
		await deprecatedHeading.click();

		const deprecated = page.locator(
			'#health-check-accordion-block-gravity-pdf-deprecated'
		);

		await expect(
			deprecated.locator('h4', { hasText: 'Deprecated Features' })
		).toBeVisible();

		// The intro belongs to a list, so it is present only because there is something to introduce
		await expect(deprecated).toContainText(
			'Legacy functionality that will be removed in an upcoming release'
		);

		// Templates are named by file, with the upgrade URL travelling in the support ticket beside them
		await expect(deprecated).toContainText(
			`e2e-legacy.php (form ID ${formId}). Support for Legacy Templates will be removed in Gravity PDF 7.0.`
		);
		await expect(deprecated).toContainText(
			'https://docs.gravitypdf.com/upgrade/legacy-templates/'
		);
		await expect(deprecated).toContainText(`In use on form ID ${formId}`);
		await expect(deprecated).toContainText('gfpdf_rtl has 1 listener');

		await isolateForSnapshot(page, [deprecatedHeading, deprecated]);
		await maskFormIds(page);

		await snapshot(page, testinfo);
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
			hasText: 'This site uses deprecated Gravity PDF functionality',
		});

		await expect(notice).toHaveCount(1);
		await expect(notice).toBeVisible();

		// Functionality still working, but not for much longer, reads as a warning rather than an error
		await expect(notice).toHaveClass(/notice-warning/);

		// Every detected feature is listed, each linking to the guide that covers it
		await expect(notice).toContainText(
			'Support for Legacy Templates will be removed in Gravity PDF 7.0.'
		);
		await expect(notice).toContainText(
			'Support for Business Plus / Tier 2 Templates will be removed in Gravity PDF 7.0.'
		);
		await expect(notice).toContainText(
			'Support for legacy download URLs will be removed in Gravity PDF 7.0.'
		);

		// The hooks item says what is actually affected: out of its report row, "Actions and Filters" reads as the lot
		await expect(notice).toContainText(
			'Code on this site uses Gravity PDF hooks that are removed in version 7.0.'
		);
		await expect(
			notice.getByRole('button', { name: 'View the system report' })
		).toBeVisible();
		await expect(
			notice.getByRole('button', { name: 'Dismiss Notice' })
		).toBeVisible();

		await isolateForSnapshot(page, [notice]);

		await snapshot(page, testinfo);
	});
});
