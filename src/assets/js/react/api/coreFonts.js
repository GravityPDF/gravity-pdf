import request from 'superagent'

/**
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2019, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       5.2
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (c) 2019, Blue Liquid Designs

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
 * Do AJAX call
 *
 * @returns {{method.get}}
 *
 * @since 5.2
 */
export function apiGetFilesFromGitHub () {
  return request
    .get(GFPDF.coreFontListUrl)
    .accept('application/vnd.github.v3+json')
    .type('json')
}

/**
 * Do AJAX call
 *
 * @param file
 * @returns {{method.post}}
 *
 * @since 5.2
 */
export function apiPostDownloadFonts (file) {
  return request
    .post(GFPDF.ajaxUrl)
    .field('action', 'gfpdf_save_core_font')
    .field('nonce', GFPDF.ajaxNonce)
    .field('font_name', file)
}
