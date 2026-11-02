import type Pdf from '@self:playwright/utils/gravitypdf';
import { FIXED_ENTRY_DATE } from '@self:playwright/utils/gravityforms';
import {
	allFields,
	columnFixtures,
	IMAGES,
	type Fixture,
} from '@self:playwright/utils/template-fixtures';

type Settings = Record<string, unknown>;

/**
 * Every core setting on, with a different first page header and footer
 * @param site
 */
const allOptions = (site: string): Settings => ({
	show_form_title: true,
	show_page_names: true,
	show_html: true,
	show_section_content: true,
	enable_conditional: true,
	show_empty: true,
	first_header: `<p><img src="${site}${IMAGES.photo}" class="header-footer-img" /> First page header for {form_title}, submitted {date_created:format:j F Y}</p>`,
	header: '<p class="alignright">Header on later pages for {Name (First):11.3} {Name (Last):11.6}</p>',
	first_footer:
		'<p class="aligncenter">First page footer, page {PAGENO} of {nbpg}</p>',
	footer: '<p class="alignleft">Footer on later pages, page {PAGENO} of {nbpg}</p>',
	background_color: '#FDF6E3',
	// A flat vector keeps the baselines light
	background_image: `${site}${IMAGES.logo}`,
	pdf_size: 'LETTER',
	orientation: 'portrait',
	font_size: 11,
	font_colour: '#1D3557',
	image_dpi: 300,
	security: true,
	password: '',
	privileges: ['print'],
	master_password: 'master',
});

/**
 * allOptions inverted, plus the settings it leaves at their default, changed
 * @param site
 */
const optionsOff = (site: string): Settings => ({
	show_form_title: false,
	show_page_names: false,
	show_html: false,
	show_section_content: false,
	enable_conditional: false,
	show_empty: false,
	// No first page header, so the header also runs on page 1
	header: `<p><img src="${site}${IMAGES.photo}" class="header-footer-img" /> Header on every page for {form_title}</p>`,
	first_footer: '<p>First page footer only</p>',
	background_color: '#E8F0FE',
	pdf_size: 'CUSTOM',
	custom_pdf_size: { width: 180, height: 250, unit: 'mm' },
	orientation: 'landscape',
	font_size: 8,
	font_colour: '#5A189A',
	// RTL and PDF/A omitted: mPDF refuses both with E2E's core fonts
});

// Show every laid-out field, so only hidden or empty ones vary
const columnOptions: Settings = {
	show_page_names: true,
	show_html: true,
	show_section_content: true,
	show_empty: false,
};

export type TemplateOptions = { allOptions?: Settings; optionsOff?: Settings };

export type Variant = {
	title: string;
	fixture: Fixture;
	settings: (site: string) => Settings;
};

/**
 * A template's defaults, every option on, every option off, and each column layout with the conditions on its hidden
 * fields met and not, the latter with conditional logic both on and off
 * @param options the template's own settings, laid over `allOptions` and `optionsOff`
 */
export function templateVariants(options: TemplateOptions): Variant[] {
	const variants: Variant[] = [
		{ title: 'defaults', fixture: allFields, settings: () => ({}) },
		{
			title: 'all options',
			fixture: allFields,
			settings: (site) => ({
				...allOptions(site),
				...options.allOptions,
			}),
		},
		{
			title: 'options off',
			fixture: allFields,
			settings: (site) => ({
				...optionsOff(site),
				...options.optionsOff,
			}),
		},
	];

	for (const [name, { met, notMet }] of Object.entries(columnFixtures)) {
		const columns = (fixture: Fixture, conditional: boolean) => ({
			fixture,
			settings: () => ({
				...columnOptions,
				enable_conditional: conditional,
			}),
		});

		variants.push(
			{ title: `${name}, conditions met`, ...columns(met, true) },
			{ title: `${name}, conditions not met`, ...columns(notMet, true) },
			{
				title: `${name}, conditions not met, conditional logic off`,
				...columns(notMet, false),
			}
		);
	}

	return variants;
}

// Seeded once per worker; tests only read them, each adding a PDF of its own
const forms = new Map<string, Promise<number>>();
const entries = new Map<Fixture, Promise<number>>();

/**
 * The form and entry a fixture describes, created on first use in this worker
 * @param pdf
 * @param fixture
 * @param site
 */
export async function seedFixture(pdf: Pdf, fixture: Fixture, site: string) {
	if (!forms.has(fixture.title)) {
		forms.set(
			fixture.title,
			pdf.addForm(fixture.title, fixture.form()).then((form) => form.id)
		);
	}

	const formId = await forms.get(fixture.title)!;

	if (!entries.has(fixture)) {
		entries.set(
			fixture,
			pdf
				.createEntry({
					...fixture.entry(site),
					form_id: formId,
					date_created: FIXED_ENTRY_DATE,
				})
				.then((entry) => entry.id)
		);
	}

	return { formId, entryId: await entries.get(fixture)! };
}
