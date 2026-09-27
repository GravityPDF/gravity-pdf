import { test as setup } from '@playwright/test';
import { wpCli } from '@self:playwright/utils/wp-cli';

setup('setup', async ({ request }, testInfo) => {
	const storageStatePath = testInfo.project.metadata
		.storageStatePath as string;

	process.env.WP_BASE_URL = testInfo.project.use.baseURL as string;
	process.env.STORAGE_STATE_PATH = storageStatePath;

	// Pretty permalinks keep their rewrite rules and .htaccess in place for the project that uses them. The other
	// asks for plain ones per request (see tools/mu-plugins), so both run against the site at once
	wpCli(
		"wp option update permalink_structure '/%postname%/'",
		'wp rewrite flush --hard'
	);

	const { RequestUtils } =
		await import('@wordpress/e2e-test-utils-playwright');
	const requestUtils = new RequestUtils(request, {
		storageStatePath,
	});

	// Authenticate and save the storageState to disk.
	await requestUtils.setupRest();

	// Activate plugins sequentially: WP's REST plugin controller updates the
	// `active_plugins` option non-atomically (read-modify-write), so concurrent
	// activations race and the last write wins, leaving one plugin inactive.
	await requestUtils.activatePlugin('gravity-forms');
	await requestUtils.activatePlugin('gravity-pdf');

	// The remaining resets are independent and safe to parallelise.
	await Promise.all([
		requestUtils.activateTheme('twentytwentyfive'),
		requestUtils.deleteAllPosts(),
		requestUtils.deleteAllBlocks(),
		requestUtils.resetPreferences(),
	]);
});
