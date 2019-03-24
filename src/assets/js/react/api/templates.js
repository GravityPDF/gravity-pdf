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
 * @returns {{method.post}}
 *
 * @since 5.2
 */
export function apiPostUpdateSelectBox () {
  return request
    .post(GFPDF.ajaxUrl)
    .field('action', 'gfpdf_get_template_options')
    .field('nonce', GFPDF.ajaxNonce)
}

/**
 * Do AJAX call
 *
 * @param {String} templateId
 *
 * @returns {{method.post}}
 *
 * @since 5.2
 */
export function apiPostTemplateProcessing (templateId) {
  return request
    .post(GFPDF.ajaxUrl)
    .field('action', 'gfpdf_delete_template')
    .field('nonce', GFPDF.ajaxNonce)
    .field('id', templateId)
}

/**
 * Do AJAX call
 *
 * @param {{file: Object, filename: String}}
 *
 * @returns {{method.post}}
 *
 * @since 5.2
 */
export function apiPostTemplateUploadProcessing (file, filename) {
  return request
    .post(GFPDF.ajaxUrl)
    .field('action', 'gfpdf_upload_template')
    .field('nonce', GFPDF.ajaxNonce)
    .attach('template', file, filename)
}
