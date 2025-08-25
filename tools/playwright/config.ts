import { fileURLToPath } from 'url';
import { defineConfig } from '@playwright/test';
import * as path from 'path';

process.env.WP_ARTIFACTS_PATH = path.join(process.cwd(), 'tmp/artifacts');
const baseConfig = require('@wordpress/scripts/config/playwright.config.js');

const config = defineConfig({
	...baseConfig,
	testDir: path.join(process.cwd(), 'tests/playwright'),
	reporter: process.env.CI
		? [['github'], ['./flaky-tests-reporter.ts']]
		: 'list',

	globalSetup: fileURLToPath(
		new URL('global-setup.ts', 'file:' + __filename).href
	),

	webServer: {
		...baseConfig.webServer,
		port: process.env.WP_BASE_URL ? process.env.WP_BASE_URL.split(':').at(-1) : 8889,
	},
});

export default config;
