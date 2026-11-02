import { test } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { pdfSnapshot } from '@self:playwright/utils/pdf-snapshot';
import {
	seedFixture,
	templateVariants,
	type TemplateOptions,
} from '@self:playwright/utils/template-snapshots';

const TEMPLATES: Record<string, TemplateOptions> = {
	'blank-slate': {},
	'focus-gravity': {
		allOptions: {
			focusgravity_accent_colour: '#1E5C8A',
			focusgravity_secondary_colour: '#F4E1C1',
			focusgravity_label_format: 'split_label',
		},
		// Dark enough that the labels and titles switch to light text
		optionsOff: {
			focusgravity_accent_colour: '#222222',
			focusgravity_secondary_colour: '#555555',
			focusgravity_label_format: 'combined_label',
		},
	},
	rubix: {
		allOptions: { rubix_container_background_colour: '#D8ECF3' },
		optionsOff: { rubix_container_background_colour: '#333333' },
	},
	zadani: {
		allOptions: { zadani_border_colour: '#B03030' },
		optionsOff: { zadani_border_colour: '#000000' },
	},
};

for (const [template, options] of Object.entries(TEMPLATES)) {
	test.describe(`${template} template`, () => {
		for (const variant of templateVariants(options)) {
			test(
				variant.title,
				async ({ requestUtils, page, admin }, testinfo) => {
					const pdf = new Pdf(requestUtils, admin, page);
					const site = testinfo.project.use.baseURL!;
					const { formId, entryId } = await seedFixture(
						pdf,
						variant.fixture,
						site
					);

					const pdfId = await pdf.addPdf(
						formId,
						`${template} ${variant.title}`,
						{ ...variant.settings(site), template }
					);

					await pdfSnapshot(
						testinfo,
						await pdf.getPdfBody(pdfId, entryId, {
							'X-GPDF-E2E-Form-Title': variant.fixture.title,
						})
					);
				}
			);
		}
	});
}
