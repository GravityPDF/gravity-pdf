import { all } from 'redux-saga/effects'
import { watchGetResults } from './help'
import { watchUpdateSelectBox, watchTemplateProcessing, watchpostTemplateUploadProcessing } from './templates'
import { watchGetFilesFromGitHub, watchDownloadFonts } from './coreFonts'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2020, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/**
 * Generator function that watch all the watcher sagas and run them in parallel
 *
 * @since 5.2
 */
export default function * rootSaga () {
  yield all([
    watchGetResults(),
    watchUpdateSelectBox(),
    watchTemplateProcessing(),
    watchpostTemplateUploadProcessing(),
    watchGetFilesFromGitHub(),
    watchDownloadFonts()
  ])
}
