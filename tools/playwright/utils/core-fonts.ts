import * as fs from 'fs';
import * as path from 'path';

const manifest: { name: string; size: number; sha: string }[] = JSON.parse(
	fs.readFileSync(
		path.join(__dirname, '..', '..', '..', 'fonts', 'core-fonts.json'),
		'utf8'
	)
);

const entryFor = (file: string) => {
	const entry = manifest.find((row) => row.name === file);
	if (entry === undefined) {
		throw new Error(`${file} is not on the core font manifest`);
	}

	return entry;
};

/**
 * The name a core font file takes when another file holds its own: `{stem}-{first 8 of its SHA-1}{ext}`
 *
 * @param file The core font file's name
 */
export function hashedName(file: string) {
	const { ext, name } = path.parse(file);

	return `${name}-${entryFor(file).sha.slice(0, 8)}${ext}`;
}

/**
 * A shell command putting a stand-in for a core font file under its hashed name (see hashedName()) beside `dest`: a
 * copy of `source` at the core file's size on the manifest. The plugin takes a hashed copy of that size as the core
 * font's own without hashing it, while one at the plain name is hashed before it's deleted (downloads are off on the
 * E2E sites).
 *
 * @param source A font file path in the container
 * @param dest   The core font file's path in the container
 */
export function coreFontStandIn(source: string, dest: string) {
	const file = path.basename(dest);
	const target = path.join(path.dirname(dest), hashedName(file));

	return `cp ${source} ${target} && truncate -s ${entryFor(file).size} ${target}`;
}
