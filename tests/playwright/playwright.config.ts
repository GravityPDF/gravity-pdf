/**
 * External dependencies
 */

import { fileURLToPath } from 'url';
import { defineConfig } from '@playwright/test';

/**
 * WordPress dependencies
 */
import baseConfig from '@wordpress/scripts/config/playwright.config.js';

const config = defineConfig({
	...baseConfig,
	reporter: process.env.CI
		? [['github'], ['./config/flaky-tests-reporter.ts']]
		: 'list',
	workers: 1,
	globalSetup: fileURLToPath(
		new URL('./config/global-setup.ts', 'file:' + __filename).href
	),
});

export default config;
