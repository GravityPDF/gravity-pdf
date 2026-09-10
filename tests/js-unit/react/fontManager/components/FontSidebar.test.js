import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FontManager from '../../../../../src/assets/js/react/fontManager/components/FontManager';
import { renderWithStore } from '../renderWithStore';

const openAddMenu = async (user) => {
	await screen.findByText('Bundled');

	await user.click(
		document.querySelector('.components-dropdown-menu__toggle')
	);
};

describe('Font Manager - the sidebar', () => {
	test('builds the browse items from the registered sources alone', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />);

		await openAddMenu(user);

		expect(screen.getByText('Upload font files')).toBeTruthy();
		expect(screen.getByText('Browse Language packs')).toBeTruthy();
		expect(screen.getByText('Browse Google Fonts')).toBeTruthy();
	});

	test('a browse item opens that source', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />);

		await openAddMenu(user);
		await user.click(screen.getByText('Browse Google Fonts'));

		await waitFor(() =>
			expect(window.location.hash).toBe('#/fontmanager/browse/google')
		);
	});

	test('banners the pending updates, and the banner opens the panel', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />);

		const banner = await screen.findByText(
			'1 update available · Update all'
		);

		await user.click(banner);

		await waitFor(() =>
			expect(window.location.hash).toBe('#/fontmanager/updates')
		);
	});

	test('a pack row carries its script chips', async () => {
		renderWithStore(<FontManager onActive={jest.fn()} />);

		const row = (
			await screen.findByText('Emoji', { selector: '.font-name' })
		).closest('.font-row');

		expect(
			within(row).getByText('Emoji', { selector: '.gfpdf-fm-chip' })
		).toBeTruthy();
	});

	test('a pack row opens the entry page rather than a font key', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />);

		await user.click(
			await screen.findByText('Emoji', { selector: '.font-name' })
		);

		await waitFor(() =>
			expect(window.location.hash).toBe(
				'#/fontmanager/browse/packs/emoji'
			)
		);
	});

	test('a custom row counts the faces it has, and names its source', async () => {
		renderWithStore(<FontManager onActive={jest.fn()} />);

		await screen.findByText('Brand Sans');

		expect(screen.getByText('4/4 variants')).toBeTruthy();
		expect(screen.getByText('4/4 variants · Google Fonts')).toBeTruthy();
	});
});
