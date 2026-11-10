import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { APIRequestContext, Browser, Page } from '@playwright/test';
import { test, resourcesPath } from '@self:playwright/fixtures/test';
import {
	SUBSITE,
	logInToSubsite as logIn,
} from '@self:playwright/utils/multisite';
import { wpCli } from '@self:playwright/utils/wp-cli';
import * as fs from 'fs';
import * as path from 'path';

const FONTS = 'wp-content/uploads/PDF_EXTENDED_TEMPLATES/fonts';
const FONT_NAME = 'Network Roboto';
const FONT_ID = 'networkroboto';
const FILE = 'NetworkRoboto.ttf';

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
			for (const font of fonts.filter(
				(row: { font_name?: string }) => row.font_name === FONT_NAME
			)) {
				await send(admin, base, 'delete', `fonts/${font.id}`);
			}
		}

		await admin.context().close();
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
});
