import { screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import FontManager from '../../../../../src/assets/js/react/fontManager/components/FontManager';
import { renderWithStore } from '../renderWithStore';

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

	test('asks before replacing a file that is already there', async () => {
		const user = userEvent.setup();

		renderWithStore(<FontManager onActive={jest.fn()} />, {
			hash: '#/fontmanager/brandsans',
		});

		await screen.findByText('Edit font');

		drop(variantRow('Bold'), new File(['x'], 'BrandSans-Heavy.ttf'));

		await user.click(
			await screen.findByRole('button', { name: 'Save changes' })
		);

		expect(
			await screen.findByText('Save changes to font files?')
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
