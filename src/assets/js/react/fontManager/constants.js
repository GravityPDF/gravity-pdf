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
 * "No font": leave whatever the document is already in alone
 *
 * `Registry::LANGUAGE_NONE` on the other side of the wire, where it is also what a language row carries when
 * nothing routes its code. Not a valid font key, so it can never collide with one.
 *
 * @since 7.0
 */
export const LANGUAGE_NONE = '*';

/**
 * The four faces, in the order every list in the UI shows them
 *
 * `id` is the file row's `role`, which is what every read speaks. `field` is the 6.x name the custom-font
 * *write* still speaks (`Font_Repository::LEGACY_FACE_ROLES`, kept because the public API and every add-on use
 * it), so an upload names its parts `regular`/`italics`/… while the row that comes back is keyed `R`/`I`/….
 * Both halves sit on one row here so nothing has to hold a second list to translate between them.
 *
 * `weight` and `style` are what a preview line renders at.
 *
 * @since 7.0
 */
export const ROLES = [
	{
		id: 'R',
		field: 'regular',
		label: 'Regular',
		weight: 400,
		style: 'normal',
		required: true,
	},
	{
		id: 'I',
		field: 'italics',
		label: 'Italic',
		weight: 400,
		style: 'italic',
		required: false,
	},
	{
		id: 'B',
		field: 'bold',
		label: 'Bold',
		weight: 700,
		style: 'normal',
		required: false,
	},
	{
		id: 'BI',
		field: 'bolditalics',
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

/**
 * The documentation page describing every way to install a font without an outbound connection
 *
 * The one page that describes all three offline routes, so it is what the import dialog offers an admin whose
 * host will not take the upload at all — the case no retry of this screen can fix.
 *
 * @since 7.0
 */
export const DOCS_OFFLINE_INSTALL =
	'https://docs.gravitypdf.com/users/installing-fonts-offline/';
