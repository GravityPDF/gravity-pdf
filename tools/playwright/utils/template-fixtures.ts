// Derived from the PHPUnit all-form-fields.json and its first entry: a field id looked up here that goes missing throws
import { readFileSync } from 'node:fs';
import * as path from 'node:path';

type Field = { id: number; type: string; [property: string]: unknown };
type Values = Record<string, unknown>;

export type Fixture = {
	// The form's title, which also tells apart the forms a worker seeds
	title: string;
	form: () => { fields: Field[]; [property: string]: unknown };
	entry: (site: string) => Values;
};

// Both ship in the release zip
export const IMAGES = {
	photo: '/wp-content/plugins/gravity-pdf/src/assets/images/cap-paws-sitting.png',
	logo: '/wp-content/plugins/gravity-pdf/src/assets/images/gravitypdf-logo.svg',
};

const PHPUNIT_DATA = path.join(__dirname, '../../phpunit/data');

// Add-ons aren't installed on the E2E site
const ADD_ON_FIELDS = ['poll', 'quiz', 'survey', 'signature'];

const SHOW_IF_TOGGLED = {
	actionType: 'show',
	logicType: 'all',
	rules: [{ fieldId: '1', operator: 'is', value: 'Show' }],
};

const readJson = (file: string) =>
	JSON.parse(readFileSync(path.join(PHPUNIT_DATA, file), 'utf8'));

/**
 * Number each field by its page, as the form editor does on save
 * @param fields
 */
function paginate(fields: Field[]) {
	let page = 1;

	return fields.map((field) => ({
		...field,
		pageNumber: field.type === 'page' ? ++page : page,
	}));
}

/**
 * The PHPUnit all-fields fixture, less the add-on fields, plus the core fields it predates, page breaks, columns, a
 * field hidden by conditional logic and one left empty
 */
