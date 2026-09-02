import { mergeTests } from '@playwright/test';
import { test as wpTests } from '@wordpress/e2e-test-utils-playwright';
import { test as chromeTests } from '@chromatic-com/playwright';
import * as path from 'node:path';

export const test = mergeTests(wpTests, chromeTests).extend({
	page: async ({ page }, use) => {
		// Gravatar is unreachable from wp-env, so the admin bar's avatar hangs for the life of the test rather
		// than resolving either way. That leaves a snapshot's layout to depend on whether the request happened to
		// come back, which differs between a laptop and CI. Failing them at once is what the suite renders in
		// practice anyway, only deterministically.
		await page.route('**://*.gravatar.com/**', (route) => route.abort());

		await use(page);
	},
});

export const resourcesPath = path.join(__dirname, '..', 'data');
