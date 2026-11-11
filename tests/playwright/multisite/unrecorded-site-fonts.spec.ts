import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Page } from '@playwright/test';
import { test, resourcesPath } from '@self:playwright/fixtures/test';
import { wpCli } from '@self:playwright/utils/wp-cli';
import * as fs from 'fs';
import * as path from 'path';

const FONTS = 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts';
const SHARED = 'UnrecordedShared.ttf';
const OWN = 'UnrecordedOwn.ttf';

// Each site gets one custom font
const SITES = {
	'gpdf-unrecorded-a': { id: 'unrecordeda', file: SHARED },
	'gpdf-unrecorded-b': { id: 'unrecordedb', file: 'UnrecordedB.ttf' },
	'gpdf-unrecorded-c': { id: 'unrecordedc', file: 'UnrecordedC.ttf' },
};
const BROKEN = 'gpdf-unrecorded-c';

const roboto = fs.readFileSync(
	path.join(resourcesPath, 'fonts', 'Roboto-Regular.ttf')
);

const restRoute = (route: string) => `/?rest_route=/gravity-pdf/v1/${route}`;

const headers = async (page: Page) => ({
	'X-WP-Nonce': await (
		await page.request.get('/wp-admin/admin-ajax.php?action=rest-nonce')
	).text(),
});

const upload = async (page: Page, label: string, name: string) =>
	page.request.post(restRoute('fonts/'), {
		headers: await headers(page),
		multipart: {
			label,
			regular: { name, mimeType: 'font/ttf', buffer: roboto },
		},
	});

const remove = async (page: Page, id: string) =>
	page.request.delete(restRoute(`fonts/${id}`), {
		headers: await headers(page),
	});

// The last output line matching, as wpCli() output includes wp-env's own lines
const lastLine = (output: string, pattern: RegExp) =>
	output
		.split('\n')
		.map((line) => line.trim())
		.filter((line) => pattern.test(line))
		.pop() ?? '';

const fontFiles = () =>
	wpCli(`ls ${FONTS}`)
		.split('\n')
		.map((line) => line.trim());

const dbPrefix = () => lastLine(wpCli('wp db prefix'), /^\w+_$/);

const optionsTable = (siteId: string) => `${dbPrefix()}${siteId}_options`;

// Base64, as wpCli() runs its commands inside double quotes
const settings = ({ id, file }: { id: string; file: string }) =>
	Buffer.from(
		JSON.stringify({
			custom_fonts: {
				[id]: { id, font_name: id, regular: `/fonts/${file}` },
			},
		})
	).toString('base64');

// Sites that saved fonts before their files were recorded, as after the 6.19 upgrade until the font sync runs
test.describe('Custom fonts of sites with no record', () => {
	test.describe.configure({ mode: 'serial' });

	const siteIds: Record<string, string> = {};

	// By slug, which still finds a site whose options table is gone
	const deleteSites = () =>
		wpCli(
			...Object.keys(SITES).map(
				(slug) =>
					`(wp site delete --slug=${slug} --yes --quiet 2>/dev/null || true)`
			)
		);

	const deleteFonts = async (page: Page) => {
		const fonts = await (
			await page.request.get(restRoute('fonts/'), {
				headers: await headers(page),
			})
		).json();
		for (const font of fonts.filter((row: { font_name?: string }) =>
			row.font_name?.startsWith('Unrecorded')
		)) {
			await remove(page, font.id);
		}

		// A file left behind would make the next upload save under another name
		wpCli(`rm -f ${FONTS}/${SHARED} ${FONTS}/${OWN}`);
	};

	test.beforeAll(() => {
		deleteSites();

		for (const [slug, font] of Object.entries(SITES)) {
			siteIds[slug] = lastLine(
				wpCli(
					`wp site create --slug=${slug} --title=${slug} --porcelain`
				),
				/^\d+$/
			);
			const url = lastLine(
				wpCli('wp site list --field=url'),
				new RegExp(`/${slug}/$`)
			);

			// Saving the setting records the site's files, so the record is deleted after
			wpCli(
				`echo ${settings(font)} | base64 -d | wp option update gfpdf_settings --format=json --url=${url} --quiet`,
				`wp site option delete gfpdf_site_custom_fonts_${siteIds[slug]} --quiet`
			);
		}
	});

	test.beforeEach(async ({ page }) => {
		await page.goto('/wp-admin/index.php');
		await deleteFonts(page);
	});

	test.afterAll(async ({ browser }) => {
		const page = await browser.newPage();
		await page.goto('/wp-admin/index.php');
		await deleteFonts(page);
		await page.close();

		// Put back the table the last test hides, so the site deletes cleanly
		if (siteIds[BROKEN]) {
			const table = optionsTable(siteIds[BROKEN]);
			wpCli(
				`(wp db query 'RENAME TABLE ${table}_hidden TO ${table}' || true)`
			);
		}
		deleteSites();
	});

	test('should not reuse the font ID of a site with no record', async ({
		page,
	}) => {
		const response = await upload(page, 'Unrecorded B', OWN);

		expect(response.status()).toBe(200);
		expect((await response.json()).id).toMatch(/^unrecordedb\d+$/);
	});

	test('should keep a file a site with no record uses when a font is deleted', async ({
		page,
	}) => {
		const shared = await (
			await upload(page, 'Unrecorded Shared', SHARED)
		).json();
		const own = await (await upload(page, 'Unrecorded Own', OWN)).json();
		expect(fontFiles()).toEqual(expect.arrayContaining([SHARED, OWN]));

		expect((await remove(page, shared.id)).status()).toBe(200);
		expect((await remove(page, own.id)).status()).toBe(200);

		const files = fontFiles();
		expect(files).toContain(SHARED);
		expect(files).not.toContain(OWN);
	});

	// A site with no options table has no fonts
	test("should still read the other sites when one site's options table is missing", async ({
		page,
	}) => {
		const table = optionsTable(siteIds[BROKEN]);
		wpCli(`wp db query 'RENAME TABLE ${table} TO ${table}_hidden'`);

		const response = await upload(page, 'Unrecorded Own', OWN);
		expect(response.status()).toBe(200);
		const own = await response.json();
		expect(own.id).toBe('unrecordedown');
		const shared = await (
			await upload(page, 'Unrecorded Shared', SHARED)
		).json();

		expect((await remove(page, own.id)).status()).toBe(200);
		expect((await remove(page, shared.id)).status()).toBe(200);

		const files = fontFiles();
		expect(files).not.toContain(OWN);
		expect(files).toContain(SHARED);
	});
});
