#!/usr/bin/env node

/**
 * Resolve the WordPress version the wp-env configs should pin to, and rewrite them.
 *
 *   wp-version.mjs                  print `current`/`latest`/`changed`/`prerelease` lines (for $GITHUB_OUTPUT)
 *   wp-version.mjs update X         rewrite every wp-env config's core pin to version X
 *   wp-version.mjs download-url C   print the core zip URL for config C's pin, whichever format it uses
 *
 * Release candidates are pinned so the suite runs against in-flight releases; alphas and betas are not.
 */

import { readFileSync, writeFileSync } from 'node:fs';

// The beta channel adds in-flight releases to the stable offers. version=6.0 is an arbitrary old release,
// which guarantees every newer version is offered.
const API =
	'https://api.wordpress.org/core/version-check/1.7/?channel=beta&version=6.0';

// integration.json is the canonical source for the current pin; the three configs are kept in lockstep.
const CANONICAL = 'tools/wp-env/integration.json';

const CONFIGS = [
	'tools/wp-env/development.json',
	CANONICAL,
	'tools/wp-env/e2e.json',
];

const VERSION = /(\d+)\.(\d+)(?:\.(\d+))?(?:-([A-Za-z]+)(\d*))?/;
const EXACT_VERSION = new RegExp(`^${VERSION.source}$`);

// Matches both pin formats — a wordpress.org zip URL and a GitHub ref (WordPress/WordPress#7.1) — each
// optionally carrying a prerelease suffix (-RC3, -beta1).
const PIN = new RegExp(
	`(?<prefix>wordpress-|WordPress/WordPress#)(?<version>${VERSION.source})`
);

// alpha < beta < RC < final, so a superseded prerelease bumps to its stable release while a pin that runs
// ahead of stable is never downgraded.
const PHASES = { alpha: 0, beta: 1, rc: 2 };
const FINAL = 3;

function fail(message) {
	console.error(message);
	process.exit(1);
}

// Ordering tuple for a WordPress version, or null if it isn't a plain release/prerelease (e.g. a nightly).
function sortKey(version) {
	const match = EXACT_VERSION.exec(version);
	if (!match) {
		return null;
	}

	const [, major, minor, patch, phase, build] = match;
	return [
		Number(major),
		Number(minor),
		Number(patch ?? 0),
		PHASES[(phase ?? '').toLowerCase()] ?? FINAL,
		Number(build || 0),
	];
}

const phase = (version) => sortKey(version)?.[3];

function compare(a, b) {
	const difference = a.findIndex((part, index) => part !== b[index]);
	return difference === -1 ? 0 : a[difference] - b[difference];
}

function pinnedVersion(path) {
	return PIN.exec(readFileSync(path, 'utf8'))?.groups.version ?? '';
}

function downloadUrl(path) {
	const version = pinnedVersion(path);
	if (!version) {
		fail(`No WordPress version pin found in ${path}`);
	}

	// Both pin formats resolve to the same download; wp-env's own fetch is bypassed via WP_ENV_CORE anyway.
	console.log(`https://wordpress.org/wordpress-${version}.zip`);
}

async function latestOffered() {
	const response = await fetch(API);
	if (!response.ok) {
		fail(`WordPress API returned ${response.status}`);
	}

	const { offers } = await response.json();
	// Anything below RC — betas, alphas, nightlies — is too unstable to run the suite against.
	const candidates = [
		...new Set(offers.map((offer) => offer.version ?? '')),
	].filter((version) => phase(version) >= PHASES.rc);

	if (candidates.length === 0) {
		fail('No WordPress release or RC offered by the API');
	}

	return candidates.reduce((latest, version) =>
		compare(sortKey(version), sortKey(latest)) > 0 ? version : latest
	);
}

async function resolve() {
	const current = pinnedVersion(CANONICAL);
	const latest = await latestOffered();

	if (!current) {
		// An unpinned config, or a branch ref like #trunk — leave core alone.
		console.error(
			`No WordPress version pin found in ${CANONICAL}; skipping core update`
		);
	}

	const changed = current
		? compare(sortKey(latest), sortKey(current)) > 0
		: false;

	console.log(`current=${current}`);
	console.log(`latest=${latest}`);
	console.log(`changed=${changed}`);
	console.log(`prerelease=${phase(latest) < FINAL}`);
}

function update(version) {
	if (!sortKey(version)) {
		fail(`Unrecognised WordPress version: ${version}`);
	}

	for (const path of CONFIGS) {
		const original = readFileSync(path, 'utf8');
		// Each config keeps whichever pin format it already uses.
		const updated = original.replace(PIN, `$<prefix>${version}`);
		if (updated !== original) {
			writeFileSync(path, updated);
		}
	}
}

const [command, argument] = process.argv.slice(2);

if (!command) {
	await resolve();
} else if (command === 'update' && argument) {
	update(argument);
} else if (command === 'download-url' && argument) {
	downloadUrl(argument);
} else {
	fail('Usage: wp-version.mjs [update <version> | download-url <config>]');
}
