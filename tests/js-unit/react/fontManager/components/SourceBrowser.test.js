import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FontManager from '../../../../../src/assets/js/react/fontManager/components/FontManager';
import { state } from '../../../../../src/assets/js/react/fontManager/api/mock/state';
import { renderWithStore } from '../renderWithStore';

const open = (hash = '#/fontmanager/browse/google', prepare = null) =>
	renderWithStore(<FontManager onActive={jest.fn()} />, { hash, prepare });

const findCards = async () => {
	await waitFor(() =>
		expect(document.querySelector('.gfpdf-fm-cards')).toBeTruthy()
	);

	return document.querySelector('.gfpdf-fm-cards');
};

describe('Font Manager - the source browser', () => {
	test('offers every source as a tab, and switches between them', async () => {
		const user = userEvent.setup();

		open();

		await findCards();

		await user.click(screen.getByRole('tab', { name: 'Language packs' }));

		await waitFor(() =>
			expect(window.location.hash).toBe('#/fontmanager/browse/packs')
		);
	});

	test('a source with no catalogue hides the list and asks for a refresh', async () => {
		open('#/fontmanager/browse/google', () => {
			state.sync.google = {
				synced: null,
				last_attempt: null,
				last_error: '',
			};
		});

		expect(
			await screen.findByText(
				"The Google Fonts catalogue hasn't been downloaded yet. Refresh to get it from Gravity PDF."
			)
		).toBeTruthy();

		expect(document.querySelector('.gfpdf-fm-cards')).toBeNull();
	});

	test('a stale catalogue keeps its list, and says what went wrong', async () => {
		open();

		expect(
			await screen.findByText(
				'The catalogue server did not respond in time.'
			)
		).toBeTruthy();
		expect(await findCards()).toBeTruthy();
	});

	test('searches the server rather than the page', async () => {
		const user = userEvent.setup();

		open();

		await findCards();

		await user.type(screen.getByLabelText('Search the catalogue'), 'mono');

		await waitFor(() =>
			expect(screen.getByText(/of 2 families/)).toBeTruthy()
		);
	});

	test('an empty result says how to get out of it', async () => {
		const user = userEvent.setup();

		open();

		await findCards();

		await user.type(screen.getByLabelText('Search the catalogue'), 'zzzz');

		expect(
			await screen.findByText(
				'No families match. Try a different search, or clear the filters.'
			)
		).toBeTruthy();
	});

	test('Clear filters appears only once a filter is on', async () => {
		const user = userEvent.setup();

		open();

		await findCards();

		expect(
			screen.queryByRole('button', { name: 'Clear filters' })
		).toBeNull();

		await user.selectOptions(screen.getByLabelText('Language'), 'arabic');

		const clear = await screen.findByRole('button', {
			name: 'Clear filters',
		});

		await user.click(clear);

		await waitFor(() =>
			expect(
				screen.queryByRole('button', { name: 'Clear filters' })
			).toBeNull()
		);
	});

	test('an installed family reads Installed, or offers the update it is behind', async () => {
		open();

		const list = await findCards();
		const card = within(list).getByText('Lato').closest('.gfpdf-fm-card');

		expect(
			within(card).getByRole('button', { name: 'Update' })
		).toBeTruthy();

		const other = within(list)
			.getByText('Roboto')
			.closest('.gfpdf-fm-card');

		expect(
			within(other).getByRole('button', { name: 'Install' })
		).toBeTruthy();
	});

	test('the packs view offers no category filter, because packs have no category', async () => {
		open('#/fontmanager/browse/packs');

		await findCards();

		expect(screen.queryByLabelText('Category')).toBeNull();
		expect(screen.getByLabelText('Language')).toBeTruthy();
	});
});
