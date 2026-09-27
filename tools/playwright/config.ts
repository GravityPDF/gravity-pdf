import { defineConfig, devices } from '@playwright/test';
import * as path from 'path';

process.env.WP_ARTIFACTS_PATH = path.join(process.cwd(), 'tmp/artifacts');

import baseConfig = require('@wordpress/scripts/config/playwright.config.js');

const STORAGE_STATE_PATH = path.join(
	process.cwd(),
	'tmp/artifacts/storage-states/e2e.json'
);

const config = defineConfig({
	...baseConfig,

	fullyParallel: true,
	workers: 4,
	quiet: !!process.env.CI,
	maxFailures: process.env.CI ? 5 : 0,

	testDir: undefined,

	reporter: process.env.CI
		? [['github'], ['./flaky-tests-reporter.ts']]
		: 'list',

	// Remove the global setup as we now use setup projects per server
	globalSetup: undefined,

	webServer: [
		{
			...baseConfig.webServer,
			command: 'yarn wp-env:e2e start',
			name: 'Core',
			port: 8702,
		},
	],

	use: {
		...baseConfig.use,
		baseURL: undefined,
		// Recording a trace for every test costs a third of a short test's run time. CI retries a failure, so
		// tracing the retry still leaves one to debug from.
		trace: process.env.CI ? 'on-first-retry' : 'retain-on-failure',
	},

	projects: [
		{
			name: 'setup-core',
			testDir: path.join(process.cwd(), 'tools/playwright'),
			testMatch: /.*global-setup\.ts/,
			use: {
				baseURL: 'http://localhost:8702',
				storageState: { cookies: [], origins: [] },
			},
			metadata: { storageStatePath: STORAGE_STATE_PATH },
		},

		{
			name: 'core',
			dependencies: ['setup-core'],
			testDir: path.join(process.cwd(), 'tests/playwright'),
			testMatch: /(core|permalinks)\/.*(test|spec).(js|ts|mjs)/,
			use: {
				...devices['Desktop Chrome'],
				baseURL: 'http://localhost:8702',
				storageState: STORAGE_STATE_PATH,
				// The site runs on pretty permalinks; this project's requests see plain ones (see tools/mu-plugins)
				extraHTTPHeaders: { 'X-GPDF-E2E-Permalinks': 'plain' },
			},
		},

		{
			name: 'core-with-permalinks',
			dependencies: ['setup-core'],
			testDir: path.join(process.cwd(), 'tests/playwright'),
			testMatch: /permalinks\/.*(test|spec).(js|ts|mjs)/,
			use: {
				...devices['Desktop Chrome'],
				baseURL: 'http://localhost:8702',
				storageState: STORAGE_STATE_PATH,
			},
		},
	],
});

export default config;
