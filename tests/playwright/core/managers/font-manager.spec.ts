import type { Admin, RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import { expect } from '@wordpress/e2e-test-utils-playwright';
import type { Locator, Page } from '@playwright/test';
import { test, resourcesPath } from '@self:playwright/fixtures/test';
import Pdf from '@self:playwright/utils/gravitypdf';
import { isolateForSnapshot, snapshot } from '@self:playwright/utils/snapshot';
import * as path from 'node:path';

/**
 * The 7.0 Font Manager.
 *
 * Every `/fonts` route is served by the plugin now, so what the manager shows here is what this site actually
 * holds: one bundled family, no packs, no custom fonts and neither catalogue downloaded. That is what the
 * assertions below are written against — an empty group draws no heading, and a source that has never synced
 * offers its Refresh button instead of a grid.
 *
 * That premise is not an accident of timing: `WP_HTTP_BLOCK_EXTERNAL` in `tools/wp-env/e2e.json` is what keeps
 * the catalogues empty, since a blocked sync records `last_attempt` and never `synced`. Lift that flag and these
 * assertions go red for a reason nothing here will point at.
 *
 * Still uncovered *in the browser*, because both need fixtures this project cannot leave behind for the specs it
 * runs beside: the add → edit → delete lifecycle of a custom font, and the install run of a catalogue entry.
 * Neither is untested — `Test_Rest_Custom_Fonts` and `Test_Rest_Font_Installs` drive both routes for real,
 * uploads included. What is missing is only the browser half, and the offline import's refusal path (below)
 * needs no fixture to cover its.
 */
test.describe('Font Manager', () => {
	let pdf: Pdf;
	let form: any;

	const openManager = async (page: Page) => {
		await page.locator('.gfpdf-manage-fonts').getByRole('button').click();

		return page.getByRole('dialog', { name: 'Font manager' });
	};

	// The one font every site has. `.font-row` rather than the name, which the detail pane repeats once it opens
	const bundledRow = (manager: Locator) =>
		manager.locator('.font-row').filter({ hasText: 'Arimo' });

	// Scoped to the sidebar: the empty detail pane offers a button of the same name
	const addMenu = async (manager: Locator, item: string) => {
		await manager
			.locator('.fm-sidebar')
			.getByRole('button', { name: 'Add new font' })
			.click();

		await manager.page().getByRole('menuitem', { name: item }).click();
	};

	test.beforeEach(
		async ({
			requestUtils,
			page,
			admin,
		}: {
			requestUtils: RequestUtils;
			page: Page;
			admin: Admin;
		}) => {
			pdf = new Pdf(requestUtils, admin, page);
			form = await pdf.createForm('Font Manager');
		}
	);

	test('should display "Font" field and open Font Manager', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		await expect(page.getByLabel('Font', { exact: true })).toBeVisible();

		await expect(await openManager(page)).toBeVisible();
	});

	test('should display a dropdown of default fonts option', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const select = await page.getByLabel('Font', { exact: true });

		// 7.0 ships none of the 6.x core fonts, so the dropdown lists what the font registry actually has
		await expect(
			select.locator('optgroup[label="Bundled Fonts"]')
		).toHaveCount(1);

		await expect(select.locator('optgroup[label="Unicode"]')).toHaveCount(
			0
		);

		await select.selectOption('Arimo');
	});

	test('should save selected font', async ({ page }) => {
		await pdf.navigateToNewFormPdf(form.id);
		await page
			.getByLabel('Font', { exact: true })
			.selectOption('gfpdf-arimo');

		await pdf.addOrUpdatePdf();

		await expect(page.getByLabel('Font', { exact: true })).toHaveValue(
			'gfpdf-arimo'
		);
	});

	test('should list the installed fonts by group, and narrow them by search', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await expect(
			manager.getByText('Bundled', { exact: true })
		).toBeVisible();
		await expect(bundledRow(manager)).toBeVisible();

		// A group with no rows draws no heading, and this site has neither a custom font nor a pack
		await expect(manager.getByText('Custom', { exact: true })).toHaveCount(
			0
		);
		await expect(manager.getByText('Language packs')).toHaveCount(0);

		await manager.getByLabel('Search fonts').fill('ari');

		await expect(bundledRow(manager)).toBeVisible();

		await manager.getByLabel('Search fonts').fill('nothing by this name');

		await expect(bundledRow(manager)).toBeHidden();
		await expect(manager.getByText('No results.')).toBeVisible();
	});

	test('should open a bundled font, and show the key a template writes', async ({
		page,
	}, testinfo) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await bundledRow(manager).click();

		await expect(
			manager.getByText('Bundled with Gravity PDF · gfpdf-arimo')
		).toBeVisible();

		await isolateForSnapshot(page, [manager]);
		await snapshot(page, testinfo);
	});

	test('should offer to download a catalogue that has never synced', async ({
		page,
	}, testinfo) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await addMenu(manager, 'Browse Google Fonts');

		await expect(
			manager.getByText(/The Google Fonts catalogue hasn.t been/)
		).toBeVisible();

		// The header carries a Refresh of its own, so this is the one the empty state offers
		await expect(
			manager
				.locator('.fm-empty-detail')
				.getByRole('button', { name: 'Refresh' })
		).toBeVisible();

		await isolateForSnapshot(page, [manager]);
		await snapshot(page, testinfo);
	});

	/**
	 * The browser half of the offline path: the menu item, the picker, a real multipart upload to
	 * `POST /fonts/import`, and the WP_Error the route answers with rendered back into the dialog. What the
	 * route itself does is `Test_Rest_Font_Installs`' subject, and the dialog's own branches are the Jest
	 * suite's — so this asserts only what needs a browser, a built bundle and a real request to fail.
	 *
	 * The refusal is the 404, because this site has never synced, which is the state §10 describes: no
	 * catalogue, so nothing to verify an archive against. The fixture declares a package name no published
	 * catalogue can carry, so that stays true even on a site that has one, and it carries the manifest and
	 * nothing else because the lookup that refuses it happens before a font file is read.
	 */
	test('should refuse an offline package the catalogue does not list', async ({
		page,
	}, testinfo) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await addMenu(manager, 'Install from file');

		const dialog = page.getByRole('dialog', { name: 'Install from file' });

		await expect(dialog).toBeVisible();

		await dialog
			.locator('input[type="file"]')
			.setInputFiles(
				path.join(
					resourcesPath,
					'fonts',
					'e2e-unlisted-pack-v0.0.0.zip'
				)
			);

		await expect(
			dialog.getByText(/e2e-unlisted-pack-v0\.0\.0\.zip/)
		).toBeVisible();

		await dialog.getByRole('button', { name: 'Install' }).click();

		await expect(
			dialog.getByText(/The font catalogue does not list this package/)
		).toBeVisible();

		// The manager too, so the dialog is not on a blank page — by class, because it leaves the a11y tree
		await isolateForSnapshot(page, [page.locator('.gfpdf-fm'), dialog]);
		await snapshot(page, testinfo);
	});

	test('should open the language settings from the header', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await manager
			.getByRole('button', { name: 'Language settings' })
			.click();

		await expect(
			manager.getByLabel('Default document language')
		).toBeVisible();
		await expect(manager.getByText('Bundled · Arimo')).toBeVisible();
	});

	test('should save a language override and read it back on the next visit', async ({
		page,
		requestUtils,
	}) => {
		/*
		 * The four language keys are site-wide, so this is the one spec here that writes state the others can
		 * see — and that a re-run would otherwise inherit from the last one. Cleared at both ends rather than
		 * only at the end, since a run killed part-way through leaves the site dirty for the next one.
		 */
		const reset = () =>
			requestUtils.rest({
				path: '/gravity-pdf/v1/fonts/settings',
				method: 'POST',
				data: {
					default_pdf_language: '',
					document_script: '',
					auto_install_fonts: true,
					font_language_overrides: {},
				},
			});

		await reset();
		await pdf.navigateToNewFormPdf(form.id);

		const open = async () => {
			const manager = await openManager(page);

			await manager
				.getByRole('button', { name: 'Language settings' })
				.click();

			await expect(
				manager.getByLabel('Default document language')
			).toBeVisible();

			return manager;
		};

		let manager = await open();

		// `manager` is rebound after the re-open, so this has to read the current one rather than close over it
		const row = (name: string) =>
			manager.locator('.gfpdf-fm-language-row', { hasText: name });

		// This site has no packs, so the bundled Latin row is the only one the map carries
		await expect(row('Latin script')).toHaveCount(1);
		await row('Latin script').getByRole('combobox').selectOption('*');

		// By value: the same name is carried by three codes, and only one of them is the row this adds
		await manager.getByLabel('Add a language').selectOption('ja');
		await manager.getByRole('button', { name: 'Add', exact: true }).click();

		await row('Japanese').getByRole('combobox').selectOption('gfpdf-arimo');

		await manager
			.getByLabel('Default document language')
			.selectOption('ja');

		await manager.getByRole('button', { name: 'Save settings' }).click();
		// The snackbar, not the a11y live region, which carries the same words
		await expect(
			page.getByTestId('snackbar').getByText('Language settings saved')
		).toBeVisible();

		// A fresh page rather than a reload: the manager's store is built at mount, so only a new one re-fetches
		await pdf.navigateToNewFormPdf(form.id);

		manager = await open();

		await expect(
			manager.getByLabel('Default document language')
		).toHaveValue('ja');

		// The added row came back, which means the override was stored rather than only drawn
		await expect(row('Japanese').getByRole('combobox')).toHaveValue(
			'gfpdf-arimo'
		);

		// And the bundled row is still where it was, holding the sentinel rather than a font
		await expect(row('Latin script').getByRole('combobox')).toHaveValue(
			'*'
		);

		// Reset puts a changed row back on its default, and the button goes with the change it undid
		const undo = row('Latin script').getByRole('button', {
			name: 'Reset to the default',
		});

		await undo.click();
		await expect(undo).toBeHidden();

		await reset();
	});

	test('should be able to close font manager popup with button', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await expect(manager).toBeVisible();
		await manager.getByRole('button', { name: 'Close dialog' }).click();
		await expect(manager).toBeHidden();
	});

	test('should be able to close font manager popup with esc key', async ({
		page,
	}) => {
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await expect(manager).toBeVisible();
		await page.keyboard.press('Escape');
		await expect(manager).toBeHidden();
	});

	test('should show one pane at a time on a phone', async ({ page }) => {
		await page.setViewportSize({ width: 390, height: 780 });
		await pdf.navigateToNewFormPdf(form.id);

		const manager = await openManager(page);

		await expect(manager.locator('.fm-sidebar')).toBeVisible();
		await expect(manager.locator('.fm-detail')).toBeHidden();

		await bundledRow(manager).click();

		await expect(manager.locator('.fm-sidebar')).toBeHidden();
		await expect(manager.locator('.fm-detail')).toBeVisible();

		await manager.getByRole('button', { name: 'Fonts' }).click();

		await expect(manager.locator('.fm-sidebar')).toBeVisible();
	});
});
