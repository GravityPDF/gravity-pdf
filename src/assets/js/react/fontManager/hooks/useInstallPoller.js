/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

import { useEffect, useRef } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { POLL_IDLE_INTERVAL, POLL_INTERVALS, STORE_NAME } from '../constants';
import { isRunning } from '../utils/install';

/**
 * One timer for the whole modal
 *
 * A pack row, a family card, the entry page and an Updates row all show progress from this: the store holds one
 * status map, and every surface selects from it. The interval backs off while an install runs, settles into a
 * heartbeat once nothing is moving, stops dead when every entry is terminal or stuck, and does not run at all
 * while the tab is hidden — a background tab watching an install is the one case worth not paying for.
 *
 * @since 7.0
 */
export function useInstallPoller() {
	const statuses = useSelect(
		(select) => select(STORE_NAME).getStatuses(),
		[]
	);
	const { refreshStatuses, refreshFonts } = useDispatch(STORE_NAME);

	const previous = useRef(statuses);
	const step = useRef(0);

	const running = Object.values(statuses).some(isRunning);

	/* A finished install changes what `GET /fonts/` would say, and nothing else tells the store so */
	useEffect(() => {
		const settled = Object.keys(statuses).some(
			(id) => !isRunning(statuses[id]) && isRunning(previous.current[id])
		);

		step.current = moved(previous.current, statuses) ? 0 : step.current;
		previous.current = statuses;

		if (settled) {
			refreshFonts();
		}
	}, [statuses, refreshFonts]);

	useEffect(() => {
		if (!running) {
			return undefined;
		}

		let timer = null;

		const read = () => {
			step.current += 1;

			refreshStatuses();
		};

		const onVisibilityChange = () => {
			clearTimeout(timer);

			if (document.hidden) {
				return;
			}

			/* Back at the front: one read straight away, and the back-off starts again */
			step.current = 0;

			refreshStatuses();
		};

		if (!document.hidden) {
			timer = setTimeout(read, interval(step.current));
		}

		document.addEventListener('visibilitychange', onVisibilityChange);

		return () => {
			clearTimeout(timer);
			document.removeEventListener(
				'visibilitychange',
				onVisibilityChange
			);
		};
	}, [running, statuses, refreshStatuses]);
}

/**
 * Whether any entry's progress changed between two reads
 *
 * @param {Object} before
 * @param {Object} after
 *
 * @return {boolean} Whether anything moved
 *
 * @since 7.0
 */
export function moved(before, after) {
	const ids = new Set([...Object.keys(before), ...Object.keys(after)]);

	return [...ids].some(
		(id) =>
			before[id]?.phase !== after[id]?.phase ||
			before[id]?.files_done !== after[id]?.files_done
	);
}

/**
 * How long to wait before the next read
 *
 * The table is the whole back-off: each read that finds nothing moving takes the next step, and running off
 * the end of it is what "nothing is happening, just keep an eye on it" means.
 *
 * @param {number} step How many reads have happened since anything last moved
 *
 * @return {number} Milliseconds
 *
 * @since 7.0
 */
export function interval(step) {
	return POLL_INTERVALS[step] ?? POLL_IDLE_INTERVAL;
}
