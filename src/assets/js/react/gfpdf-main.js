import $ from 'jquery'
import Promise from 'promise-polyfill'
import templateBootstrap from './bootstrap/templateBootstrap'

/**
 * JS Entry point for WebPack
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2017, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
 */

/*
 This file is part of Gravity PDF.

 Gravity PDF – Copyright (C) 2017, Blue Liquid Designs

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
 * Our main entry point for our modern unit-tested JS
 * This file gets run through Webpack to built it into valid ES5
 *
 * As we convert more JS to ES6 we'll likely load it from this file (unless we decide to make each feature modular)
 *
 * @since 4.1
 */
$(function () {

  'use strict'

  /* Initialise the Fancy Template Picker */
  if (GFPDF.templateList !== undefined) {

    // To add to window
    if (!window.Promise) {
      window.Promise = Promise;
    }

    /* Check if we should show the Fancy Template Picker */
    var templateId = '#gfpdf_settings\\[template\\], #gfpdf_settings\\[default_template\\]'
    var $templateField = $(templateId)

    /* Run this code if the element exists */
    if ($templateField.length > 0) {
        templateBootstrap($templateField)
    }
  }
})