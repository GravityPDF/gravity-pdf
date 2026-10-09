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
 * `mask` paints over content inside the frame that legitimately differs between runs. A frame inside a
 * `position: fixed` layer, such as a modal, is shot from the viewport: a full-page capture misplaces that layer.
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
 * @param options
 * @param options.mask
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
				// Scripts that run on load (jQuery ready included) can still move the layout after it first holds still
				if (document.readyState !== 'complete') {
					return false;
				}

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

	const frame = [targets].flat();
	const fixed = await frame[0].evaluate((el) => {
		for (let node: Element | null = el; node; node = node.parentElement) {
			if (getComputedStyle(node).position === 'fixed') {
				return true;
			}
		}

		return false;
	});

	if (fixed) {
		// The clip has to sit within the viewport, and a fixed layer often scrolls inside itself. The pixel nudge below
		// moves the page, not a fixed layer, so it doesn't apply.
		await frame[0].scrollIntoViewIfNeeded();
	} else {
		// Edges and text are painted on whole pixels from wherever the frame lands, so its offset within a pixel
		// decides how they come out: content above that is a fraction of a pixel taller moves an edge inside an
		// otherwise identical frame. Nudging the page down onto a pixel boundary takes that out of the shot.
		const top = Math.min(...(await pageBoxes(frame)).map((box) => box.top));
		await setPageOffset(page, Math.ceil(top) - top);
	}

	await page.screenshot({
		path: path.join(VISUAL_SHOTS_DIR, shotName(testinfo)),
		fullPage: !fixed,
		clip: await pageArea(frame, !fixed),
		animations: 'disabled',
		caret: 'hide',
		mask,
		style: HIDE_FIXED_CHROME,
	});

	if (!fixed) {
		await setPageOffset(page, 0);
	}
}

/**
 * Push the whole page down by `offset` pixels
 * @param page
 * @param offset
 */
async function setPageOffset(page: Page, offset: number) {
	await page.evaluate((px) => {
		document.documentElement.style.marginTop = px ? `${px}px` : '';
	}, offset);
}

/**
 * Each target's box, in page coordinates, or in viewport coordinates when `inPage` is false
 * @param targets
 * @param inPage
 */
async function pageBoxes(targets: Locator[], inPage = true) {
	return await Promise.all(
		targets.map((target) =>
			target.evaluate((el, offset) => {
				const box = el.getBoundingClientRect();
				const x = offset ? window.scrollX : 0;
				const y = offset ? window.scrollY : 0;

				return {
					left: box.left + x,
					top: box.top + y,
					right: box.right + x,
					bottom: box.bottom + y,
				};
			}, inPage)
		)
	);
}

/**
 * The smallest whole-pixel rectangle that covers every target, in the coordinates `pageBoxes` uses
 * @param targets
 * @param inPage
 */
async function pageArea(targets: Locator[], inPage = true) {
	const boxes = await pageBoxes(targets, inPage);

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
