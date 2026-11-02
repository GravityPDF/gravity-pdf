import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Locator, Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

const CONSENT_FIELD = 1;

// Arimo is first in the Unicode group, and the symbol subset, which only fills in characters, isn't listed
const expectBundledFontsListed = async (select: Locator) => {
	const first = select.locator('optgroup[label="Unicode"] option').first();

	await expect(first).toHaveText('Arimo');
	await expect(first).toHaveAttribute('value', 'arimo');
	await expect(
		select.locator('option[value="gfpdf-dejavu-symbols"]')
	).toHaveCount(0);
};

// The fonts a PDF embeds, without their subset tags
const embeddedFonts = (pdf: Buffer) =>
	Array.from(
		new Set(
			Array.from(
				pdf
					.toString('latin1')
					.matchAll(/\/BaseFont \/(?:[A-Z]{6}\+)?([\w-]+)/g),
				(match) => match[1]
			)
		)
	).sort();

test.describe('Bundled fonts', () => {
	let pdf: Pdf;

	test.beforeEach(
		async ({
			requestUtils,
			page,
			admin,
		}: {
			requestUtils: RequestUtils;
			page: Page;
			admin: Admin;
		}) => {
			pdf = new Pdf(requestUtils, admin, page);
		}
	);

	test('should list Arimo first in the PDF font dropdown', async ({
		page,
	}) => {
		const form = await pdf.createForm('Bundled Fonts List');
		await pdf.navigateToNewFormPdf(form.id);

		await expectBundledFontsListed(
			page.getByLabel('Font', { exact: true })
		);
	});

	test('should list Arimo first in the default font dropdown', async ({
		page,
		admin,
	}) => {
		await admin.visitAdminPage(
			'admin.php',
			'page=gf_settings&subview=PDF&tab=general'
		);

		await expectBundledFontsListed(
			page.getByLabel('Default Font', { exact: true })
		);
	});

	test('should render in Arimo with consent ticks from the symbol subset', async ({
		page,
	}) => {
		const form = await pdf.createForm('Bundled Fonts Render', undefined, {
			fields: [
				{
					id: CONSENT_FIELD,
					type: 'consent',
					label: 'Consent',
					checkboxLabel: 'I agree',
					inputs: [
						{ id: `${CONSENT_FIELD}.1`, label: 'Consent' },
						{
							id: `${CONSENT_FIELD}.2`,
							label: 'Text',
							isHidden: true,
						},
						{
							id: `${CONSENT_FIELD}.3`,
							label: 'Description',
							isHidden: true,
						},
					],
				},
			],
		});

		const pdfId = await pdf.addPdf(form.id, 'Bundled Fonts', {
			font: 'arimo',
		});
		const entry = await pdf.createEntry({
			form_id: form.id,
			[`${CONSENT_FIELD}.1`]: '1',
			[`${CONSENT_FIELD}.2`]: 'I agree',
			[`${CONSENT_FIELD}.3`]: '1',
		});

		// The test site renders with mPDF's core fonts unless a request asks for real ones. It installs no core fonts,
		// so DejaVuSans can only be the symbol subset
		const response = await page.request.get(
			`/?gpdf=1&pid=${pdfId}&lid=${entry.id}`,
			{ headers: { 'X-GPDF-E2E-Fonts': 'unicode' } }
		);

		expect(response.headers()['content-type']).toBe('application/pdf');
		expect(embeddedFonts(await response.body())).toEqual([
			'Arimo-Bold',
			'Arimo-Regular',
			'DejaVuSans',
		]);
	});
});
