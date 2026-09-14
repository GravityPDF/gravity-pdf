import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FontManager from '../../../../../src/assets/js/react/fontManager/components/FontManager';
import { archiveName } from '../../../../../src/assets/js/react/fontManager/api/mock';
import { PACK_ENTRIES } from '../../../../../src/assets/js/react/fontManager/api/mock/catalog';
import { state } from '../../../../../src/assets/js/react/fontManager/api/mock/state';
import { openAddMenu, renderWithStore } from '../renderWithStore';

const JAPANESE = PACK_ENTRIES.find((pack) => pack.entry === 'japanese');

const archive = (bytes = 'zip') =>
	new File([bytes], archiveName(JAPANESE), { type: 'application/zip' });

const open = (hash = '#/fontmanager/', prepare = null) =>
	renderWithStore(<FontManager onActive={jest.fn()} />, { hash, prepare });

const openDialog = async (user) => {
	await openAddMenu(user);
	await user.click(screen.getByText('Install from file'));

	return screen.findByRole('dialog', { name: 'Install from file' });
};

const choose = (user, dialog, file) =>
	user.upload(dialog.querySelector('input[type="file"]'), file);

const installButton = (dialog) =>
	within(dialog).getByRole('button', { name: 'Install' });

describe('Font Manager - installing a pack from a file', () => {
	test('Install waits for a package to be chosen', async () => {
		const user = userEvent.setup();

		open();

		const dialog = await openDialog(user);

		expect(installButton(dialog)).toBeDisabled();

		await choose(user, dialog, archive());

		expect(
			within(dialog).getByText(`${archiveName(JAPANESE)} · 3 B`)
		).toBeTruthy();

		expect(installButton(dialog)).toBeEnabled();
	});

	test('an accepted package queues the install and opens the entry', async () => {
		const user = userEvent.setup();

		open();

		const dialog = await openDialog(user);

		await choose(user, dialog, archive());
		await user.click(installButton(dialog));

		await waitFor(() =>
			expect(window.location.hash).toBe(
				'#/fontmanager/browse/packs/japanese'
			)
		);

		expect(
			screen.queryByRole('dialog', { name: 'Install from file' })
		).toBe(null);
	});

	test('a package no catalogue row claims says so, and stays open', async () => {
		const user = userEvent.setup();

		open();

		const dialog = await openDialog(user);

		await choose(user, dialog, new File(['zip'], 'not-a-pack.zip'));
		await user.click(installButton(dialog));

		expect(
			await within(dialog).findByText(/catalogue does not list/)
		).toBeTruthy();
	});

	/**
	 * The one failure a different file cannot fix, so it is the one that has to name another way in — the host's
	 * own upload cap, which nine of the seventeen packs are over on a 2 MB site
	 */
	test("a package past the host's upload cap points at the offline routes", async () => {
		const user = userEvent.setup();

		open('#/fontmanager/', () => {
			state.upload_cap = 2;
		});

		const dialog = await openDialog(user);

		await choose(user, dialog, archive());
		await user.click(installButton(dialog));

		expect(
			await within(dialog).findByText(/larger than this server accepts/)
		).toBeTruthy();

		expect(
			within(dialog).getByText(/covers the offline routes/)
		).toBeTruthy();

		expect(
			within(dialog)
				.getByRole('link', { name: /Ways to install fonts offline/ })
				.getAttribute('href')
		).toContain('docs.gravitypdf.com');
	});

	test('the packs view offers it and a catalogue of families does not', async () => {
		const user = userEvent.setup();

		open('#/fontmanager/browse/packs');

		expect(
			await screen.findByRole('button', { name: 'Install from file' })
		).toBeTruthy();

		await user.click(screen.getByRole('tab', { name: 'Google Fonts' }));

		/*
		 * Waited on Google's own page being fully drawn — the Category control it alone has, then the cards
		 * that only appear once its results land. Waiting for the button to go would have passed against the
		 * Spinner the pane shows in between, which has no buttons at all.
		 */
		await screen.findByLabelText('Category');
		await waitFor(() =>
			expect(document.querySelector('.gfpdf-fm-cards')).toBeTruthy()
		);

		expect(
			screen.queryByRole('button', { name: 'Install from file' })
		).toBe(null);
	});

	test("a pack's own page offers it", async () => {
		open('#/fontmanager/browse/packs/japanese');

		expect(
			await screen.findByRole('button', { name: 'Install from file' })
		).toBeTruthy();
	});

	test('a display entry has no package to install from', async () => {
		open('#/fontmanager/browse/google/lato');

		await screen.findByLabelText('Font name');

		expect(
			screen.queryByRole('button', { name: 'Install from file' })
		).toBe(null);
	});
});
