import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FontManager from '../../../../../src/assets/js/react/fontManager/components/FontManager';
import LanguageSettings, {
	applyOverrides,
	derive,
} from '../../../../../src/assets/js/react/fontManager/components/LanguageSettings';
import { renderWithStore } from '../renderWithStore';

const groups = [
	{
		group: 'packs/japanese',
		label: 'Japanese',
		rows: [
			{
				code: 'ja',
				label: 'Japanese',
				default_font: 'notosansjp',
				font: 'notosansjp',
			},
		],
	},
	{
		group: 'bundled',
		label: 'Bundled · Arimo',
		rows: [
			{
				code: 'ru',
				label: 'Russian',
				default_font: 'gfpdf-arimo',
				font: 'gfpdf-arimo',
			},
		],
	},
];

describe('Font Manager - applyOverrides()', () => {
	test('leaves the map alone when nothing has been edited', () => {
		expect(applyOverrides(groups, {})).toEqual(groups);
	});

	test('folds an edit into the row it belongs to', () => {
		expect(applyOverrides(groups, { ru: '*' })[1].rows[0].font).toBe('*');
	});

	test('puts a code the server never listed into Other languages', () => {
		const result = applyOverrides(groups, { th: '*' });

		expect(result[2]).toMatchObject({
			group: 'other',
			rows: [{ code: 'th', font: '*', default_font: '*' }],
		});
	});
});

describe('Font Manager - derive()', () => {
	test('sends only the rows that differ from the default', () => {
		expect(derive(applyOverrides(groups, { ru: '*' }))).toEqual({
			ru: '*',
		});
	});

	test('sends nothing when every row is at its default', () => {
		expect(derive(groups)).toEqual({});
	});
});

/* Found by its code: the label sits beside it, and the language select lists every name again */
const languageRow = (code) =>
	within(document.querySelector('.gfpdf-fm-language-table'))
		.getByText(code)
		.closest('.gfpdf-fm-language-row');

describe('Font Manager - the language settings panel', () => {
	test('offers a reset once a row is overridden, and saves what changed', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />, {
			hash: '#/fontmanager/settings',
		});

		await screen.findByText('Bundled · Arimo');

		const russian = languageRow('ru');

		expect(
			within(russian).queryByLabelText('Reset to the default')
		).toBeNull();

		await user.selectOptions(
			within(russian).getByRole('combobox'),
			'brandsans'
		);

		expect(
			within(russian).getByLabelText('Reset to the default')
		).toBeTruthy();

		await user.click(screen.getByRole('button', { name: 'Save settings' }));

		await waitFor(() =>
			expect(
				within(document.querySelector('.gfpdf-fm-snackbars')).getByText(
					'Language settings saved'
				)
			).toBeTruthy()
		);
	});

	test('filters the table without unmounting its rows', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />, {
			hash: '#/fontmanager/settings',
		});

		await screen.findByText('Bundled · Arimo');

		await user.type(
			screen.getByPlaceholderText('Filter by name or code…'),
			'russ'
		);

		const russian = languageRow('ru');
		const greek = languageRow('el');

		expect(russian.hidden).toBe(false);
		expect(greek.hidden).toBe(true);
	});

	test('locks the auto-install toggle when the constant owns it', async () => {
		renderWithStore(<LanguageSettings onBack={jest.fn()} />);

		const toggle = await screen.findByLabelText(
			'Install fonts automatically'
		);

		expect(toggle).toBeEnabled();
	});
});
