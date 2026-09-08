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

		const unsupported = sectionByTitle('Unsupported');

		// Everything the v3 provider detects is gone in 7.0, so there is nothing left to report as merely deprecated
		await expect(sectionByTitle('Deprecated')).toHaveCount(0);
		await expect(unsupported).toBeVisible();

		// Templates are listed by file name alone, since the report states the working directory of its own, and
		// by the forms configured to render through them
		await expect(unsupported).toContainText(
			'PDFs that use a legacy template can no longer be generated in Gravity PDF 7.0.'
		);
		await expect(unsupported).toContainText(
			`e2e-legacy.php (form ID ${formId})`
		);
		await expect(unsupported).not.toContainText('PDF_EXTENDED_TEMPLATES');

		// A template that drives the PDF engine is a Business Plus one, and reports under its own heading
		await expect(unsupported).toContainText(
			'PDFs built with Advanced Templating can no longer be generated in Gravity PDF 7.0.'
		);
		await expect(unsupported).toContainText(
			'e2e-business-plus.php (not configured on a form)'
		);

		// Each detected form links to its own PDF settings, which is where both the template and the URL are replaced
		await expect(unsupported).toContainText(
			`Broken link stored on form ID ${formId}`
		);

		const formLinks = unsupported.getByRole('link', {
			name: `${formId}`,
			exact: true,
		});

		await expect(formLinks).toHaveCount(2);
		await expect(formLinks.first()).toHaveAttribute(
			'href',
			new RegExp(`subview=PDF&id=${formId}$`)
		);

		// Both hook shapes the map names are reported: a v3-shaped alias and a `gfpdf_legacy_` one
		await expect(unsupported).toContainText('gfpdf_rtl has 1 listener');
		await expect(unsupported).toContainText(
			'gfpdf_legacy_templates has 1 listener'
		);

		await isolateForSnapshot(page, [unsupported]);
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
				'Your site uses Gravity PDF functionality that has been removed',
		});

		await expect(heading).toBeVisible({ timeout: 30000 });
		await heading.click();

		const panel = page.locator(
			'#health-check-accordion-block-gravity_pdf_deprecated_features'
		);

		// Each feature reads as it does in the system report, under the heading of the group it belongs to
		await expect(
			panel.getByRole('heading', { name: 'Unsupported', exact: true })
		).toBeVisible();

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

		// Each group in use gets its own section, so a support ticket carries the detections split the way they are
		// fixed. Only the unsupported group is declared since 7.0
		const unsupportedHeading = page.locator(
			'#health-check-section-gravity-pdf-unsupported'
		);

		// The title no longer names the group, which the panel now does for itself
		await expect(unsupportedHeading).toHaveText('Gravity PDF');
		await unsupportedHeading.click();

		const unsupported = page.locator(
			'#health-check-accordion-block-gravity-pdf-unsupported'
		);

		await expect(
			unsupported.locator('h4', { hasText: 'Unsupported Features' })
		).toBeVisible();

		// The intro belongs to a list, so it is present only because there is something to introduce
		await expect(unsupported).toContainText(
			'These features have been removed and any Gravity PDF document that relied on them will stop working.'
		);

		// Templates are named by file, with the upgrade URL travelling in the support ticket beside them
		await expect(unsupported).toContainText(
			`e2e-legacy.php (form ID ${formId}). PDFs that use a legacy template can no longer be generated in Gravity PDF 7.0.`
		);
		await expect(unsupported).toContainText(
			'https://docs.gravitypdf.com/upgrade/legacy-templates/'
		);
		await expect(unsupported).toContainText(
			`Broken link stored on form ID ${formId}`
		);
		await expect(unsupported).toContainText('gfpdf_rtl has 1 listener');

		await isolateForSnapshot(page, [unsupportedHeading, unsupported]);
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

		await isolateForSnapshot(page, [notice]);

		await snapshot(page, testinfo);
	});
});
