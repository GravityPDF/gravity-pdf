import type { Locator, Page, TestInfo } from '@playwright/test';
import * as path from 'node:path';
import { config as lostPixel } from '../../lost-pixel/lostpixel.config';

const VISUAL_SHOTS_DIR = path.resolve(lostPixel.customShots.currentShotsPath);

// VISUAL=1 forces visual snapshots on, VISUAL=0 off, otherwise they follow CI. Only the screenshot is gated; the
// settle wait before it always runs, so turning capture off never changes what the surrounding test asserts.
const visualEnabled =
	process.env.VISUAL === '1' ||
	(process.env.VISUAL !== '0' && !!process.env.CI);

// Fixed admin chrome, hidden for the capture without moving anything: a full-page capture would otherwise stamp it
// wherever the viewport sat, over the section being shot
const HIDE_FIXED_CHROME =
	'#wpadminbar, #adminmenumain { visibility: hidden !important; }';

// Long enough for an upload to lay out in the editor, short enough that a page which never holds still (a
// spinner, a marquee) costs one wait rather than stalling the suite.
const STABLE_LAYOUT_BUDGET_MS = 5000;

/**
 * Hold until the page stops moving, then screenshot the area `targets` cover for Lost Pixel to diff against the
 * committed baseline. Pass every element the shot should frame: an accordion heading and its panel are siblings, so
 * framing only one drops the other. Framing the section under test keeps unrelated admin UI out of the baseline.
 * `mask` paints over content inside the frame that legitimately differs between runs.
 *
 * Whatever is mid-flight when the screenshot is taken is what gets baselined. Three things on these screens land
 * after the interaction that triggered them: WordPress relocates admin notices to sit after `.wp-header-end` on
 * jQuery ready (`common.js`), TinyMCE's `wpautoresize` recomputes the editor height from its own document once the
 * content inside it lays out, and web fonts restyle every text metric on the page. Waiting on any one of them by
 * name would leave the next one to be found in a baseline, so this waits on the geometry itself settling.
 *
 * The predicate polls on a timer and stays synchronous. `requestAnimationFrame` does not fire in a headless page
 * the compositor considers hidden, which is every page here once the worker moves on, and awaiting one inside the
 * page hangs until the test times out.
 * @param page
 * @param testinfo
 * @param targets
 */
export async function snapshot(
	page: Page,
	testinfo: TestInfo,
	targets: Locator | Locator[],
	{ mask = [] }: { mask?: Locator[] } = {}
) {
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

	if (!visualEnabled) {
		return;
	}

	await page.screenshot({
		path: path.join(VISUAL_SHOTS_DIR, shotName(testinfo)),
		fullPage: true,
		clip: await pageArea(page, [targets].flat()),
		animations: 'disabled',
		caret: 'hide',
		mask,
		style: HIDE_FIXED_CHROME,
	});
}

/**
 * The smallest whole-pixel rectangle, in page coordinates, that covers every target
 * @param page
 * @param targets
 */
async function pageArea(page: Page, targets: Locator[]) {
	const boxes = await Promise.all(
		targets.map((target) =>
			target.evaluate((el) => {
				const box = el.getBoundingClientRect();

				return {
					left: box.left + window.scrollX,
					top: box.top + window.scrollY,
					right: box.right + window.scrollX,
					bottom: box.bottom + window.scrollY,
				};
			})
		)
	);

	const x = Math.floor(Math.min(...boxes.map((box) => box.left)));
	const y = Math.floor(Math.min(...boxes.map((box) => box.top)));

	return {
		x,
		y,
		width: Math.ceil(Math.max(...boxes.map((box) => box.right))) - x,
		height: Math.ceil(Math.max(...boxes.map((box) => box.bottom))) - y,
	};
}

/**
 * A stable, unique PNG name: Lost Pixel keys each shot by filename. The project is part of it because the permalink
 * specs run under two projects.
 * @param testinfo
 */
function shotName(testinfo: TestInfo) {
	const spec = path
		.relative(process.cwd(), testinfo.file)
		.replace(/^tests\/playwright\//, '')
		.replace(/\.(spec|test)\.[cm]?[jt]s$/, '');

	const slug = [testinfo.project.name, spec, ...testinfo.titlePath.slice(1)]
		.join('-')
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.replace(/^-+|-+$/g, '');

	return `${slug}.png`;
}
