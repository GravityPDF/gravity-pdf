import $ from 'jquery'
import templateBootstrap from './bootstrap/templateBootstrap'
import coreFontBootstrap from './bootstrap/coreFontBootstrap'
import helpBootstrap from './bootstrap/helpBootstrap'

/**
 * JS Entry point for WebPack
 *
 * @package     Gravity PDF
 * @copyright   Copyright (c) 2021, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       4.1
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

  __webpack_public_path__ = GFPDF.pluginUrl + 'dist/assets/js/' // eslint-disable-line

  /* Initialise the Fancy Template Picker */
  if (GFPDF.templateList !== undefined) {
    // To add to window
    if (!window.Promise) {
      window.Promise = Promise
    }

    /* Check if we should show the Fancy Template Picker */
    const templateId = '#gfpdf_settings\\[template\\], #gfpdf_settings\\[default_template\\]'
    const $templateField = $(templateId)

    /* Run this code if the element exists */
    if ($templateField.length > 0) {
      templateBootstrap($templateField)
    }
  }

  /* Initialise the Core Font downloader */
  if ($('#gfpdf-install-core-fonts').length) {
    coreFontBootstrap()
  }

  // Initialize the Search Bar for Help Tab
  if ($('#search-knowledgebase').length) {
    helpBootstrap()
  }
})
