import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FontManager from '../../../../../src/assets/js/react/fontManager/components/FontManager';
import { renderWithStore } from '../renderWithStore';

const open = (props = {}) =>
	renderWithStore(<FontManager onActive={jest.fn()} {...props} />);

describe('Font Manager - the modal', () => {
	test('is closed while the hash belongs to somebody else', () => {
		const { container } = renderWithStore(<FontManager />, {
			hash: '#/templates/',
		});

		expect(container.innerHTML).toBe('');
	});

	test('lists the installed fonts in three groups', async () => {
		open();

		expect(await screen.findByText('Bundled')).toBeTruthy();
		expect(screen.getByText('Custom')).toBeTruthy();
		expect(screen.getByText('Language packs')).toBeTruthy();

		expect(await screen.findByText('Arimo')).toBeTruthy();
		expect(screen.getByText('Brand Sans')).toBeTruthy();
		expect(
			within(document.querySelector('.fm-list')).getByText('Emoji', {
				selector: '.font-name',
			})
		).toBeTruthy();
	});

	test('counts what is installed, and says how old the catalogue is', async () => {
		open();

		expect(await screen.findByText('5 fonts installed')).toBeTruthy();
		expect(
			screen.getByText(/Updated 61 days ago · last refresh failed/)
		).toBeTruthy();
	});

	test('narrows the list by name and by source', async () => {
		const user = userEvent.setup();

		open();

		await screen.findByText('Brand Sans');

		await user.type(screen.getByLabelText('Search fonts'), 'brand');

		expect(screen.queryByText('Emoji')).toBeNull();
		expect(screen.getByText('Brand Sans')).toBeTruthy();

		await user.clear(screen.getByLabelText('Search fonts'));
		await user.type(screen.getByLabelText('Search fonts'), 'google');

		expect(screen.getByText('Lato')).toBeTruthy();
		expect(screen.queryByText('Arimo')).toBeNull();
	});

	test('opens the bundled face read-only, and warns about the missing emoji pack', async () => {
		const user = userEvent.setup();

		open();

		await user.click(await screen.findByText('Arimo'));

		expect(
			await screen.findByText('Bundled with Gravity PDF · gfpdf-arimo')
		).toBeTruthy();
		expect(
			screen.queryByRole('button', { name: 'Delete font' })
		).toBeNull();
	});

	test('opens an uploaded font for editing', async () => {
		const user = userEvent.setup();

		open();

		await user.click(await screen.findByText('Brand Sans'));

		expect(await screen.findByText('Edit font')).toBeTruthy();
		expect(screen.getByLabelText('Font name')).toHaveValue('Brand Sans');
		expect(screen.getByText('font-family: brandsans')).toBeTruthy();
	});

	test('an unknown font key lands on the not-found pane rather than a blank one', async () => {
		open();

		window.location.hash = '#/fontmanager/nosuchfont';

		expect(
			await screen.findByText('This font is no longer available')
		).toBeTruthy();
	});

	test('browses a source, and installs from a card', async () => {
		const user = userEvent.setup();

		open();

		window.location.hash = '#/fontmanager/browse/packs';

		expect(await screen.findByText('Indic scripts')).toBeTruthy();

		const card = screen
			.getByText('Indic scripts')
			.closest('.gfpdf-fm-card');

		await user.click(within(card).getByRole('button', { name: 'Install' }));

		await waitFor(() =>
			expect(within(card).getByText(/Installing/)).toBeTruthy()
		);
	});

	test('filters the browser by category and says which filter is on', async () => {
		const user = userEvent.setup();

		open();

		window.location.hash = '#/fontmanager/browse/google';

		expect(await screen.findByText('Lato')).toBeTruthy();

		await user.selectOptions(
			screen.getByLabelText('Category'),
			'monospace'
		);

		await waitFor(() =>
			expect(screen.getByText(/of 4 families · Monospace/)).toBeTruthy()
		);

		/* Scoped to the card list: Lato is installed, so the sidebar lists it whatever the browser is showing */
		const cards = document.querySelector('.gfpdf-fm-cards');

		expect(within(cards).queryByText('Lato')).toBeNull();
		expect(within(cards).getByText('Roboto Mono')).toBeTruthy();
	});

	test('the entry page of a display family asks for a name and the four roles', async () => {
		open();

		window.location.hash = '#/fontmanager/browse/google/montserrat';

		expect(await screen.findByText('Google Fonts · OFL-1.1')).toBeTruthy();
		expect(screen.getByLabelText('Font name')).toHaveValue('Montserrat');
		expect(screen.getByText('font-family: montserrat')).toBeTruthy();
		expect(screen.getByLabelText('Bold Italic')).toBeTruthy();
	});

	test('the entry page of a pack shows what is inside it instead', async () => {
		open();

		window.location.hash = '#/fontmanager/browse/packs/emoji';

		expect(await screen.findByText('Fonts in this pack')).toBeTruthy();
		expect(screen.getByText('Noto Emoji')).toBeTruthy();
		expect(screen.queryByLabelText('Font name')).toBeNull();
	});

	test('the updates panel lists what is behind the catalogue', async () => {
		open();

		window.location.hash = '#/fontmanager/updates';

		expect(await screen.findByText('Updates')).toBeTruthy();
		expect(screen.getByText(/v29 → v30/)).toBeTruthy();
		const panel = document.querySelector('.gfpdf-fm-update-all');

		expect(
			within(panel).getByRole('button', { name: /Update all/ })
		).toBeTruthy();
	});

	test('the language settings panel offers the four keys and the map', async () => {
		open();

		window.location.hash = '#/fontmanager/settings';

		expect(
			await screen.findByLabelText('Default document language')
		).toBeTruthy();
		expect(screen.getByLabelText('Document script')).toBeTruthy();
		expect(
			screen.getByLabelText('Install fonts automatically')
		).toBeTruthy();
		expect(screen.getByText('Bundled · Arimo')).toBeTruthy();
	});

	test('hides the Active controls when no font select is on the page', async () => {
		renderWithStore(<FontManager />);

		expect(await screen.findByText('Arimo')).toBeTruthy();
		expect(
			screen.queryByRole('button', { name: /Set as active/ })
		).toBeNull();
	});
});
