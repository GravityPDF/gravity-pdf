import { mergeTests } from '@playwright/test';
import { test as wpTests } from '@wordpress/e2e-test-utils-playwright';
import { test as chromeTests } from '@chromatic-com/playwright';
import * as path from 'node:path';

export const test = mergeTests(wpTests, chromeTests);

export const resourcesPath = path.join(__dirname, '..', 'data');
