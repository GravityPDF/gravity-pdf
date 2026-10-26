import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('File Upload Conditional Logic', () => {
	let pdf = null;
	let form = null;
	let pdfId = null;

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
			form = await pdf.createForm('File Upload Conditional Logic');

			await pdf.updateForm(form.id, (current: any) => ({
				fields: [
					...current.fields,
					{ id: 16, type: 'fileupload', label: 'Single Upload' },
					{
						id: 17,
						type: 'fileupload',
						label: 'Multi Upload',
						multipleFiles: true,
					},
				],
			}));

			pdfId = await pdf.addPdf(
				form.id,
				`File Upload Conditional Logic ${form.id}`
			);
		}
	);

	test('should offer an "is not Empty" rule for a File Upload field and keep it on save', async () => {
		await pdf.navigateToFormPdf(form.id, pdfId);
		await pdf.page
			.getByRole('checkbox', {
				name: 'Enable conditional logic',
				exact: true,
			})
			.check();

		for (const label of ['Single Upload', 'Multi Upload']) {
			await pdf.page
				.locator('#gfpdf_rule_field_0')
				.selectOption({ label });

			await expect(
				pdf.page.locator('#gfpdf_rule_operator_0 option')
			).toHaveText(['is', 'is not']);
			await expect(
				pdf.page.locator('#gfpdf_rule_value_0 option')
			).toHaveText(['Empty']);
		}

		await pdf.page.locator('#gfpdf_rule_operator_0').selectOption('isnot');
		await pdf.addOrUpdatePdf();

		await pdf.navigateToFormPdf(form.id, pdfId);
		await expect(pdf.page.locator('#gfpdf_rule_field_0')).toHaveValue('17');
		await expect(pdf.page.locator('#gfpdf_rule_operator_0')).toHaveValue(
			'isnot'
		);
		await expect(pdf.page.locator('#gfpdf_rule_value_0')).toHaveValue('');

		// Only the entry with a file passes
		await pdf.createEntry({ form_id: form.id, 17: '' });
		await pdf.createEntry({
			form_id: form.id,
			17: ['https://example.com/file.pdf'],
		});

		await pdf.navigateToEntryList(form.id);
		await expect(
			pdf.page.getByRole('link', { name: 'View PDF' })
		).toHaveCount(1);
	});
});
