import {
	mockMiddleware,
	mockOnly,
} from '../../../../../src/assets/js/react/fontManager/api/mock';
import {
	FILE_TICK,
	resetState,
	state,
} from '../../../../../src/assets/js/react/fontManager/api/mock/state';

const next = jest.fn();

/**
 * Call one route and let its latency elapse
 *
 * @param {string}  path
 * @param {string}  method
 * @param {?Object} data
 *
 * @return {Promise} What the route answered
 */
const call = async (path, method = 'GET', data) => {
	/* Settle first so a rejection never sits unhandled while the fake clock is being wound forward */
	const settled = mockMiddleware(
		{ path: '/gravity-pdf/v1' + path, method, data },
		next
	).then(
		(value) => ({ value }),
		(failure) => ({ failure })
	);

	await jest.advanceTimersByTimeAsync(500);

	const result = await settled;

	if (result.failure) {
		throw result.failure;
	}

	return result.value;
};

describe('Font Manager - the mocked /fonts routes', () => {
	beforeEach(() => {
		jest.useFakeTimers();
		resetState();
	});

	afterEach(() => {
		jest.useRealTimers();
	});

	test('leaves every other request to the rest of the chain', () => {
		mockMiddleware({ path: '/wp/v2/posts' }, next);

		expect(next).toHaveBeenCalledTimes(1);
	});

	test('GET /fonts/ carries the three groups plus the two route fields', async () => {
		const fonts = await call('/fonts/');

		expect(Object.keys(fonts).sort()).toEqual([
			'bundled',
			'can_delete_files',
			'custom',
			'groups',
			'missing',
		]);

		expect(fonts.bundled[0].id).toBe('gfpdf-arimo');
		expect(fonts.groups[0].entry).toBe('emoji');
		expect(fonts.custom.map((row) => row.id)).toContain('brandsans');
	});

	test('GET /fonts/sources describes every source without fetching', async () => {
		const sources = await call('/fonts/sources');

		expect(sources.map((source) => source.id)).toEqual(['packs', 'google']);
		expect(sources[0]).toMatchObject({
			label: 'Language packs',
			stale: false,
		});

		/* A count, as the route returns: every pack is a coverage entry, no Google family is */
		expect(sources[0].coverage).toBe(sources[0].total);
		expect(sources[1].coverage).toBe(0);

		expect(sources[1].stale).toBe(true);
		expect(sources[0].filters.subsets).toEqual(expect.any(Array));
	});

	test('GET /fonts/sources/{source} searches, filters and pages', async () => {
		const all = await call('/fonts/sources/google');

		expect(all.total).toBeGreaterThan(20);
		expect(all.pages).toBe(1);

		const serif = await call('/fonts/sources/google?category=serif');

		expect(serif.entries.every((row) => row.category === 'serif')).toBe(
			true
		);

		const arabic = await call('/fonts/sources/google?subset=arabic');

		expect(arabic.entries.map((row) => row.entry)).toEqual(
			expect.arrayContaining(['amiri', 'cairo'])
		);

		const search = await call('/fonts/sources/google?s=lato');

		expect(search.entries).toHaveLength(1);
	});

	test('GET /fonts/sources/{source} refuses a source nobody registered', async () => {
		await expect(call('/fonts/sources/nope')).rejects.toMatchObject({
			code: 'font_source_unknown',
		});
	});

	test('GET /fonts/sources/{source}/{entry} answers with the catalogue row', async () => {
		const entry = await call('/fonts/sources/packs/emoji');

		expect(entry).toMatchObject({ label: 'Emoji', coverage: 1, always: 1 });

		await expect(call('/fonts/sources/packs/nope')).rejects.toMatchObject({
			code: 'font_entry_unknown',
		});
	});

	test('an install moves through its files and then writes its rows', async () => {
		await call('/fonts/sources/packs/indic', 'POST');

		let status = await call('/fonts/status');

		expect(status['packs/indic'].phase).toBe('queued');
		expect(status['packs/indic'].installed).toBe(false);

		await jest.advanceTimersByTimeAsync(FILE_TICK * 3);

		status = await call('/fonts/status');

		expect(status['packs/indic'].phase).toBe('installing');
		expect(status['packs/indic'].files_done).toBeGreaterThan(0);

		await jest.advanceTimersByTimeAsync(FILE_TICK * 40);

		status = await call('/fonts/status');

		expect(status['packs/indic'].phase).toBeNull();
		expect(status['packs/indic'].installed).toBe(true);

		const fonts = await call('/fonts/');

		expect(fonts.groups.map((group) => group.entry)).toContain('indic');
	});

	test('a display entry installs under the key its label derives to', async () => {
		await call('/fonts/sources/google/montserrat', 'POST', {
			label: 'Montserrat Light',
			variants: { R: '300', B: '700' },
		});

		await jest.advanceTimersByTimeAsync(FILE_TICK * 10);

		const fonts = await call('/fonts/');

		expect(fonts.custom.map((row) => row.id)).toContain('montserratlight');
	});

	test('GET /fonts/status carries an update when a row is behind the catalogue', async () => {
		const status = await call('/fonts/status');

		expect(status['google/lato'].update_available).toBe(true);
		expect(status['google/lato'].update).toMatchObject({
			installed_version: 'v29',
			version: 'v30',
		});
	});

	test('POST /fonts/updates queues every entry that has one', async () => {
		const queued = await call('/fonts/updates', 'POST');

		expect(queued.queued).toEqual(['google/lato']);
	});

	test('removing an always entry records that the admin declined it', async () => {
		await call('/fonts/sources/packs/emoji', 'DELETE');

		const fonts = await call('/fonts/');

		expect(fonts.groups.map((group) => group.entry)).not.toContain('emoji');
		expect(fonts.missing).toEqual([]);
		expect(state.status['packs/emoji'].phase).toBe('removed');
	});

	test('the Bundled panel is told about an always entry that is simply absent', async () => {
		state.rows = state.rows.filter((row) => row.entry !== 'emoji');

		const fonts = await call('/fonts/');

		expect(fonts.missing).toEqual([
			{ source: 'packs', entry: 'emoji', label: 'Emoji', size: 943718 },
		]);
	});

	test('POST /fonts/ uploads a custom row under a unique key', async () => {
		const first = await call('/fonts/', 'POST', {
			label: 'Brand Sans',
			files: { R: 'BrandSans-Regular.ttf' },
		});

		expect(first.id).toBe('brandsans2');
		expect(first.source).toBe('custom');
	});

	test('POST /fonts/{id} renames without moving the key', async () => {
		const row = await call('/fonts/brandsans', 'POST', {
			label: 'House Sans',
		});

		expect(row).toMatchObject({ id: 'brandsans', label: 'House Sans' });
	});

	test('DELETE /fonts/{id} sends a pack font back to the entry route', async () => {
		await expect(call('/fonts/notoemoji', 'DELETE')).rejects.toMatchObject({
			code: 'font_owned_by_entry',
		});

		await expect(call('/fonts/brandsans', 'DELETE')).resolves.toMatchObject(
			{
				deleted: true,
			}
		);
	});

	test('GET and POST /fonts/settings round-trip the four keys', async () => {
		const settings = await call('/fonts/settings');

		expect(settings).toMatchObject({
			default_pdf_language: 'en',
			auto_install_fonts: true,
			auto_install_locked: false,
		});
		expect(settings.language_map.map((group) => group.group)).toContain(
			'bundled'
		);

		const saved = await call('/fonts/settings', 'POST', {
			default_pdf_language: 'ja',
			font_language_overrides: { ru: '*' },
		});

		expect(saved.default_pdf_language).toBe('ja');
		expect(
			saved.language_map
				.flatMap((group) => group.rows)
				.find((row) => row.code === 'ru').font
		).toBe('*');
	});

	test('POST /fonts/sources/sync says so when nothing is due', async () => {
		state.sync.google.synced = new Date().toISOString();

		expect(await call('/fonts/sources/sync', 'POST')).toEqual({
			up_to_date: true,
		});
	});

	test('a scoped mock answers its own routes and passes the rest to the site', async () => {
		const only = mockOnly(['/fonts/settings']);

		/* What the app registers: `/fonts/settings` has no REST route yet, and every other path does */
		const settings = only(
			{ path: '/gravity-pdf/v1/fonts/settings', method: 'GET' },
			next
		);

		await jest.advanceTimersByTimeAsync(500);

		expect((await settings).default_pdf_language).toBe('en');
		expect(next).not.toHaveBeenCalled();

		only({ path: '/gravity-pdf/v1/fonts/', method: 'GET' }, next);

		expect(next).toHaveBeenCalledTimes(1);
	});

	test('an unknown /fonts path is a 404 rather than a fall-through', async () => {
		await expect(
			call('/fonts/sources/packs/emoji/extra')
		).rejects.toMatchObject({ code: 'rest_no_route' });
	});
});
