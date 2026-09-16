import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ROLES } from '../../../../../src/assets/js/react/fontManager/constants';
import EmptyState from '../../../../../src/assets/js/react/fontManager/components/EmptyState';
import FontPreview from '../../../../../src/assets/js/react/fontManager/components/FontPreview';
import FontRow from '../../../../../src/assets/js/react/fontManager/components/FontRow';
import FontVariantRow from '../../../../../src/assets/js/react/fontManager/components/FontVariantRow';
import InstallProgress from '../../../../../src/assets/js/react/fontManager/components/InstallProgress';
import ScriptChips from '../../../../../src/assets/js/react/fontManager/components/ScriptChips';
import TemplateUse from '../../../../../src/assets/js/react/fontManager/components/TemplateUse';
import UpdateRow from '../../../../../src/assets/js/react/fontManager/components/UpdateRow';
import VariantRoles from '../../../../../src/assets/js/react/fontManager/components/VariantRoles';
import { syncText } from '../../../../../src/assets/js/react/fontManager/components/SyncLine';

describe('Font Manager - FontRow', () => {
	const row = { id: 'lato', label: 'Lato', sub: '4/4 variants' };

	test('shows the name and the line under it', () => {
		render(<FontRow {...row} onSelect={jest.fn()} />);

		expect(screen.getByText('Lato')).toBeTruthy();
		expect(screen.getByText('4/4 variants')).toBeTruthy();
	});

	test('tags the font the underlying select is pointed at', () => {
		const { rerender, container } = render(
			<FontRow {...row} onSelect={jest.fn()} />
		);

		expect(screen.queryByText('Active')).toBeNull();

		rerender(<FontRow {...row} active onSelect={jest.fn()} />);

		expect(screen.getByText('Active')).toBeTruthy();
		expect(container.querySelector('.font-row-active-bar')).toBeTruthy();
	});

	test('dims a font hidden on this site', () => {
		const { container } = render(
			<FontRow {...row} dimmed onSelect={jest.fn()} />
		);

		expect(container.querySelector('.font-row.is-dimmed')).toBeTruthy();
	});

	test('draws emoji rather than typing it, from the row’s own scripts', () => {
		const { container, rerender } = render(
			<FontRow {...row} scripts="und-Latn" onSelect={jest.fn()} />
		);

		expect(container.querySelector('.font-swatch svg')).toBeNull();

		rerender(<FontRow {...row} scripts="und-Zsye" onSelect={jest.fn()} />);

		expect(container.querySelector('.font-swatch svg')).toBeTruthy();
	});

	test('opens on click and on the keyboard', async () => {
		const onSelect = jest.fn();
		const user = userEvent.setup();

		render(<FontRow {...row} onSelect={onSelect} />);

		const button = screen.getByRole('button');

		await user.click(button);
		button.focus();
		await user.keyboard('{Enter}');

		expect(onSelect).toHaveBeenCalledTimes(2);
	});
});

describe('Font Manager - ScriptChips', () => {
	test('is nothing at all for an entry that claims no scripts', () => {
		const { container } = render(<ScriptChips scripts="" />);

		expect(container.firstChild).toBeNull();
	});

	test('names the scripts rather than showing their tags', () => {
		render(<ScriptChips scripts="und-Arab,und-Hebr" />);

		expect(screen.getByText('Arabic')).toBeTruthy();
		expect(screen.getByText('Hebrew')).toBeTruthy();
	});

	test('counts the ones it had no room for', () => {
		render(
			<ScriptChips
				scripts="und-Arab,und-Hebr,und-Thai,und-Grek"
				limit={2}
			/>
		);

		expect(screen.getByText('+2')).toBeTruthy();
	});

	test('falls back to the tag for a script it has no name for', () => {
		render(<ScriptChips scripts="und-Zzzz" />);

		expect(screen.getByText('und-Zzzz')).toBeTruthy();
	});
});

