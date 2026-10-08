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

	test('processes merge tags only in values the form editor set', async () => {
		const ip = '10.9.8.7';
		const entry: any = await pdf.createEntry({
			form_id: form.id,
			ip,
			'1': '{ip}',
			'2.1': '{ip}',
			'6': `<p>{ip}</p><p>{PDF:pdf:${pdfId}:signed}</p>`,
		});

		expect(entry.ip).toBe(ip);

		let html = await pdf.getPdfDebugOutput(pdfId, entry.id);

		expect(html).toContain(`Form ID: ${form.id}`);
		expect(html).toContain('&#123;ip&#125;');
		expect(html).not.toContain(ip);
		expect(html).not.toContain('signature=');

		const chosen: any = await pdf.createEntry({
			form_id: form.id,
			'1': 'First Choice',
		});

		html = await pdf.getPdfDebugOutput(pdfId, chosen.id);

		expect(html).toContain(`First <strong>Choice</strong> ${form.id}`);
	});

	test('encodes the tags in $form_data like the PDF HTML', async () => {
		const entry: any = await pdf.createEntry({
			form_id: form.id,
			'5': `[gravitypdf id="${pdfId}" raw="1"]`,
			'16': adminDefault,
			user_agent: `[gravitypdf id="${pdfId}" raw="1"]`,
		});

		const formData = await pdf.getPdfDebugOutput(pdfId, entry.id, 'data');

		expect(formData).toContain('&#91;gravitypdf');
		expect(formData).not.toMatch(/\[gravitypdf id="\d+" raw="1"\]/);

		// the administrative field's default is left for the template to process
		expect(formData).toContain(adminDefault);
	});
});
