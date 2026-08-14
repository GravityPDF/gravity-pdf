import { execSync } from 'node:child_process';

// Bounded: an unbounded execSync against a wedged Docker hangs the worker until the 6h job cap.
const CLI_TIMEOUT = 120_000;

/**
 * Run one or more WP-CLI commands in the e2e wp-env container
 *
 * Batch with `&&` rather than calling this twice, so a docker-exec round trip is paid once.
 */
export function wpCli(command: string) {
	try {
		execSync(`yarn wp-env:e2e run cli bash -c "${command}"`, {
			stdio: ['ignore', 'pipe', 'pipe'],
			timeout: CLI_TIMEOUT,
		});
	} catch (err) {
		const e = err as { stderr?: Buffer; stdout?: Buffer; status?: number };

		throw new Error(
			`WP-CLI command failed (exit ${e.status ?? '?'}): ${command}\n--- stdout ---\n${e.stdout?.toString() ?? '(empty)'}\n--- stderr ---\n${e.stderr?.toString() ?? '(empty)'}`
		);
	}
}
