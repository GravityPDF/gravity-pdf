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

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (c) 2020, Blue Liquid Designs

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Found
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
