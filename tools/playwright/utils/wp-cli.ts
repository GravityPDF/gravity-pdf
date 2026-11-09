import { execSync } from 'node:child_process';

// Bounded: an unbounded execSync against a wedged Docker hangs the worker until the 6h job cap.
const CLI_TIMEOUT = 120_000;

// Set by tools/playwright/config-multisite.ts
const WP_ENV = process.env.GPDF_WP_ENV ?? 'wp-env:e2e';

/**
 * Run one or more WP-CLI commands in the wp-env container under test, stopping at the first that fails, and return their output
 *
 * Pass every command to one call rather than calling this twice: each call pays a `wp-env run` round trip.
 * @param commands
 */
export function wpCli(...commands: string[]) {
	const command = commands.join(' && ');

	try {
		return execSync(`yarn ${WP_ENV} run cli bash -c "${command}"`, {
			stdio: ['ignore', 'pipe', 'pipe'],
			timeout: CLI_TIMEOUT,
		}).toString();
	} catch (err) {
		const e = err as { stderr?: Buffer; stdout?: Buffer; status?: number };

		throw new Error(
			`WP-CLI command failed (exit ${e.status ?? '?'}): ${command}\n--- stdout ---\n${e.stdout?.toString() ?? '(empty)'}\n--- stderr ---\n${e.stderr?.toString() ?? '(empty)'}`
		);
	}
}
