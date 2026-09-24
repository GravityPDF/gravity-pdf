/* eslint-disable no-console -- CLI script: its output is the report */
/*
 * Reverts refreshed baselines whose only difference from the committed ones is capture noise, so a refresh commits
 * the shots that actually moved. `lost-pixel update` rewrites every baseline, and a re-encoded PNG is a new blob to
 * git even when it decodes (near-)identically.
 *
 * "Unchanged" means what the gate means by it: a drift `visual:compare` would pass, measured the way lost-pixel does
 * (pixelmatch at per-pixel tolerance 0, as a fraction of the image). Additions and deletions are left alone.
 *
 * Run after refreshing the baselines and before `git add`.
 */
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import pixelmatch from 'pixelmatch';
import { PNG } from 'pngjs';
import { config } from './lostpixel.config.ts';

const BASELINE_DIR = config.imagePathBaseline;
const threshold = config.threshold;

const git = (args: string[]) =>
	execFileSync('git', args, { encoding: 'utf8' }).trim();

// Mismatched-pixel ratio, or null when the images can't be compared pixel-for-pixel (a resize is a layout change)
function drift(before: Buffer, after: Buffer): number | null {
	try {
		const a = PNG.sync.read(before);
		const b = PNG.sync.read(after);

		if (a.width !== b.width || a.height !== b.height) {
			return null;
		}

		// No output buffer: a full-size diff image per shot is wasted memory when only the count matters
		return (
			pixelmatch(a.data, b.data, null, a.width, a.height, {
				threshold: 0,
			}) /
			(a.width * a.height)
		);
	} catch {
		return null;
	}
}

const modified = git([
	'diff',
	'--diff-filter=M',
	'--name-only',
	'--',
	BASELINE_DIR,
])
	.split('\n')
	.filter(Boolean);

const reverted: string[] = [];

for (const file of modified) {
	const committed = execFileSync('git', ['show', `HEAD:${file}`], {
		maxBuffer: 64 * 1024 * 1024,
	});
	const ratio = drift(committed, readFileSync(file));
	const label = ratio === null ? 'resized' : `${(ratio * 100).toFixed(3)}%`;

	if (ratio !== null && ratio <= threshold) {
		reverted.push(file);
		console.log(`  revert  ${label.padStart(8)}  ${file}`);
	} else {
		console.log(`  update  ${label.padStart(8)}  ${file}`);
	}
}

if (reverted.length) {
	git(['checkout', 'HEAD', '--', ...reverted]);
}

console.log(
	`\n${modified.length} rewritten: ${reverted.length} reverted as noise (threshold ${threshold * 100}%).`
);
