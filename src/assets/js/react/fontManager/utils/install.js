/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

/**
 * Whether an entry has a batch in flight
 *
 * The phase vocabulary lives here rather than being spelled out at each surface, because a card, a pack row,
 * an Updates row and the entry page all have to agree about what "installing" means.
 *
 * @param {?Object} status The status object
 *
 * @return {boolean} Whether the entry is queued or installing
 *
 * @since 7.0
 */
export function isInstalling(status) {
	return status?.phase === 'queued' || status?.phase === 'installing';
}

/**
 * Whether that batch is still worth watching
 *
 * `stuck` is the server's word for "this is not coming back on its own", so it is terminal for the poller
 * even though the entry is still nominally installing — which is the one place the two questions differ.
 *
 * @param {?Object} status The status object
 *
 * @return {boolean} Whether the poller should keep reading
 *
 * @since 7.0
 */
export function isRunning(status) {
	return isInstalling(status) && !status.stuck;
}
