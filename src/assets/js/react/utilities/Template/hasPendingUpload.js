/**
 * Whether any template upload in the batch is still in flight
 *
 * @package			Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       6.18.0
 */

/**
 * @param { Object } upload One entry from the `templateUploads` batch
 *
 * @return { boolean } True while the upload is in flight
 *
 * @since 6.18.0
 */
export const isPendingUpload = (upload) => upload.status === 'pending';

/**
 * @param { Array<Object> } uploads The `templateUploads` batch from the store
 *
 * @return { boolean } True until every upload has reported back
 *
 * @since 6.18.0
 */
export const hasPendingUpload = (uploads) => uploads.some(isPendingUpload);
