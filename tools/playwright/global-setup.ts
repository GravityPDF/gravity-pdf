import { test as setup } from '@playwright/test';

setup('setup', async ({ request }, testInfo) => {
	const storageStatePath = testInfo.project.metadata
		.storageStatePath as string;

	process.env.WP_BASE_URL = testInfo.project.use.baseURL as string;
	process.env.STORAGE_STATE_PATH = storageStatePath;

	const { RequestUtils } =
		await import('@wordpress/e2e-test-utils-playwright');
	const requestUtils = new RequestUtils(request, {
		storageStatePath,
	});

	// Authenticate and save the storageState to disk.
	await requestUtils.setupRest();

	// Reset the test environment before running the tests.
	await Promise.all([
		requestUtils.activatePlugin('gravity-forms'),
		requestUtils.activatePlugin('gravity-pdf'),
		requestUtils.activateTheme('twentytwentyfive'),
		requestUtils.deleteAllPosts(),
		requestUtils.deleteAllBlocks(),
		requestUtils.resetPreferences(),
	]);
});
