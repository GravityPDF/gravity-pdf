import type { Locator, Page, TestInfo } from '@playwright/test';
import { takeSnapshot } from '@chromatic-com/playwright';

// Long enough for an upload to lay out in the editor, short enough that a page which never holds still (a
// spinner, a marquee) costs one wait rather than stalling the suite.
const STABLE_LAYOUT_BUDGET_MS = 5000;

/**
 * Reduce the page to `targets` and their ancestors, so a Chromatic snapshot captures the container alone.
 *
 * Chromatic archives the whole document and offers no element-level crop, so the surrounding admin chrome has to be
 * hidden before the snapshot is taken. Pass every element the snapshot should keep — an accordion heading and its
 * panel are siblings, so hiding one to isolate the other would drop it.
 */
export async function isolateForSnapshot(page: Page, targets: Locator[]) {
	for (const target of targets) {
		await target.evaluate((el) => {
			el.setAttribute('data-snapshot', 'keep');

			for (let node = el.parentElement; node; node = node.parentElement) {
				node.setAttribute('data-snapshot', 'ancestor');
			}
		});
	}

	await page.evaluate(() => {
		Array.from(document.querySelectorAll<HTMLElement>('body *')).forEach(
			(el) => {
				if (
					el.dataset.snapshot === undefined &&
					!el.closest('[data-snapshot="keep"]')
				) {
					el.style.display = 'none';
				}
			}
		);

		// Hiding a grid or flex item reflows what is left onto the track its sibling occupied, which sizes the
		// container to the chrome we just removed. Ancestors lay nothing else out now, so make them plain blocks.
		Array.from(
			document.querySelectorAll<HTMLElement>('[data-snapshot="ancestor"]')
		).forEach((el) => {
			if (/grid|flex/.test(getComputedStyle(el).display)) {
				el.style.display = 'block';
			}
		});

		// The admin layout reserves space for the chrome we just hid, which would pad the snapshot out again
		const reset = document.createElement('style');
		reset.textContent =
			'html.wp-toolbar{padding-top:0}#wpcontent,#wpfooter{margin-left:0}#wpbody-content{padding-bottom:0}';
		document.head.append(reset);
	});
}

/**
 * Hold until the page stops moving, then hand it to Chromatic.
 *
 * Chromatic archives the DOM at the moment it is called and re-renders that, so whatever is mid-flight is what
 * gets baselined. Three things on these screens land after the interaction that triggered them: WordPress
 * relocates admin notices to sit after `.wp-header-end` on jQuery ready (`common.js`), TinyMCE's `wpautoresize`
 * recomputes the editor height from its own document once the content inside it lays out, and web fonts restyle
 * every text metric on the page. Waiting on any one of them by name would leave the next one to be found in a
 * baseline, so this waits on the geometry itself settling.
 *
 * The predicate polls on a timer and stays synchronous. `requestAnimationFrame` does not fire in a headless page
 * the compositor considers hidden, which is every page here once the worker moves on, and awaiting one inside the
 * page hangs until the test times out.
 */
export async function snapshot(page: Page, testinfo: TestInfo) {
	await page
		.waitForFunction(
			() => {
				const state = ((window as any).gfpdfSettle ??= {
					hash: NaN,
					held: 0,
				});

				// The editor content lives in an iframe, and it is the one whose height is in question
				const documents = [
					document,
					...Array.from(document.querySelectorAll('iframe')).map(
						(frame) => {
							try {
								return frame.contentDocument;
							} catch {
								return null;
							}
						}
					),
				].filter((doc): doc is Document => !!doc?.body);

				// Only this site's own images: an external one the container cannot reach never completes, and
				// waiting on it would burn the budget on every page the admin bar renders an avatar into.
				const settling = documents.some(
					(doc) =>
						(doc.fonts && doc.fonts.status !== 'loaded') ||
						Array.from(doc.images).some(
							(image) =>
								!image.complete &&
								image.src.startsWith(location.origin)
						)
				);

				if (settling) {
					return false;
				}

				let hash = 0;

				for (const doc of documents) {
					for (const el of Array.from(
						doc.body.querySelectorAll('*')
					)) {
						const box = el.getBoundingClientRect();

						hash =
							(hash * 31 +
								box.x +
								box.y * 7 +
								box.width * 13 +
								box.height * 17) %
							2147483647;
					}
				}

				// Two polls that measure the same geometry is as settled as this gets
				state.held = hash === state.hash ? state.held + 1 : 0;
				state.hash = hash;

				return state.held >= 2;
			},
			undefined,
			{ polling: 100, timeout: STABLE_LAYOUT_BUDGET_MS }
		)
		// A page that never holds still is a diff to look at, not a test failure
		.catch(() => {});

	await takeSnapshot(page, testinfo);
}
