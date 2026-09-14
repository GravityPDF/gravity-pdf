import { act, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FontManager from '../../../../../src/assets/js/react/fontManager/components/FontManager';
import { state } from '../../../../../src/assets/js/react/fontManager/api/mock/state';
import { renderWithStore } from '../renderWithStore';

/* What the mocked server ended up holding — the only reading the panel's own state cannot fake */
const savedFaces = (id) => state.rows.find((row) => row.id === id)?.files ?? {};

const drop = (element, file) => {
	const event = new Event('drop', { bubbles: true });

	Object.defineProperty(event, 'dataTransfer', { value: { files: [file] } });
	element.dispatchEvent(event);
};

/* Scoped to the file table: the preview lists the same four names */
const variantRow = (label) =>
	within(document.querySelector('.variants'))
		.getByText(label)
		.closest('.variant-row');

/* The empty pane's button, not the sidebar menu's toggle of the same name */
const addNewFont = async (user) => {
	/* The detail pane waits on the sources read as well as the font list */
	await screen.findByText('Select a font from the list to edit it');

	await user.click(
		within(document.querySelector('.fm-empty-detail')).getByRole('button', {
			name: 'Add new font',
		})
	);
};

describe('Font Manager - adding and editing an uploaded font', () => {
	test('Add font stays disabled until there is a name and a Regular file', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />);

		await addNewFont(user);

		const save = screen.getByRole('button', { name: 'Add font' });

		expect(save).toBeDisabled();

		await user.type(screen.getByLabelText('Font name'), 'House Sans');

		expect(save).toBeDisabled();

		drop(variantRow('Regular'), new File(['x'], 'HouseSans-Regular.ttf'));

		await waitFor(() => expect(save).toBeEnabled());
	});

	test('shows the key the name derives to, live', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />);

		await addNewFont(user);
		await user.type(screen.getByLabelText('Font name'), 'House Sans');

		expect(screen.getByText('font-family: housesans')).toBeTruthy();
	});

	test('refuses a name the key rule would mangle, and one already taken', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />);

		await addNewFont(user);

		const field = screen.getByLabelText('Font name');

		await user.type(field, 'House-Sans');

		expect(
			screen.getByText('Use letters, numbers, and spaces only')
		).toBeTruthy();

		await user.clear(field);
		await user.type(field, 'Brand Sans');

		expect(
			screen.getByText('A font with this name already exists')
		).toBeTruthy();
	});

	test('saves the new row and lands on it', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />);

		await addNewFont(user);
		await user.type(screen.getByLabelText('Font name'), 'House Sans');

		drop(variantRow('Regular'), new File(['x'], 'HouseSans-Regular.ttf'));

		await waitFor(() =>
			expect(
				screen.getByRole('button', { name: 'Add font' })
			).toBeEnabled()
		);

		await user.click(screen.getByRole('button', { name: 'Add font' }));

		expect(await screen.findByText('Edit font')).toBeTruthy();
		expect(window.location.hash).toBe('#/fontmanager/housesans');
	});

	test('asks before replacing a file that is already there, then sends it', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />, {
			hash: '#/fontmanager/brandsans',
		});

		await screen.findByText('Edit font');

		drop(variantRow('Bold'), new File(['x'], 'BrandSans-Heavy.ttf'));

		await user.click(
			await screen.findByRole('button', { name: 'Save changes' })
		);

		const dialog = await screen.findByRole('dialog', {
			name: 'Save changes to font files?',
		});

		await user.click(
			within(dialog).getByRole('button', { name: 'Save changes' })
		);

		await waitFor(() =>
			expect(savedFaces('brandsans').B.path).toBe('BrandSans-Heavy.ttf')
		);
	});

	/**
	 * Removing a face is the other half of the same write, and the route spells it as the face's name present
	 * with an empty value rather than as an absence — so it is worth one test of its own
	 */
	test('removes a face the admin deleted', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />, {
			hash: '#/fontmanager/brandsans',
		});

		await screen.findByText('Edit font');

		await user.click(
			within(variantRow('Bold Italic')).getByRole('button', {
				name: 'Delete Bold Italic',
			})
		);

		await user.click(screen.getByRole('button', { name: 'Save changes' }));

		const dialog = await screen.findByRole('dialog', {
			name: 'Save changes to font files?',
		});

		await user.click(
			within(dialog).getByRole('button', { name: 'Save changes' })
		);

		await waitFor(() => expect(savedFaces('brandsans').BI).toBeUndefined());

		expect(savedFaces('brandsans').R).toBeTruthy();
	});

	/**
	 * `GET /fonts/` answers with a fresh document, so the row object changes identity on every refresh — and
	 * the poller runs one whenever any unrelated install settles. Re-seeding the form on that took the files
	 * the admin had just chosen with it, and they are not retypeable: they come off disk
	 */
	test('keeps an in-progress edit through a background refresh', async () => {
		const { registry } = renderWithStore(
			<FontManager onActive={jest.fn()} />,
			{
				hash: '#/fontmanager/brandsans',
			}
		);

		await screen.findByText('Edit font');

		drop(variantRow('Bold'), new File(['x'], 'BrandSans-Heavy.ttf'));

		await waitFor(() =>
			expect(
				within(variantRow('Bold')).getByText('BrandSans-Heavy.ttf')
			).toBeTruthy()
		);

		/* `act` so the re-render and any effect it triggers have both run before this is judged */
		await act(async () => {
			await registry.dispatch('gravity-pdf/fonts').refreshFonts();
		});

		expect(
			within(variantRow('Bold')).getByText('BrandSans-Heavy.ttf')
		).toBeTruthy();
	});

	test('deletes behind a confirm, and goes back to the list', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />, {
			hash: '#/fontmanager/brandsans',
		});

		await user.click(
			await screen.findByRole('button', { name: 'Delete font' })
		);

		const dialog = await screen.findByRole('dialog', {
			name: 'Delete this font?',
		});

		await user.click(
			within(dialog).getByRole('button', { name: 'Delete font' })
		);

		await waitFor(() =>
			expect(window.location.hash).toBe('#/fontmanager/')
		);
	});

	test('sets the font active through the select the modal was opened from', async () => {
		const onActive = jest.fn();
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={onActive} />, {
			hash: '#/fontmanager/brandsans',
		});

		await user.click(
			await screen.findByRole('button', { name: /Set as active/ })
		);

		expect(onActive).toHaveBeenCalledWith('brandsans');
		expect(await screen.findByText('Active font')).toBeTruthy();
	});
});
