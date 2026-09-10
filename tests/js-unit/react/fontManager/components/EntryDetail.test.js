import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FontManager from '../../../../../src/assets/js/react/fontManager/components/FontManager';
import { renderWithStore } from '../renderWithStore';

const open = (hash) =>
	renderWithStore(<FontManager onActive={jest.fn()} />, { hash });

describe('Font Manager - a catalogue entry', () => {
	test('a pack that is not installed offers one Install, priced', async () => {
		open('#/fontmanager/browse/packs/korean');

		expect(
			await screen.findByRole('button', { name: 'Install · 11.9 MB' })
		).toBeTruthy();
	});

	test('installing a pack shows its files landing one by one', async () => {
		const user = userEvent.setup();

		open('#/fontmanager/browse/packs/korean');

		await user.click(
			await screen.findByRole('button', { name: /Install/ })
		);

		await waitFor(() =>
			expect(screen.getByText(/Installing · \d+ of 4/)).toBeTruthy()
		);
	});

	test('an installed pack offers Remove, behind a confirm that says what goes', async () => {
		const user = userEvent.setup();

		open('#/fontmanager/browse/packs/emoji');

		await user.click(await screen.findByRole('button', { name: 'Remove' }));

		const dialog = await screen.findByRole('dialog', {
			name: 'Remove this font?',
		});

		expect(
			within(dialog).getByText(/Every font in this pack is removed/)
		).toBeTruthy();

		await user.click(
			within(dialog).getByRole('button', { name: 'Remove' })
		);

		await waitFor(() =>
			expect(window.location.hash).toBe('#/fontmanager/')
		);
	});

	test('a second install of a display family starts with an empty name', async () => {
		open('#/fontmanager/browse/google/lato');

		await screen.findByText('Google Fonts · OFL-1.1');

		expect(screen.getByLabelText('Font name')).toHaveValue('');
		expect(screen.getByPlaceholderText('e.g. Lato Light')).toBeTruthy();
	});

	test('the row of a display family edits that install alone', async () => {
		open('#/fontmanager/lato');

		expect(await screen.findByText('font-family: lato')).toBeTruthy();
		expect(
			screen.getByRole('button', { name: 'Remove this install' })
		).toBeTruthy();
	});

	test('changing a role turns Save into Apply styles', async () => {
		const user = userEvent.setup();

		open('#/fontmanager/lato');

		await screen.findByText('font-family: lato');

		await user.selectOptions(screen.getByLabelText('Bold'), '900');

		expect(
			screen.getByRole('button', { name: 'Apply styles' })
		).toBeTruthy();
	});

	test('an update says what the version is before anything re-renders', async () => {
		open('#/fontmanager/lato');

		expect(
			await screen.findByText('Update available: v29 → v30')
		).toBeTruthy();
	});

	test('a deep link to an entry the catalogue has dropped says so', async () => {
		open('#/fontmanager/browse/packs/nosuchpack');

		expect(
			await screen.findByText('This font is no longer available')
		).toBeTruthy();
	});
});