describe('Font Manager - InstallProgress', () => {
	test('says nothing when nothing is happening', () => {
		expect(
			render(<InstallProgress status={null} />).container.firstChild
		).toBeNull();
		expect(
			render(<InstallProgress status={{ phase: null }} />).container
				.firstChild
		).toBeNull();
	});

	test('counts files for a coverage entry and stays vague for a display one', () => {
		const status = { phase: 'installing', files_done: 3 };

		render(<InstallProgress status={status} total={26} />);
		expect(screen.getByText('Installing · 3 of 26')).toBeTruthy();

		render(<InstallProgress status={status} />);
		expect(screen.getByText('Installing…')).toBeTruthy();
	});

	test('says updating when the entry is already installed', () => {
		render(<InstallProgress status={{ phase: 'queued' }} updating />);

		expect(screen.getByText('Updating…')).toBeTruthy();
	});

	test('offers Retry for a stuck batch and for a failed one', async () => {
		const onRetry = jest.fn();
		const user = userEvent.setup();

		const { rerender } = render(
			<InstallProgress
				status={{ phase: 'installing', stuck: true }}
				onRetry={onRetry}
			/>
		);

		expect(screen.getByText('Stuck')).toBeTruthy();
		await user.click(screen.getByRole('button', { name: 'Retry' }));

		rerender(
			<InstallProgress
				status={{ phase: 'failed', error: 'Download failed' }}
				onRetry={onRetry}
			/>
		);

		expect(screen.getByText('Download failed')).toBeTruthy();
		await user.click(screen.getByRole('button', { name: 'Retry' }));

		expect(onRetry).toHaveBeenCalledTimes(2);
	});
});

describe('Font Manager - VariantRoles', () => {
	test('is nothing for an entry that offers no choice', () => {
		const { container } = render(
			<VariantRoles styles={null} value={{}} onChange={jest.fn()} />
		);

		expect(container.firstChild).toBeNull();
	});

	test('offers one select per role, each with a None', () => {
		render(
			<VariantRoles
				styles="regular,italic,700"
				value={{ R: 'regular' }}
				onChange={jest.fn()}
			/>
		);

		ROLES.forEach((role) => {
			expect(screen.getByLabelText(role.label)).toBeTruthy();
		});

		expect(screen.getAllByRole('option', { name: 'None' })).toHaveLength(4);
	});

	test('groups the weights by upright and italic', () => {
		const { container } = render(
			<VariantRoles
				styles="regular,italic,700"
				value={{}}
				onChange={jest.fn()}
			/>
		);

		expect(
			[...container.querySelectorAll('optgroup')].map((group) =>
				group.getAttribute('label')
			)
		).toEqual(expect.arrayContaining(['Upright', 'Italic']));
	});

	test('reports a change as the whole mapping', async () => {
		const onChange = jest.fn();
		const user = userEvent.setup();

		render(
			<VariantRoles
				styles="regular,700"
				value={{ R: 'regular' }}
				onChange={onChange}
			/>
		);

		await user.selectOptions(screen.getByLabelText('Bold'), '700');

		expect(onChange).toHaveBeenCalledWith({ R: 'regular', B: '700' });
	});
});

describe('Font Manager - TemplateUse', () => {
	test('shows the key a template writes', () => {
		render(<TemplateUse fontKey="latolight" />);

		expect(screen.getByText('font-family: latolight')).toBeTruthy();
	});
});

describe('Font Manager - FontVariantRow', () => {
	const role = ROLES[0];

	test('says which faces have no file yet', () => {
		const { container } = render(
			<FontVariantRow
				role={role}
				filename={null}
				onFile={jest.fn()}
				onDelete={jest.fn()}
			/>
		);

		expect(screen.getByText('No .ttf file added')).toBeTruthy();
		expect(container.querySelector('.variant-row.missing')).toBeTruthy();
		expect(screen.getByRole('button', { name: 'Upload' })).toBeTruthy();
	});

	test('offers Replace once there is a file, and no Delete for the required face', () => {
		render(
			<FontVariantRow
				role={role}
				filename="Lato-Regular.ttf"
				onFile={jest.fn()}
				onDelete={jest.fn()}
			/>
		);

		expect(screen.getByText('Lato-Regular.ttf')).toBeTruthy();
		expect(screen.getByRole('button', { name: 'Replace' })).toBeTruthy();
		expect(screen.queryByRole('button', { name: /Delete/ })).toBeNull();
	});

	test('lets an optional face be deleted', async () => {
		const onDelete = jest.fn();
		const user = userEvent.setup();

		render(
			<FontVariantRow
				role={ROLES[2]}
				filename="Lato-Bold.ttf"
				onFile={jest.fn()}
				onDelete={onDelete}
			/>
		);

		await user.click(screen.getByRole('button', { name: 'Delete Bold' }));

		expect(onDelete).toHaveBeenCalledTimes(1);
	});

	test('takes a file dropped on the row itself', () => {
		const onFile = jest.fn();
		const { container } = render(
			<FontVariantRow
				role={role}
				filename={null}
				onFile={onFile}
				onDelete={jest.fn()}
			/>
		);

		const row = container.querySelector('.variant-row');
		const file = new File(['x'], 'Lato-Regular.ttf');

		fireDrop(row, file);

		expect(onFile).toHaveBeenCalledWith(file);
	});
});