export const allFields: Fixture = {
	title: 'Template Snapshot All Fields',

	form: () => {
		const base = readJson('forms/all-form-fields.json');
		const byId = new Map<number, Field>(
			base.fields
				.filter((field: Field) => !ADD_ON_FIELDS.includes(field.type))
				.map((field: Field) => [field.id, field])
		);

		const added: Field[] = [
			{
				id: 200,
				type: 'text',
				label: 'Visible Single Line Text',
				description:
					'Field 1 has the exclude class, so this is the text field the PDF shows',
			},
			{
				id: 208,
				type: 'text',
				label: 'Conditional Text',
				conditionalLogic: {
					...SHOW_IF_TOGGLED,
					rules: [{ fieldId: '200', operator: 'is', value: 'Never' }],
				},
			},
			{ id: 209, type: 'text', label: 'Empty Text' },
			{
				id: 210,
				type: 'textarea',
				label: 'Rich Text Paragraph',
				useRichTextEditor: true,
			},
			{
				id: 211,
				type: 'text',
				label: 'Administrative Text',
				visibility: 'administrative',
			},
			{
				id: 212,
				type: 'html',
				label: 'Rich HTML Block',
				content:
					'<p>HTML with <strong>bold</strong>, <em>italic</em> and a <a href="https://gravitypdf.com">link</a>.</p><ul><li>First item</li><li>Second item</li></ul>',
			},
			{
				id: 213,
				type: 'section',
				label: 'Second Section Break',
				description: 'Second section description',
			},
			{
				id: 201,
				type: 'consent',
				label: 'Consent',
				checkboxLabel: 'I agree to the privacy policy',
				description: 'Consent description shown under the checkbox.',
				inputs: [
					{ id: '201.1', label: 'Consent' },
					{ id: '201.2', label: 'Text', isHidden: true },
					{ id: '201.3', label: 'Description', isHidden: true },
				],
			},
			{
				id: 203,
				type: 'image_choice',
				inputType: 'radio',
				label: 'Image Choice',
				choices: [
					{ text: 'Image One', value: 'Image One', key: 'image-1' },
					{ text: 'Image Two', value: 'Image Two', key: 'image-2' },
				],
			},
			{
				id: 204,
				type: 'multi_choice',
				inputType: 'checkbox',
				label: 'Multiple Choice',
				choices: [
					{ text: 'Multi One', value: 'Multi One', key: 'multi-1' },
					{ text: 'Multi Two', value: 'Multi Two', key: 'multi-2' },
					{
						text: 'Multi Three',
						value: 'Multi Three',
						key: 'multi-3',
					},
				],
				inputs: [
					{ id: '204.1', label: 'Multi One', key: 'multi-1' },
					{ id: '204.2', label: 'Multi Two', key: 'multi-2' },
					{ id: '204.3', label: 'Multi Three', key: 'multi-3' },
				],
			},
			{ id: 205, type: 'post_content', label: 'Post Body' },
			{
				id: 202,
				type: 'creditcard',
				label: 'Credit Card',
				inputs: [
					{ id: '202.1', label: 'Card Number' },
					{ id: '202.2_month', label: 'Expiration Month' },
					{ id: '202.2_year', label: 'Expiration Year' },
					{ id: '202.3', label: 'Security Code' },
					{ id: '202.4', label: 'Card Type' },
					{ id: '202.5', label: 'Cardholder Name' },
				],
			},
			{ id: 206, type: 'captcha', label: 'CAPTCHA' },
			{ id: 207, type: 'password', label: 'Password' },
			{ id: 300, type: 'page' },
			{ id: 301, type: 'page' },
		];

		for (const field of added) {
			byId.set(field.id, field);
		}

		const field = (id: number) => {
			const found = byId.get(id);
			if (!found) {
				throw new Error(
					`Field ${id} is missing from all-form-fields.json`
				);
			}

			return found;
		};

		// Phone, website and email share a row; the credit card starts a new page
		for (const id of [14, 16, 17]) {
			byId.set(id, {
				...field(id),
				layoutGroupId: 'contact',
				layoutGridColumnSpan: 4,
			});
		}
		byId.set(202, { ...field(202), cssClass: 'pagebreak' });

		const order = [
			// Page 1
			200, 1, 2, 210, 3, 4, 5, 6, 7, 8, 9, 212, 10, 208, 209, 211,
			// Page 2
			300, 11, 12, 13, 14, 16, 17, 15, 213, 18, 19, 20, 21, 201, 203, 204,
			28, 29, 205, 30, 31, 32, 33, 78, 81,
			// Page 3
			301, 34, 35, 51, 52, 53, 54, 36, 37, 38, 39, 40, 202, 206, 207,
		];

		return {
			...base,
			markupVersion: 2,
			pagination: {
				type: 'steps',
				pages: ['Your Details', 'Contact and Uploads', 'Your Order'],
			},
			fields: paginate(order.map(field)),
		};
	},

	entry: (site) => {
		const base: Values = readJson(
			'entries/all-form-fields-entries.json'
		)[0];

		// The add-on fields' values, and entry properties the new entry gets its own of
		const dropped =
			/^(22|23|24|25|26|27|41|42|43|44|45|46|47|48|49|50)(\.|$)|^(id|form_id|post_id|date_created)$/;

		return {
			...Object.fromEntries(
				Object.entries(base).filter(([key]) => !dropped.test(key))
			),
			// example.org isn't reachable from E2E
			32: `${site}${IMAGES.photo}|:|Post Image Title|:|Post Image caption|:|Post Image Description`,
			200: 'Visible text response',
			208: 'Shown only when conditional logic is off',
			210: '<p>Rich text with <strong>bold</strong> and <em>italic</em>.</p><ol><li>One</li><li>Two</li></ol>',
			211: 'Administrative response',
			'201.1': '1',
			'201.2': 'I agree to the privacy policy',
			'201.3': '1',
			203: 'Image Two',
			'204.1': 'Multi One',
			'204.3': 'Multi Three',
			205: 'The post body response.',
			'202.1': 'XXXXXXXXXXXX1111',
			'202.4': 'Visa',
		};
	},
};

type Column = {
	id: number;
	// A GF 2.5+ grid span out of 12, or a legacy CSS Ready class
	width: number | string;
	label?: string;
	hidden?: boolean;
	type?: string;
	[property: string]: unknown;
};

/**
 * Lay `rows` out as a form, each row a layout group; a hidden column shows only when field 1 is "Show"
 * @param rows
 * @param legacy
 */
function columnsForm(rows: Column[][], legacy: boolean) {
	const fields = rows.flatMap((row, index) =>
		row.map(({ width, hidden, label, type = 'text', ...field }) => ({
			type,
			label: label ?? `Row ${index + 1} ${type} ${field.id}`,
			layoutGroupId: `row${index + 1}`,
			...(typeof width === 'number'
				? { layoutGridColumnSpan: width }
				: { cssClass: width }),
			...(hidden ? { conditionalLogic: SHOW_IF_TOGGLED } : {}),
			...field,
		}))
	);

	return {
		markupVersion: legacy ? 1 : 2,
		pagination: {
			type: 'steps',
			pages: ['Before the page break', 'After the page break'],
		},
		fields: paginate(fields as Field[]),
	};
}

