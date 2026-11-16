import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { APIRequestContext, Browser, Page } from '@playwright/test';
import { test, resourcesPath } from '@self:playwright/fixtures/test';
import {
	SUBSITE,
	logInToSubsite as logIn,
} from '@self:playwright/utils/multisite';
import { wpCli } from '@self:playwright/utils/wp-cli';
import { coreFontStandIn, hashedName } from '@self:playwright/utils/core-fonts';
import * as fs from 'fs';
import * as path from 'path';

const FONTS = 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts';
const FONT_NAME = 'Network Roboto';
const FONT_ID = 'networkroboto';
const FILE = 'NetworkRoboto.ttf';
const CORE_NAMED_FONT = 'Core Named Roboto';
const CORE_FILE = 'DejaVuSerifCondensed.ttf';
const CORE_BOLD_FILE = 'DejaVuSerifCondensed-Bold.ttf';
const CORE_BOLD_COPY = hashedName(CORE_BOLD_FILE);
// The manifest's own build, which the plugin hashes before deleting it from the core font's name
const CORE_FILE_IN_CONTAINER =
	'wp-content/plugins/gravity-pdf/tools/phpunit/data/fonts/DejaVuSerifCondensed.ttf';
const ROBOTO_IN_CONTAINER =
	'wp-content/plugins/gravity-pdf/tools/playwright/data/fonts/Roboto-Regular.ttf';

const roboto = fs.readFileSync(
	path.join(resourcesPath, 'fonts', 'Roboto-Regular.ttf')
);

const nonceFor = async (request: APIRequestContext, base: string) =>
	(
		await request.get(`${base}/wp-admin/admin-ajax.php?action=rest-nonce`)
	).text();

const restRoute = (base: string, route: string) =>
	`${base}/?rest_route=/gravity-pdf/v1/${route}`;

const upload = async (
	page: Page,
	base: string,
	label: string,
	name: string,
	buffer: Buffer
) =>
	page.request.post(restRoute(base, 'fonts/'), {
		headers: { 'X-WP-Nonce': await nonceFor(page.request, base) },
		multipart: {
			label,
			regular: { name, mimeType: 'font/ttf', buffer },
		},
	});

const send = async (
	page: Page,
	base: string,
	method: 'get' | 'delete',
	route: string
) =>
	page.request[method](restRoute(base, route), {
		headers: { 'X-WP-Nonce': await nonceFor(page.request, base) },
	});

const fontFiles = () => wpCli(`ls ${FONTS}`);

const fontFileList = () => fontFiles().split('\n');

