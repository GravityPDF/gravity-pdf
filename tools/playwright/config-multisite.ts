import { defineConfig, devices } from '@playwright/test';
import * as path from 'path';
import config from './config';

import baseConfig = require('@wordpress/scripts/config/playwright.config.js');

// Read by wpCli(), so its commands run in this environment. Set here as workers load this file too
process.env.GPDF_WP_ENV = 'wp-env:e2e-multisite';

const BASE_URL = 'http://localhost:8704';

const STORAGE_STATE_PATH = path.join(
	process.cwd(),
	'tmp/artifacts/storage-states/e2e-multisite.json'
);

export default defineConfig({
	...config,

	webServer: {
		...baseConfig.webServer,
		command: 'yarn wp-env:e2e-multisite start',
		name: 'Multisite',
		port: 8704,
	},

	projects: [
		{
			name: 'setup-multisite',
			testDir: path.join(process.cwd(), 'tools/playwright'),
			testMatch: /.*multisite-setup\.ts/,
			use: {
				baseURL: BASE_URL,
				storageState: { cookies: [], origins: [] },
			},
			metadata: { storageStatePath: STORAGE_STATE_PATH },
		},

		{
			name: 'multisite',
			dependencies: ['setup-multisite'],
			testDir: path.join(process.cwd(), 'tests/playwright'),
			testMatch: /multisite\/.*(test|spec).(js|ts|mjs)/,
			use: {
				...devices['Desktop Chrome'],
				baseURL: BASE_URL,
				storageState: STORAGE_STATE_PATH,
			},
		},
	],
});