/**
 * Every text column filled, other than those labelled "Empty"; field 1 decides what conditional logic hides
 * @param form
 * @param toggle
 */
function columnsEntry(form: ReturnType<typeof columnsForm>, toggle: string) {
	return {
		1: toggle,
		...Object.fromEntries(
			form.fields
				.filter(
					(field) =>
						field.type === 'text' &&
						!String(field.label).startsWith('Empty')
				)
				.map((field) => [field.id, `Value ${field.id}`])
		),
	};
}

const toggle: Column = {
	id: 1,
	width: 12,
	type: 'radio',
	label: 'Show the optional fields?',
	choices: [
		{ text: 'Show', value: 'Show' },
		{ text: 'Hide', value: 'Hide' },
	],
};

const section = (id: number): Column => ({
	id,
	width: 12,
	type: 'section',
	label: 'Section between columns',
	description: 'Section description',
});

const html = (id: number, width: number | string): Column => ({
	id,
	width,
	type: 'html',
	label: 'HTML column',
	content: `<p>HTML field ${id} in a column</p>`,
});

const page = (id: number): Column => ({ id, width: 12, type: 'page' });

// Each GF grid span, mixed rows, and rows that end early or leave a gap
const GRID_ROWS: Column[][] = [
	[toggle],
	[
		{
			id: 10,
			width: 6,
			cssClass: 'gf_left_half',
			label: 'Half with a legacy class',
		},
		{
			id: 11,
			width: 6,
			cssClass: 'gf_right_half',
			label: 'Half with a legacy class',
		},
	],
	[
		{ id: 12, width: 4 },
		{ id: 13, width: 4 },
		{ id: 14, width: 4 },
	],
	[
		{ id: 15, width: 4 },
		{ id: 16, width: 8 },
	],
	[
		{ id: 17, width: 8 },
		{ id: 18, width: 4 },
	],
	[
		{ id: 19, width: 3 },
		{ id: 20, width: 3 },
		{ id: 21, width: 3 },
		{ id: 22, width: 3 },
	],
	[
		{ id: 23, width: 9 },
		{ id: 24, width: 3 },
	],
	[
		{ id: 25, width: 6 },
		{ id: 26, width: 3 },
		{ id: 27, width: 3, label: 'Empty quarter' },
	],
	[
		{
			id: 28,
			width: 4,
			layoutSpacerGridColumnSpan: 8,
			label: 'Third with a gap after it',
		},
	],
	[
		{ id: 29, width: 4 },
		{ id: 30, width: 4 },
		{ id: 31, width: 4 },
	],
	[
		{ id: 62, width: 5 },
		{ id: 63, width: 7 },
	],
	[
		{ id: 64, width: 10 },
		{ id: 65, width: 2 },
	],
	[{ id: 66, width: 11, layoutSpacerGridColumnSpan: 1 }],
	[section(32)],
	[html(33, 6), { id: 34, width: 6 }],
	[
		{ id: 35, width: 6 },
		{ id: 36, width: 6 },
	],
	[page(37)],
	[
		{ id: 38, width: 6 },
		{ id: 39, width: 6 },
	],
	[
		{ id: 40, width: 4, hidden: true, label: 'Hidden first third' },
		{ id: 41, width: 4 },
		{ id: 42, width: 4 },
	],
	[
		{ id: 43, width: 4 },
		{ id: 44, width: 4, hidden: true, label: 'Hidden middle third' },
		{ id: 45, width: 4 },
	],
	[
		{ id: 46, width: 4 },
		{ id: 47, width: 4 },
		{ id: 48, width: 4, hidden: true, label: 'Hidden last third' },
	],
	[
		{ id: 49, width: 6, hidden: true, label: 'Hidden row half' },
		{ id: 50, width: 6, hidden: true, label: 'Hidden row half' },
	],
	[
		{ id: 51, width: 6 },
		{ id: 52, width: 6, hidden: true, label: 'Hidden half ending the row' },
	],
	[
		{ id: 53, width: 6 },
		{ id: 54, width: 6 },
	],
	[
		{
			id: 55,
			width: 4,
			hidden: true,
			layoutSpacerGridColumnSpan: 8,
			label: 'Hidden third with a gap after it',
		},
	],
	[
		{ id: 56, width: 4 },
		{ id: 57, width: 4 },
		{ id: 58, width: 4 },
	],
	[
		{ id: 59, width: 6 },
		{ id: 60, width: 6, cssClass: 'exclude', label: 'Excluded half' },
	],
	[{ id: 61, width: 12, label: 'Full width' }],
];

