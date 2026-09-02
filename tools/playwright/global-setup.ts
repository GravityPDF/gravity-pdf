import { test as setup } from '@playwright/test';
import { wpCli } from '@self:playwright/utils/wp-cli';

setup('setup', async ({ request }, testInfo) => {
	const storageStatePath = testInfo.project.metadata
		.storageStatePath as string;
	const permalinkStructure = testInfo.project.metadata
		.permalinkStructure as string;

	process.env.WP_BASE_URL = testInfo.project.use.baseURL as string;
	process.env.STORAGE_STATE_PATH = storageStatePath;

	// Both setup projects share one wp-env instance on port 8702;
	// flip the permalink structure here so each project group runs against
	// its required URL scheme. Batched into one `bash -c` to keep the
	// docker-exec round-trip count to one. `|| true` on the .htaccess
	// removal makes it best-effort: a cached wp-env work-dir can restore
	// the file with a UID the cli container can't write over, and a stale
	// .htaccess doesn't affect plain-permalink tests anyway (URLs hit
	// `?p=N` and skip rewriting).
	const flush =
		permalinkStructure === ''
			? '(rm -f /var/www/html/.htaccess || true)'
			: 'wp rewrite flush --hard';
	wpCli(
		`wp option update permalink_structure '${permalinkStructure}' && ${flush}`
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
