import { test as wpTests } from '@wordpress/e2e-test-utils-playwright';
import * as path from 'node:path';

export const test = wpTests.extend({
	page: async ({ page }, use) => {
		// Gravatar is unreachable from wp-env, so the admin bar's avatar hangs for the life of the test rather
		// than resolving either way. That leaves a snapshot's layout to depend on whether the request happened to
		// come back, which differs between a laptop and CI. Failing them at once is what the suite renders in
		// practice anyway, only deterministically.
		await page.route('**://*.gravatar.com/**', (route) => route.abort());

		await use(page);

		// A `target="_blank"` link to a PDF leaves a viewer tab open, which the failure screenshot taken at teardown
		// can't capture: it stalls 5 s on every test that opens one, pass or fail
		await Promise.all(
			page
				.context()
				.pages()
				.filter((other) => other !== page)
				.map((other) => other.close())
		);
	},
});

export const resourcesPath = path.join(__dirname, '..', 'data');