test.describe('Fonts of a Multisite subsite', () => {
	test.describe.configure({ mode: 'serial' });

	const SUBSITE_URL = `/${SUBSITE}`;
	let subsiteFontId = '';

	// By name, as a failed run never learns the subsite font's ID
	const deleteFonts = async ({ browser }: { browser: Browser }) => {
		const admin = await logIn(browser, 'admin');

		for (const base of ['', SUBSITE_URL]) {
			const fonts = await (
				await send(admin, base, 'get', 'fonts/')
			).json();
			for (const font of fonts.filter((row: { font_name?: string }) =>
				[FONT_NAME, CORE_NAMED_FONT].includes(row.font_name ?? '')
			)) {
				await send(admin, base, 'delete', `fonts/${font.id}`);
			}
		}

		await admin.context().close();
		wpCli(`rm -f ${FONTS}/${CORE_FILE} ${FONTS}/${CORE_BOLD_COPY}`);
	};

	test.beforeAll(deleteFonts);
	test.afterAll(deleteFonts);

	test('should give a font an ID no other site has', async ({
		browser,
		page,
	}) => {
		await page.goto('/wp-admin/index.php');
		const main = await upload(page, '', FONT_NAME, FILE, roboto);
		expect(main.status()).toBe(200);
		expect((await main.json()).id).toBe(FONT_ID);

		const subsite = await logIn(browser, 'siteadmin');
		const response = await upload(
			subsite,
			SUBSITE_URL,
			FONT_NAME,
			FILE,
			roboto
		);
		expect(response.status()).toBe(200);
		subsiteFontId = (await response.json()).id;
		await subsite.context().close();

		expect(subsiteFontId).toMatch(new RegExp(`^${FONT_ID}\\d+$`));
	});

	test("should not list the subsite's font on the main site", async ({
		page,
	}) => {
		await page.goto('/wp-admin/index.php');
		const fonts = await send(page, '', 'get', 'fonts/');

		expect(fonts.ok()).toBe(true);
		const ids = (await fonts.json()).map((font: { id: string }) => font.id);
		expect(ids).toContain(FONT_ID);
		expect(ids).not.toContain(subsiteFontId);
	});

	test('should refuse an upload that is not a font and keep nothing', async ({
		browser,
	}) => {
		const page = await logIn(browser, 'siteadmin');
		const before = fontFiles();
		const response = await upload(
			page,
			SUBSITE_URL,
			'Not A Font',
			'NotAFont.ttf',
			Buffer.from('not a font')
		);

		expect(response.status()).toBe(400);
		expect(fontFiles()).toBe(before);

		await page.context().close();
	});

	test('should refuse a font upload from a user who cannot edit forms', async ({
		browser,
	}) => {
		const page = await logIn(browser, 'siteeditor');
		const response = await upload(
			page,
			SUBSITE_URL,
			'Editor Font',
			'Editor.ttf',
			roboto
		);

		expect(response.status()).toBe(403);
		expect(fontFiles()).not.toContain('Editor.ttf');

		await page.context().close();
	});

	test("should keep core fonts out of a site administrator's reach", async ({
		browser,
	}) => {
		const page = await logIn(browser, 'siteadmin');
		const response = await send(
			page,
			SUBSITE_URL,
			'delete',
			'fonts/dejavuserif&scope=core'
		);

		expect(response.status()).toBe(403);

		await page.context().close();
	});

	test("should keep the core font's file another site's custom font uses when the core font is deleted", async ({
		browser,
		page,
	}) => {
		const subsite = await logIn(browser, 'siteadmin');
		const uploaded = await upload(
			subsite,
			SUBSITE_URL,
			CORE_NAMED_FONT,
			CORE_FILE,
			roboto
		);
		expect(uploaded.status()).toBe(200);

		// A file of the family that no site's custom font uses
		wpCli(
			coreFontStandIn(ROBOTO_IN_CONTAINER, `${FONTS}/${CORE_BOLD_FILE}`)
		);

		await page.goto('/wp-admin/index.php');

		// The subsite's file isn't the main site's core font, so only the bold style is downloaded
		const installed = await (
			await send(page, '', 'get', 'fonts/&scope=all')
		).json();
		const core = installed.find(
			(row: { id: string }) => row.id === 'dejavuserifcondensed'
		);
		expect(core.regular).toBe('');
		expect(core.bold).toBe(CORE_BOLD_COPY);

		const deleted = await send(
			page,
			'',
			'delete',
			'fonts/dejavuserifcondensed&scope=core'
		);
		expect(deleted.status()).toBe(200);
		const body = await deleted.json();
		expect(body.removed).toEqual([CORE_BOLD_FILE]);
		expect(body.font).toBeNull();

		const files = fontFileList();
		expect(files).toContain(CORE_FILE);
		expect(files).not.toContain(CORE_BOLD_COPY);

		// Throws if the subsite's font no longer has its own bytes
		wpCli(`cmp ${ROBOTO_IN_CONTAINER} ${FONTS}/${CORE_FILE}`);

		await subsite.context().close();
	});

	test("should delete the core font's file once no site's custom font uses it", async ({
		browser,
		page,
	}) => {
		const subsite = await logIn(browser, 'siteadmin');
		const fonts = await (
			await send(subsite, SUBSITE_URL, 'get', 'fonts/')
		).json();
		const font = fonts.find(
			(row: { font_name?: string }) => row.font_name === CORE_NAMED_FONT
		);
		expect(
			(
				await send(subsite, SUBSITE_URL, 'delete', `fonts/${font.id}`)
			).ok()
		).toBe(true);
		await subsite.context().close();

		// The custom font's delete removed its file, so put one back at the core font's name
		wpCli(`cp ${CORE_FILE_IN_CONTAINER} ${FONTS}/${CORE_FILE}`);

		await page.goto('/wp-admin/index.php');
		const deleted = await send(
			page,
			'',
			'delete',
			'fonts/dejavuserifcondensed&scope=core'
		);
		expect(deleted.status()).toBe(200);
		expect((await deleted.json()).removed).toEqual([CORE_FILE]);
		expect(fontFileList()).not.toContain(CORE_FILE);
	});
});
