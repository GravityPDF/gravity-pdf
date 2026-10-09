import type { Page } from '@playwright/test';
import { expect } from '@playwright/test';

/**
 * Record any `.gf-notice` still visible outside `#gf-admin-notices-wrapper` on DOM ready, i.e. painted before it jumps
 * into place. Call before the navigation that shows the notice.
 * @param page
 */
export async function watchNoticePlacement(page: Page) {
	await page.addInitScript(() => {
		const escaped: string[] = ((window as any).gfpdfEscapedNotices = []);

		// Runs ahead of common.js moving notices into the wrapper on jQuery ready
		document.addEventListener('DOMContentLoaded', () =>
			document.querySelectorAll('.gf-notice').forEach((notice) => {
				if (
					notice.parentElement?.id !== 'gf-admin-notices-wrapper' &&
					notice.getBoundingClientRect().height > 0
				) {
					escaped.push(notice.textContent.trim());
				}
			})
		);
	});
}

/**
 * Assert the notice holding `text` sits in `#gf-admin-notices-wrapper` and was never shown anywhere else
 * @param page
 * @param text
 */
export async function expectNoticeInPlace(page: Page, text: string) {
	await expect(
		page.locator('#gf-admin-notices-wrapper > .gf-notice', {
			hasText: text,
		})
	).toBeVisible();

	expect(
		await page.evaluate(() => (window as any).gfpdfEscapedNotices)
	).toEqual([]);
}