function fireDrop(element, file) {
	const event = new Event('drop', { bubbles: true });

	Object.defineProperty(event, 'dataTransfer', { value: { files: [file] } });
	element.dispatchEvent(event);
}

describe('Font Manager - FontPreview', () => {
	test('shows all four faces, and says which have no file', () => {
		render(
			<FontPreview
				fontKey="lato"
				files={{ R: { role: 'R', url: null } }}
				size={12}
				sample=""
			/>
		);

		ROLES.forEach((role) => {
			expect(screen.getByText(role.label)).toBeTruthy();
		});

		expect(screen.getAllByText('No file added')).toHaveLength(3);
	});

	test('the typed sample replaces the line in every row', () => {
		render(
			<FontPreview
				fontKey="lato"
				files={{
					R: { role: 'R', url: null },
					B: { role: 'B', url: null },
				}}
				size={12}
				sample="Annual Report"
			/>
		);

		expect(screen.getAllByText('Annual Report')).toHaveLength(2);
	});
});

describe('Font Manager - UpdateRow', () => {
	const update = {
		id: 'google/lato',
		source: 'google',
		entry: 'lato',
		installed_version: 'v29',
		version: 'v30',
		files: 4,
		size: 2734376,
		notes: 'Rebuilt from the variable font',
		released: '2026-07-14',
	};

	test('says what the version is and why it exists', () => {
		render(
			<UpdateRow
				update={update}
				status={null}
				source="Google Fonts"
				onUpdate={jest.fn()}
			/>
		);

		expect(screen.getByText('v29 → v30 · 4 files · 2.6 MB')).toBeTruthy();
		expect(
			screen.getByText('Rebuilt from the variable font (2026-07-14)')
		).toBeTruthy();
	});

	test('shows progress instead of a button while it runs', () => {
		render(
			<UpdateRow
				update={update}
				status={{ phase: 'installing' }}
				source="Google Fonts"
				onUpdate={jest.fn()}
			/>
		);

		expect(screen.getByText('Updating…')).toBeTruthy();
		expect(screen.queryByRole('button', { name: 'Update' })).toBeNull();
	});
});

describe('Font Manager - EmptyState', () => {
	test('says its piece and offers the way forward', async () => {
		const onAdd = jest.fn();
		const user = userEvent.setup();

		render(
			<EmptyState glyph="Aa" text="Nothing selected">
				<button type="button" onClick={onAdd}>
					Add new font
				</button>
			</EmptyState>
		);

		expect(screen.getByText('Nothing selected')).toBeTruthy();
		expect(screen.getByText('Aa')).toBeTruthy();

		await user.click(screen.getByRole('button', { name: 'Add new font' }));

		expect(onAdd).toHaveBeenCalledTimes(1);
	});

	test('wraps itself in a detail section unless told not to', () => {
		const { container, rerender } = render(<EmptyState text="Gone" />);

		expect(container.querySelector('.fm-detail')).toBeTruthy();

		rerender(<EmptyState text="Gone" plain />);

		expect(container.querySelector('.fm-detail')).toBeNull();
		expect(container.querySelector('.fm-empty-detail')).toBeTruthy();
	});
});

describe('Font Manager - syncText()', () => {
	test('says when the catalogue has never been downloaded', () => {
		expect(syncText({ never: true, synced: null })).toBe(
			'Catalogue not downloaded yet'
		);
	});

	test('reports the oldest sync', () => {
		expect(
			syncText({
				never: false,
				synced: new Date(Date.now() - 3 * 86400000).toISOString(),
			})
		).toBe('Updated 3 days ago');
	});

	test('adds the failure when the last attempt was newer than the sync', () => {
		expect(
			syncText({
				never: false,
				failed: true,
				synced: new Date(Date.now() - 3 * 86400000).toISOString(),
			})
		).toBe('Updated 3 days ago · last refresh failed');
	});
});
