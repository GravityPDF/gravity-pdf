import type { CustomProjectConfig } from 'lost-pixel';

// Lost Pixel (OSS, custom-shots mode) diffs the PNGs tools/playwright/utils/snapshot.ts writes against committed
// baselines. Baselines must come from the CI runner that verifies them — macOS anti-aliasing differs — so refresh
// them with the `update-visual-baselines` PR label rather than committing local PNGs.
export const config = {
	customShots: {
		currentShotsPath: 'tmp/visual/current',
	},

	// Under tests/, which .gitattributes export-ignores, so the baselines never ship in the release zip
	imagePathBaseline: 'tests/playwright/visual-baselines',

	// Unused in custom-shots mode but still created; keep it out of the repo root. Must differ from currentShotsPath.
	imagePathCurrent: 'tmp/visual/_lostpixel-current',

	imagePathDifference: 'tmp/visual/difference',

	// Typed as deprecated, but the failOnDifference exit path is gated on it: without it a diff exits 0
	generateOnly: true,

	failOnDifference: true,

	// Fraction of pixels allowed to differ; absorbs anti-aliasing fringe between Linux runs (pixelmatch compares at
	// per-pixel tolerance 0) while still catching layout and content changes
	threshold: 0.01,
} satisfies CustomProjectConfig;
