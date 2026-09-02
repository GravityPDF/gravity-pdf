import { defineConfig, devices } from '@playwright/test';
import * as path from 'path';

process.env.WP_ARTIFACTS_PATH = path.join(process.cwd(), 'tmp/artifacts');

import baseConfig = require('@wordpress/scripts/config/playwright.config.js');

// Specs whose fixtures change state the whole site can see. The deprecation notice renders on every admin page
// the other snapshots are taken on, so `fullyParallel` running these beside them puts a notice in an unrelated
// baseline. They get a project to themselves, ordered into the chain below so nothing else is in flight.
const ISOLATED = /core\/system-status\/.*(test|spec)\.(js|ts|mjs)/;

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

	expect: {
		toHaveScreenshot: { maxDiffPixelRatio: 0.1 },
	},

	use: {
		...baseConfig.use,
		baseURL: undefined,
		disableAutoSnapshot: true,
		ignoreSelectors: [
			'#wpadminbar',
			'#adminmenumain',
			'#gform-form-toolbar',
		],
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
			metadata: {
				storageStatePath: path.join(
					process.cwd(),
					'tmp/artifacts/storage-states/e2e.json'
				),
				permalinkStructure: '',
			},
		},

		{
			name: 'core',
			dependencies: ['setup-core'],
			testDir: path.join(process.cwd(), 'tests/playwright'),
			testMatch: /(core|permalinks)\/.*(test|spec).(js|ts|mjs)/,
			testIgnore: ISOLATED,
			use: {
				...devices['Desktop Chrome'],
				baseURL: 'http://localhost:8702',
				storageState: path.join(
					process.cwd(),
					'tmp/artifacts/storage-states/e2e.json'
				),
			},
		},

		{
			name: 'core-isolated',
			dependencies: ['core'],
			testDir: path.join(process.cwd(), 'tests/playwright'),
			testMatch: ISOLATED,
			use: {
				...devices['Desktop Chrome'],
				baseURL: 'http://localhost:8702',
				storageState: path.join(
					process.cwd(),
					'tmp/artifacts/storage-states/e2e.json'
				),
			},
		},

		{
			name: 'setup-core-with-permalinks',
			dependencies: ['core-isolated'],
			testDir: path.join(process.cwd(), 'tools/playwright'),
			testMatch: /.*global-setup\.ts/,
			use: {
				baseURL: 'http://localhost:8702',
				storageState: { cookies: [], origins: [] },
			},
			metadata: {
				storageStatePath: path.join(
					process.cwd(),
					'tmp/artifacts/storage-states/e2e-permalinks.json'
				),
				permalinkStructure: '/%postname%/',
			},
		},

		{
			name: 'core-with-permalinks',
			dependencies: ['setup-core-with-permalinks'],
			testDir: path.join(process.cwd(), 'tests/playwright'),
			testMatch: /permalinks\/.*(test|spec).(js|ts|mjs)/,
			use: {
				...devices['Desktop Chrome'],
				baseURL: 'http://localhost:8702',
				storageState: path.join(
					process.cwd(),
					'tmp/artifacts/storage-states/e2e-permalinks.json'
				),
			},
		},
	],
});

export default config;
