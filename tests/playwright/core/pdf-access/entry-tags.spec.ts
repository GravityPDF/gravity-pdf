import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';

test.describe('Shortcodes and merge tags in entry values', () => {
	let pdf: Pdf;
	let form: any;
	let pdfId: string;
	let adminDefault: string;

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
			form = await pdf.createForm('Entry Tags');
			pdfId = await pdf.addPdf(form.id, 'Entry Tags', {
				show_html: true,
			});

			adminDefault = `[gravitypdf id="${pdfId}" signed="1" raw="1"]`;

			// a choice, a rich text field, an HTML block and an administrative field (the last row) the form editor
			// sets the tags in
			await pdf.updateForm(form.id, (current: any) => ({
				fields: [
					...current.fields.map((field: any) => {
						if (field.id === 1) {
							const [first, ...rest] = field.choices;

							return {
								...field,
								choices: [
									{
										...first,
										text: 'First <strong>Choice</strong> {form_id}',
									},
									...rest,
								],
							};
						}

						if (field.id === 3) {
							return { ...field, content: 'Form ID: {form_id}' };
						}

						if (field.id === 6) {
							return { ...field, useRichTextEditor: true };
						}

						return field;
					}),
					{
						id: 16,
						type: 'text',
						label: 'Administrative',
						visibility: 'administrative',
						defaultValue: adminDefault,
					},
				],
			}));
		}
	);

	test('signs the PDF link in an administrative field default', async () => {
		const entry: any = await pdf.createEntry({
			form_id: form.id,
			'16': adminDefault,
		});

		const html = await pdf.getPdfDebugOutput(pdfId, entry.id);

		expect(html).toMatch(
			new RegExp(
				`${pdfId}.*${entry.id}.*signature=|${entry.id}.*${pdfId}.*signature=`
			)
		);
	});

	test('keeps the tags in field values encoded through the render', async () => {
		const entry: any = await pdf.createEntry({
			form_id: form.id,
			'6': `<a href="[gravitypdf id=${pdfId}]">Link</a>`,
			'16': `Posted [gravitypdf id="${pdfId}" raw="1"]`,
		});

		const html = await pdf.getPdfDebugOutput(pdfId, entry.id);

		expect(html).toMatch(/href="[^"]*%5Bgravitypdf/);
		expect(html).toContain('Posted &#091;gravitypdf');
		expect(html).not.toMatch(/\[gravitypdf/);
	});
});
