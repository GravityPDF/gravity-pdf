/**
 * Whether any template upload in the batch is still in flight
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.18.0
 */

/**
 * @param { Array<Object> } uploads The `templateUploads` batch from the store
 *
 * @return { boolean } True until every upload has reported back
 *
 * @since 6.18.0
 */
export const hasPendingUpload = (uploads) =>
	uploads.some((upload) => upload.status === 'pending');
