/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       7.0
 */

/**
 * The data store's name
 *
 * @since 7.0
 */
export const STORE_NAME = 'gravity-pdf/fonts';

/**
 * The REST namespace every request below is built from
 *
 * @since 7.0
 */
export const API_ROOT = '/gravity-pdf/v1/fonts';

/**
 * The id the modal mounts into, and the class its shell carries
 *
 * @since 7.0
 */
export const OVERLAY_ID = 'font-manager-overlay';

/**
 * The four faces, in the order every list in the UI shows them
 *
 * `id` is the file row's `role`; `weight` and `style` are what a preview line renders at.
 *
 * @since 7.0
 */
export const ROLES = [
	{ id: 'R', label: 'Regular', weight: 400, style: 'normal', required: true },
	{ id: 'I', label: 'Italic', weight: 400, style: 'italic', required: false },
	{ id: 'B', label: 'Bold', weight: 700, style: 'normal', required: false },
	{
		id: 'BI',
		label: 'Bold Italic',
		weight: 700,
		style: 'italic',
		required: false,
	},
];

/**
 * A page of the source browser, fixed server-side
 *
 * @since 7.0
 */
export const PER_PAGE = 50;

/**
 * Preview sizes, in points: the PDF `font_size` default sits in the middle
 *
 * @since 7.0
 */
export const PREVIEW_SIZE = { min: 6, max: 48, initial: 10 };

/**
 * The status poll's back-off, in milliseconds
 *
 * One step per read that found nothing moving; anything moving starts it again.
 *
 * @since 7.0
 */
export const POLL_INTERVALS = [2000, 5000, 10000];

/**
 * The heartbeat a poll settles into once the back-off runs out
 *
 * @since 7.0
 */
export const POLL_IDLE_INTERVAL = 30000;
