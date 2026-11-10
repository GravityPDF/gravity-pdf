import type { Browser } from '@playwright/test';

// The subsite tools/playwright/multisite-setup.ts creates
export const SUBSITE = 'site2';

// Logs in on the subsite and lands on its dashboard
export const logInToSubsite = async (browser: Browser, username: string) => {
	const context = await browser.newContext({
		storageState: { cookies: [], origins: [] },
	});
	const page = await context.newPage();

	// As the page fixture does: wp-env can't reach Gravatar
	await page.route('**://*.gravatar.com/**', (route) => route.abort());
	await page.goto(`/${SUBSITE}/wp-login.php`);
	await page.locator('#user_login').fill(username);
	await page.locator('#user_pass').fill('password');
	await page.locator('#wp-submit').click();
	await page.waitForURL(new RegExp(`/${SUBSITE}/wp-admin/`));

	return page;
};
