import { mergeTests } from '@playwright/test';
import { test as wpTests } from '@wordpress/e2e-test-utils-playwright';

export const test = mergeTests(wpTests);