// The CSS Ready classes forms on legacy markup lay out with, including the row they overflow and the faux column a
// hidden field leaves
const LEGACY_ROWS: Column[][] = [
	[{ ...toggle, width: '' }],
	[
		{ id: 10, width: 'gf_left_half' },
		{ id: 11, width: 'gf_right_half' },
	],
	[
		{ id: 12, width: 'gf_left_third' },
		{ id: 13, width: 'gf_middle_third' },
		{ id: 14, width: 'gf_right_third' },
	],
	[
		{ id: 15, width: 'gf_first_quarter' },
		{ id: 16, width: 'gf_second_quarter' },
		{ id: 17, width: 'gf_third_quarter' },
		{ id: 18, width: 'gf_fourth_quarter' },
	],
	[
		{ id: 19, width: 'gf_left_half' },
		{ id: 20, width: 'gf_third_quarter' },
		{ id: 21, width: 'gf_fourth_quarter', label: 'Empty quarter' },
	],
	[
		{ id: 22, width: 'gf_left_third', label: 'Third, then a half' },
		{ id: 23, width: 'gf_left_half' },
		{
			id: 24,
			width: 'gf_right_third',
			label: 'Third that overflows the row',
		},
	],
	[{ id: 25, width: '', label: 'Full width' }],
	[section(26)],
	[
		{ id: 28, width: 'gf_left_half', label: 'Half before an HTML field' },
		html(27, ''),
		{ id: 29, width: 'gf_right_half', label: 'Half after an HTML field' },
	],
	[
		{ id: 30, width: 'gf_left_half' },
		{
			id: 31,
			width: 'gf_right_half pagebreak',
			label: 'Half with the pagebreak class',
		},
	],
	[page(32)],
	[
		{ id: 33, width: 'gf_left_half' },
		{ id: 34, width: 'gf_right_half' },
	],
	[
		{
			id: 35,
			width: 'gf_left_third',
			hidden: true,
			label: 'Hidden first third',
		},
		{ id: 36, width: 'gf_middle_third' },
		{ id: 37, width: 'gf_right_third' },
	],
	[
		{ id: 38, width: 'gf_left_third' },
		{
			id: 39,
			width: 'gf_middle_third',
			hidden: true,
			label: 'Hidden middle third',
		},
		{ id: 40, width: 'gf_right_third' },
	],
	[
		{ id: 41, width: 'gf_left_third' },
		{ id: 42, width: 'gf_middle_third' },
		{
			id: 43,
			width: 'gf_right_third',
			hidden: true,
			label: 'Hidden last third',
		},
	],
	[
		{
			id: 44,
			width: 'gf_left_half',
			hidden: true,
			label: 'Hidden row half',
		},
		{
			id: 45,
			width: 'gf_right_half',
			hidden: true,
			label: 'Hidden row half',
		},
	],
	[
		{ id: 46, width: 'gf_left_half' },
		{
			id: 47,
			width: 'gf_right_half',
			hidden: true,
			label: 'Hidden half ending the row',
		},
	],
	[
		{ id: 48, width: 'gf_left_half' },
		{ id: 49, width: 'gf_right_half' },
	],
	[
		{ id: 50, width: 'gf_left_half' },
		{ id: 51, width: 'gf_right_half exclude', label: 'Excluded half' },
	],
];

/**
 * A columns form, and an entry that meets the hidden fields' conditions or doesn't
 * @param legacy
 * @param shown
 */
function columns(legacy: boolean, shown: boolean): Fixture {
	const form = () => columnsForm(legacy ? LEGACY_ROWS : GRID_ROWS, legacy);

	return {
		title: `Template Snapshot ${legacy ? 'Legacy Columns' : 'Columns'}`,
		form,
		entry: () => columnsEntry(form(), shown ? 'Show' : 'Hide'),
	};
}

export const columnFixtures = {
	columns: { met: columns(false, true), notMet: columns(false, false) },
	'legacy columns': {
		met: columns(true, true),
		notMet: columns(true, false),
	},
};
