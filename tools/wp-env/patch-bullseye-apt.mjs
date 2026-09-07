#!/usr/bin/env node

/**
 * Repoint bullseye's apt repositories at archive.debian.org in wp-env's WordPress.Dockerfile template.
 *
 * Debian 11 reached EOL on 2026-08-31. deb.debian.org still advertises the bullseye-security index but its
 * pool is gone and its Release file has expired, so `apt-get update` fails while building the PHP 7.4 and 8.0
 * images — the last two the official `wordpress` tags still base on bullseye. wp-env does this same rewrite
 * for stretch and buster; this adds bullseye until it does so upstream.
 *
 * Idempotent, and a no-op on every other PHP version: wp-env touches an empty sources.list on the newer
 * trixie images, so the inserted `sed` calls match nothing there.
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, join } from 'node:path';

const TEMPLATE = join(
	dirname(
		createRequire(import.meta.url).resolve('@wordpress/env/package.json')
	),
	'lib/runtime/docker/docker-config.js'
);

// The last line of wp-env's own buster rewrite; ours slots in directly after it.
const ANCHOR = "RUN sed -i '/buster-updates/d' /etc/apt/sources.list";

// bullseye-security is dropped rather than repointed — it has not been published to archive.debian.org.
const PATCH = `

# bullseye
RUN sed -i 's|deb.debian.org/debian bullseye|archive.debian.org/debian bullseye|g' /etc/apt/sources.list
RUN sed -i '/bullseye-security/d' /etc/apt/sources.list`;

const source = readFileSync(TEMPLATE, 'utf8');

if (!source.includes('archive.debian.org/debian bullseye')) {
	if (source.includes(ANCHOR)) {
		writeFileSync(TEMPLATE, source.replace(ANCHOR, ANCHOR + PATCH));
	} else {
		console.warn(
			`Could not patch bullseye apt sources into ${TEMPLATE} — PHP 7.4 and 8.0 environments will fail to build.`
		);
	}
}
