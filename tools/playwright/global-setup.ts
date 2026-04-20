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
		/* Truncate Gravity Forms tables. Each test run creates new forms via
		   pdf.createForm; without this cleanup they accumulate (1000+ rows
		   over time) and post-new.php blows the 256MB PHP memory limit when
		   GF_Blocks_Config eagerly loads every form for the editor's block
		   sidebar — manifesting as a "critical error" page and a wp.data
		   timeout in @wordpress/e2e-test-utils-playwright's setPreferences.
		   Endpoint is registered by tools/mu-plugins/gravitypdf.php only
		   when E2E_TEST_SUITE is set. */
		requestUtils
			.rest({ method: 'POST', path: '/gravitypdf-test/v1/reset-gf' })
			.catch(() => {
				/* Non-fatal: don't block tests if cleanup endpoint hiccups. */
			}),
	]);
});
