/* Dependencies */
import { all } from 'redux-saga/effects';
/* Sagas */
import {
	watchUpdateSelectBox,
	watchTemplateProcessing,
	watchpostTemplateUploadProcessing,
} from './templates';

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2026, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Generator function that watch all the watcher sagas and run them in parallel
 *
 * @since 5.2
 */
export default function* rootSaga() {
	yield all([
		watchUpdateSelectBox(),
		watchTemplateProcessing(),
		watchpostTemplateUploadProcessing(),
	]);
}
