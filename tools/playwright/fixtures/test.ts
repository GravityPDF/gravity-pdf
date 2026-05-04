import { mergeTests } from '@playwright/test';
import { test as wpTests, expect } from '@wordpress/e2e-test-utils-playwright';
import { test as chromeTests } from '@chromatic-com/playwright';
import * as path from 'node:path';

const test = mergeTests(wpTests, chromeTests);

const resourcesPath = path.join(__dirname, '..', 'data');

export { test, expect, resourcesPath };
