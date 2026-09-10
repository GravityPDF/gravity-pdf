import { createRegistry } from '@wordpress/data';
import { store as fontStore } from '../../../../../src/assets/js/react/fontManager/store';
import { STORE_NAME } from '../../../../../src/assets/js/react/fontManager/constants';

/**
 * The derived selectors resolve their own reads, so they are exercised through a registry rather than called
 * with a bare state object — which is also how every component reaches them.
 */
const day = 86400000;
const iso = (offset) => new Date(Date.now() - offset).toISOString();

const fonts = {
	bundled: [{ id: 'gfpdf-arimo', source: 'bundled', entry: null }],
	custom: [
		{ id: 'brandsans', source: 'custom', entry: null },
		{ id: 'lato', source: 'google', entry: 'lato' },
		{ id: 'latolight', source: 'google', entry: 'lato' },
	],
	groups: [
		{
			source: 'packs',
			entry: 'emoji',
			label: 'Emoji',
			fonts: [{ id: 'notoemoji', source: 'packs', entry: 'emoji' }],
		},
	],
	missing: [],
	can_delete_files: true,
};

const sources = [
	{
		id: 'packs',
		label: 'Language packs',
		synced: iso(3 * day),
		last_attempt: iso(3 * day),
		last_error: '',
		stale: false,
	},
	{
		id: 'google',
		label: 'Google Fonts',
		synced: iso(60 * day),
		last_attempt: iso(day),
		last_error: 'timed out',
		stale: true,
	},
];

const statuses = {
	'packs/emoji': { phase: null, installed: true, update_available: false },
	'google/lato': {
		phase: null,
		installed: true,
		update_available: true,
		update: { installed_version: 'v29', version: 'v30', size: 10 },
	},
};

let select;

beforeEach(() => {
	const registry = createRegistry();

	registry.register(fontStore);

	registry.dispatch(STORE_NAME).receiveFonts(fonts);
	registry.dispatch(STORE_NAME).receiveSources(sources);
	registry.dispatch(STORE_NAME).receiveStatuses(statuses);

	select = registry.select(STORE_NAME);
});

describe('Font Manager - store/selectors.js', () => {
	test('getAllRows() flattens every group and stays identical between calls', () => {
		expect(select.getAllRows().map((row) => row.id)).toEqual([
			'gfpdf-arimo',
			'brandsans',
			'lato',
			'latolight',
			'notoemoji',
		]);

		expect(select.getAllRows()).toBe(select.getAllRows());
	});

	test('getAllRows() tags each row with the bucket it came from', () => {
		const kinds = Object.fromEntries(
			select.getAllRows().map((row) => [row.id, row.kind])
		);

		expect(kinds).toEqual({
			'gfpdf-arimo': 'bundled',
			brandsans: 'custom',
			lato: 'entry',
			latolight: 'entry',
			notoemoji: 'entry',
		});
	});

	test('getRow() finds a row wherever it is grouped', () => {
		expect(select.getRow('notoemoji').entry).toBe('emoji');
		expect(select.getRow('nope')).toBeNull();
	});

	test('getEntryRows() gathers every install of one entry', () => {
		expect(
			select.getEntryRows('google', 'lato').map((row) => row.id)
		).toEqual(['lato', 'latolight']);
	});

	test('getTakenKeys() is every key but the row being edited', () => {
		expect(select.getTakenKeys('lato')).toEqual([
			'gfpdf-arimo',
			'brandsans',
			'latolight',
			'notoemoji',
		]);
	});

	test('getInstallStatus() is null for an entry nothing has touched', () => {
		expect(select.getInstallStatus('packs/indic')).toBeNull();
		expect(select.getInstallStatus('packs/emoji').installed).toBe(true);
	});

	test('getSyncSummary() reports the oldest sync, and whether anything is stale', () => {
		const summary = select.getSyncSummary();

		expect(summary.never).toBe(false);
		expect(summary.stale).toBe(true);
		expect(summary.failed).toBe(true);
		expect(summary.synced).toBe(sources[1].synced);
	});

	test('getSyncSummary() says so when a catalogue has never been downloaded', () => {
		const registry = createRegistry();

		registry.register(fontStore);
		registry
			.dispatch(STORE_NAME)
			.receiveSources([{ id: 'packs', synced: null, stale: true }]);

		expect(registry.select(STORE_NAME).getSyncSummary().never).toBe(true);
	});

	test('getUpdates() is the status objects that carry one, with no join', () => {
		expect(select.getUpdates()).toEqual([
			{
				id: 'google/lato',
				source: 'google',
				entry: 'lato',
				installed_version: 'v29',
				version: 'v30',
				size: 10,
			},
		]);
	});

	test('getSourceLabels() maps every registered source', () => {
		expect(select.getSourceLabels()).toEqual({
			packs: 'Language packs',
			google: 'Google Fonts',
		});
	});

	test('getSource() and getSourceEntries() read what has been resolved', () => {
		expect(select.getSource('google').label).toBe('Google Fonts');
		expect(select.getSource('nope')).toBeNull();
		expect(select.getSourceEntries('google', { page: '1' })).toBeNull();
	});

	test('canDeleteFiles() follows the route field', () => {
		expect(select.canDeleteFiles()).toBe(true);
	});
});
