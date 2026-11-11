import * as fs from 'fs';
import * as path from 'path';

const manifest: { name: string; size: number }[] = JSON.parse(
	fs.readFileSync(
		path.join(__dirname, '..', '..', '..', 'fonts', 'core-fonts.json'),
		'utf8'
	)
);

/**
 * A shell command putting a stand-in for a core font file at `dest`: a copy of `source` at the core file's size on the
 * manifest, which the plugin takes as the core font's own copy (downloads are off on the E2E sites)
 *
 * @param source A font file path in the container
 * @param dest   The core font file's path in the container
 */
export function coreFontStandIn(source: string, dest: string) {
	const file = path.basename(dest);
	const size = manifest.find((entry) => entry.name === file)?.size;
	if (size === undefined) {
		throw new Error(`${file} is not on the core font manifest`);
	}

	return `cp ${source} ${dest} && truncate -s ${size} ${dest}`;
}
